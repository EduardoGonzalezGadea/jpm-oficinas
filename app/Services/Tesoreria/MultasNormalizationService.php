<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\TesMultasItems;
use Illuminate\Support\Collection;

class MultasNormalizationService
{
    /**
     * Generates a summary of fines grouped by Article and Section.
     * Returns an array with 'grouped' collection and 'unclassified' collection.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getResumenData($dateFrom, $dateTo)
    {
        $items = TesMultasItems::whereHas('cobrada', function ($q) use ($dateFrom, $dateTo) {
            $q->whereDate('fecha', '>=', $dateFrom)
                ->whereDate('fecha', '<=', $dateTo);
        })->with('cobrada')->get();

        $grouped = [];
        $unclassified = []; // Will be populated at the end for pure unclassifieds

        // Initialize grouping
        foreach ($items as $item) {
            $normalized = $this->normalizeItem($item);

            // Note: We group by Article + Apartado + PRICE to separate variations initially
            // This allows us to detect "Anomalies" (Wrong Price for Article) vs "Variations" (Valid different prices)
            $priceKey = (string)round($item->importe, 0); // Round to integer for grouping stability

            // Unique key for this specific variant
            $key = $normalized['articulo'] . '|' . $normalized['apartado'] . '|' . $priceKey;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'articulo' => $normalized['articulo'],
                    'apartado' => $normalized['apartado'],
                    'price_key' => $priceKey, // Store convenient price key
                    'descripcion_list' => [],
                    'cantidad' => 0,
                    'importe_total' => 0,
                    'valores_unitarios' => [],
                    'items_raw' => [] // Keep track of raw items for "Unclassified" fallback
                ];
            }

            // Create concatenated description for mode detection
            $fullDesc = trim($item->detalle . ' ' . $item->descripcion);
            $fullDesc = preg_replace('/^MULTAS DE TRANSITO-\s*/i', '', $fullDesc);
            $fullDesc = preg_replace('/^.*?CORRESPONDE\s+(?:A\s+)?((?:ART|ARTICULO).*)$/i', '$1', $fullDesc);

            if ($fullDesc !== '') {
                $grouped[$key]['descripcion_list'][] = $fullDesc;
            }

            $grouped[$key]['cantidad']++;
            $grouped[$key]['importe_total'] += $item->importe;
            $grouped[$key]['valores_unitarios'][] = $item->importe;

            // If it's unclassified, keep content for potential separate listing if not merged
            if ($normalized['articulo'] === 'Otros / Sin Clasificar') {
                $grouped[$key]['items_raw'][] = $item;
            }
        }

        // --- MERGE LOGIC: FREQUENCY BY PRICE ---
        // We look for groups that share the same PRICE.
        // If there is a DOMINANT group for that price, we merge small/unclassified groups into it.

        $groupsByPrice = [];
        foreach ($grouped as $key => $data) {
            $pKey = $data['price_key'];
            if (!isset($groupsByPrice[$pKey])) {
                $groupsByPrice[$pKey] = [];
            }
            $groupsByPrice[$pKey][] = $key;
        }

        foreach ($groupsByPrice as $priceStr => $keys) {
            if (count($keys) < 2) continue; // Unique price, no conflict

            // Sort keys by quantity desc (Dominant first)
            usort($keys, function ($a, $b) use ($grouped) {
                return $grouped[$b]['cantidad'] <=> $grouped[$a]['cantidad'];
            });

            $dominantKey = $keys[0];

            // Dominant must have at least 2 items to extract gravity (reduced from 3 to catch smaller valid groups)
            // But if it's "Unclassified", it can never be dominant over a Classified one
            if ($grouped[$dominantKey]['articulo'] === 'Otros / Sin Clasificar') {
                // Try to find first Classified group
                $foundClassified = false;
                foreach ($keys as $k) {
                    if ($grouped[$k]['articulo'] !== 'Otros / Sin Clasificar') {
                        $dominantKey = $k;
                        $foundClassified = true;
                        break;
                    }
                }
                if (!$foundClassified) continue; // All are unclassified, cannot merge
            }

            $dominant = &$grouped[$dominantKey];

            for ($i = 0; $i < count($keys); $i++) {
                $victimKey = $keys[$i];
                if ($victimKey === $dominantKey) continue;

                $victim = $grouped[$victimKey];

                $isUnclassified = ($victim['articulo'] === 'Otros / Sin Clasificar');
                $isSmall = ($victim['cantidad'] <= 2);

                // Allow merging into "Dominant" even if it's only moderately sized (e.g. 2 items)
                // provided the victim is strictly smaller (e.g. 1 item).
                $isDominantStrong = ($dominant['cantidad'] >= 3);
                $isDominantModerate = ($dominant['cantidad'] == 2);

                $shouldMerge = false;

                if ($isUnclassified) {
                    $shouldMerge = true;
                } elseif ($isSmall) {
                    // CRITICAL FIX: Do NOT merge if the victim has a distinct, valid Article classification
                    // We only want to merge "Unclassified" items or items that might be typos.
                    // If it is a distinct Article (e.g. Art. 22 vs Art. 19), even if it has same price and low quantity,
                    // it is likely a legit different fine and should NOT be hidden.
                    if ($victim['articulo'] !== 'Otros / Sin Clasificar' && $victim['articulo'] !== $dominant['articulo']) {
                        $shouldMerge = false;
                    } else {
                        if ($isDominantStrong) {
                            // Strong dominance: Merge
                            $shouldMerge = true;
                        } elseif ($isDominantModerate && $victim['cantidad'] < $dominant['cantidad']) {
                            // Moderate dominance: Merge only if clear winner (2 vs 1)
                            $shouldMerge = true;
                        }
                    }
                }

                if ($shouldMerge) {
                    // MERGE
                    $dominant['cantidad'] += $victim['cantidad'];
                    $dominant['importe_total'] += $victim['importe_total'];
                    $dominant['valores_unitarios'] = array_merge($dominant['valores_unitarios'], $victim['valores_unitarios']);
                    $dominant['descripcion_list'] = array_merge($dominant['descripcion_list'], $victim['descripcion_list']);

                    // Remove victim group
                    unset($grouped[$victimKey]);
                }
            }
        }

        // Separate remaining Unclassified items
        $finalGrouped = [];
        $unclassifiedItems = [];

        foreach ($grouped as $key => $group) {
            if ($group['articulo'] === 'Otros / Sin Clasificar') {
                // If it survived merge, it returns to unclassified list
                foreach ($group['items_raw'] as $rawItem) {
                    $unclassifiedItems[] = $rawItem;
                }
            } else {
                $finalGrouped[] = $group;
            }
        }

        // Transform into a collection of objects and sort naturally
        $resultGrouped = collect($finalGrouped)->map(function ($group) {
            $uniqueValues = array_unique($group['valores_unitarios']);
            sort($uniqueValues);

            if (count($uniqueValues) === 1) {
                $valorUnitarioDisplay = '$ ' . number_format(reset($uniqueValues), 2, ',', '.');
            } else {
                $formattedValues = array_map(function ($val) {
                    return '$ ' . number_format($val, 2, ',', '.');
                }, $uniqueValues);
                $valorUnitarioDisplay = implode(' / ', $formattedValues);
            }

            // Determining Most Frequent Description (Mode)
            $description = '';
            if (!empty($group['descripcion_list'])) {
                $counts = array_count_values($group['descripcion_list']);
                arsort($counts);
                $description = array_key_first($counts);
            }

            return (object) [
                'articulo' => $group['articulo'],
                'articulo_sort' => is_numeric($group['articulo']) ? (int)$group['articulo'] : 999999,
                'apartado' => $group['apartado'],
                'descripcion_display' => $description,
                'cantidad' => $group['cantidad'],
                'valor_unitario_display' => $valorUnitarioDisplay,
                'importe_total' => $group['importe_total'],
            ];
        })->sortBy(['articulo_sort', 'apartado'])->values();

        return [
            'grouped' => $resultGrouped,
            'unclassified' => collect($unclassifiedItems)
        ];
    }

    // Helper for safe float array keys
    function stringify_float($float)
    {
        return (string)round($float, 2);
    }

    // Kept for backward compatibility or potential future use, though currently unused in favor of dynamic description
    private function getLinkDescription($articulo)
    {
        $map = [
            '184' => 'SEGURO OBLIGATORIO (SOA)',
        ];
        return $map[$articulo] ?? null;
    }

    /**
     * Normalizes a single item to extract Article and Section.
     *
     * @param TesMultasItems $item
     * @return array
     */
    private function normalizeItem(TesMultasItems $item)
    {
        // Combine fields and normalize text
        // Note: Sometimes relevant info is in detalle, sometimes in descripcion
        $rawText = $item->detalle . ' ' . $item->descripcion;
        $text = strtoupper($rawText);
        $text = preg_replace('/\s+/', ' ', $text); // Remove extra whitespace

        // Remove accents for easier matching
        $text = $this->removeAccents($text);

        $articulo = null;
        $apartado = ''; // Default empty, not 'General' to keep it clean if not found

        // --- PRE-PROCESSING FOR CONCATENATED DESCRIPTIONS ---
        // Fix for cases where description contains multiple fines (e.g. "MULTAS DE TRANSITO- ART.141 ... MULTAS DE TRANSITO- ART.170")
        // We take the LAST occurrence of "MULTAS DE TRANSITO" as the valid one
        $lastHeaderPos = strrpos($text, 'MULTAS DE TRANSITO');
        $searchableText = $text;

        if ($lastHeaderPos !== false && $lastHeaderPos > 0) {
            // Use only the part from the last header onwards
            $searchableText = substr($text, $lastHeaderPos);
        }

        // --- 1. PRIORITY RULE: "CORRESPONDE" ---
        // Looks for "CORRESPONDE ART 123" or "CORRESPONDE ART 123/A"
        if (preg_match('/CORRESPONDE\s*(?:A\s*)?(?:AL\s*)?(?:ART\.?|ARTICULO)?\s*(\d+)(?:\s*\/\s*([A-Z0-9]+))?/i', $searchableText, $matches)) {
            $articulo = $matches[1];
            if (isset($matches[2]) && $matches[2] !== '') {
                $apartado = 'Ap. ' . $matches[2];
            }
        }

        // --- 2. Specific Known Cases ---
        elseif (str_contains($searchableText, 'SOA') || str_contains($searchableText, 'SEGURO OBLIGATORIO')) {
            $articulo = '184';
        }

        // --- 3. Standard Article Detection ---
        // Looks for "ART. 123" or "ART. 123/A"
        elseif (preg_match('/(?:ART\.?|ARTICULO)\.?\s*(\d+)(?:\s*\/\s*([A-Z0-9]+))?/i', $searchableText, $matches)) {
            $articulo = $matches[1];
            if (isset($matches[2]) && $matches[2] !== '') {
                $apartado = 'Ap. ' . $matches[2];
            }
        }

        // Fallback for unclassified
        if (!$articulo) {
            $articulo = 'Otros / Sin Clasificar';
        }

        // --- Apartado Detection (Explicit Keyword) ---
        // Only run if we haven't found an apartado yet
        if (!$apartado && preg_match('/(?:APART|APART\.|AP|AP\.|APARTADO)\.?\s*([A-Z0-9]+(?:\/[A-Z0-9]+)?)/i', $searchableText, $apMatches)) {
            $apartado = 'Ap. ' . $apMatches[1];
        }

        return [
            'articulo' => $articulo,
            'apartado' => $apartado,
        ];
    }

    private function removeAccents($string)
    {
        $unwanted = [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Ñ' => 'N',
            'ñ' => 'n'
        ];
        return strtr($string, $unwanted);
    }
}
