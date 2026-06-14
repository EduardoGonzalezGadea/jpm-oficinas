<?php

namespace App\Services\Tesoreria;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CfeUniversalParserService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function parsePdf(string $rutaAbsoluta): array
    {
        $content = file_get_contents($rutaAbsoluta);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $trimmedContent = rtrim($content);
        if (str_ends_with($trimmedContent, '%%E')) {
            $content = $trimmedContent . 'OF';
        } elseif (str_ends_with($trimmedContent, '%%EO')) {
            $content = $trimmedContent . 'F';
        }

        $pdf = $this->parser->parseContent($content);
        $texto = $pdf->getText();

        $datos = $this->extraerDatos($texto);

        // Extracción geométrica precisa para Receptor (separa Nombre y Domicilio que están en celdas adyacentes)
        try {
            $pages = $pdf->getPages();
            if (count($pages) > 0) {
                $dataTm = $pages[0]->getDataTm();
                
                $yTop = null;
                $yBottom = null;
                
                foreach ($dataTm as $tm) {
                    $text = trim($tm[1]);
                    $y = round($tm[0][5], 2);
                    
                    if (str_contains(str_replace('Ó', 'O', strtoupper($text)), 'NOMBRE O DENOMINACION')) {
                        $yTop = $y;
                    }
                }
                
                if ($yTop !== null) {
                    foreach ($dataTm as $tm) {
                        $text = trim($tm[1]);
                        $y = round($tm[0][5], 2);
                        
                        if (str_contains(strtoupper($text), 'INFORMACION ADICIONAL') || strtoupper($text) === 'PERIODO' || strtoupper($text) === 'FECHA' || strtoupper($text) === 'DETALLE') {
                            if ($y < $yTop) {
                                if ($yBottom === null || $y > $yBottom) {
                                    $yBottom = $y;
                                }
                            }
                        }
                    }
                    
                    if ($yBottom === null) {
                        $yBottom = $yTop - 100;
                    }
                    
                    $nombre = [];
                    $domicilio = [];
                    
                    foreach ($dataTm as $tm) {
                        $text = trim($tm[1]);
                        if ($text === '') continue;
                        $x = round($tm[0][4], 2);
                        $y = round($tm[0][5], 2);
                        
                        if ($y < $yTop && $y > $yBottom) {
                            if ($x >= 230 && $x < 390) {
                                $nombre[] = $text;
                            } elseif ($x >= 390) {
                                $domicilio[] = $text;
                            }
                        }
                    }
                    
                    if (!empty($nombre)) {
                        $datos['receptor_nombre_denominacion'] = implode(" ", $nombre);
                    }
                    if (!empty($domicilio)) {
                        $datos['receptor_domicilio_fiscal'] = implode(" ", $domicilio);
                    } else {
                        $datos['receptor_domicilio_fiscal'] = '';
                    }
                }
            }
        } catch (\Exception $e) {
            // Si falla la extracción geométrica, se conserva el resultado de la extracción por RegEx
        }

        return $datos;
    }

    public function extraerDatos(string $texto): array
    {
        $datos = [
            'emisor_nombre' => 'Jefatura de Policía de Montevideo', // Suponemos constante o se puede mejorar
            'emisor_direccion' => '',
            'emisor_localidad' => '',
            'emisor_telefono' => '',
            'emisor_correo' => '',
            'emisor_ruc' => '',
            
            'documento_tipo' => '',
            'documento_serie' => '',
            'documento_numero' => '',
            'forma_pago' => '',
            'vencimiento' => null,
            'comprobante_tipo' => '',
            
            'receptor_documento_ruc' => '',
            'receptor_nombre_denominacion' => '',
            'receptor_domicilio_fiscal' => '',
            
            'periodo' => '',
            'nro_compra' => '',
            'fecha' => null,
            'moneda' => 'UYU',
            
            'items' => [],
            'medios_pago' => [],
            
            'monto_no_facturable' => 0.0,
            'monto_total' => 0.0,
            'total_a_pagar' => 0.0,
            
            'referencias' => '',
            'adenda' => ''
        ];

        // RUC Emisor
        if (preg_match('/(\d{12})\s+(?:e-Factura|e-Ticket|e-Boleta)/i', $texto, $m)) {
            $datos['emisor_ruc'] = trim($m[1]);
        }

        // Tipo de Comprobante / Documento
        if (preg_match('/(e-Factura|e-Ticket|e-Boleta)(?:\s+Cobranza)?/i', $texto, $m)) {
            $datos['documento_tipo'] = trim($m[0]);
        }

        if (preg_match('/Consumidor Final/i', $texto)) {
            $datos['comprobante_tipo'] = 'Consumidor Final';
        }

        // Serie, Número, Forma Pago, Vencimiento
        if (preg_match('/([A-Z])\s+(\d+)\s+(Contado|Cr.dito|Crédito|Credito)(?:\s+(\d{2}\/\d{2}\/\d{4}))?/iu', $texto, $m)) {
            $datos['documento_serie'] = trim($m[1]);
            $datos['documento_numero'] = trim($m[2]);
            $datos['forma_pago'] = trim($m[3]);
            if (isset($m[4]) && $m[4]) {
                try {
                    $datos['vencimiento'] = Carbon::createFromFormat('d/m/Y', $m[4])->format('Y-m-d');
                } catch (\Exception $e) {}
            }
        }

        // Emisor Telefono y Correo
        if (preg_match('/Tel\.:\s*(.*?)(?=\s+jpmonte|RUC COMPRADOR|$)/i', $texto, $m)) {
            $datos['emisor_telefono'] = trim($m[1]);
        }
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $texto, $m)) {
            $datos['emisor_correo'] = trim($m[1]);
        }
        
        if (preg_match('/VARELA JOSE PEDRO 3440\s*(.*?)(?=\s*Tel\.:)/is', $texto, $m)) {
             $datos['emisor_direccion'] = 'VARELA JOSE PEDRO 3440';
             $datos['emisor_localidad'] = trim(str_replace("\n", " ", $m[1]));
        }

        // Receptor (RUC / C.I.)
        if (preg_match('/C\.I\.\s*\(UY\)[\s:]*([\d\.-]+)/i', $texto, $m)) {
            $datos['receptor_documento_ruc'] = trim($m[1]);
        } elseif (preg_match('/RUC COMPRADOR[\s:]*(\d{12})/i', $texto, $m)) {
            $datos['receptor_documento_ruc'] = trim($m[1]);
        } elseif (preg_match('/DOCUMENTO RECEPTOR[\s:]*([\d\.-]+)/i', $texto, $m)) {
            $datos['receptor_documento_ruc'] = trim($m[1]);
        }

        // Nombre Receptor
        if (preg_match('/NOMBRE O DENOMINACI.N DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu', $texto, $m)) {
             $lines = explode("\n", trim($m[1]));
             $datos['receptor_nombre_denominacion'] = trim($lines[0]);
             if (count($lines) > 1) {
                  $datos['receptor_domicilio_fiscal'] = trim(implode(" ", array_slice($lines, 1)));
             }
        } elseif (preg_match('/FISCAL\s*\n(.*?)(?=\s*\n\s*(?:INFORMACION|DETALLE|PERIODO|FECHA|$))/isu', $texto, $m)) {
             // Otro posible formato
             $lines = explode("\n", trim($m[1]));
             $datos['receptor_nombre_denominacion'] = trim($lines[0]);
             if (count($lines) > 1) {
                  $datos['receptor_domicilio_fiscal'] = trim(implode(" ", array_slice($lines, 1)));
             }
        }

        // Periodo
        if (preg_match('/PERIODO\s*\n\s*(.*?)(?=\s*\n\s*(?:FECHA|DETALLE|$))/isu', $texto, $m)) {
             $datos['periodo'] = trim($m[1]);
        }

        // Fecha y Moneda
        if (preg_match('/FECHA\s*MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})\s*(.*?)(?=\s*\n\s*(?:DETALLE|$))/isu', $texto, $m)) {
            try {
                $datos['fecha'] = Carbon::createFromFormat('d/m/Y', $m[1])->format('Y-m-d');
            } catch (\Exception $e) {}
            if (stripos($m[2], 'Dólar') !== false) {
                 $datos['moneda'] = 'USD';
            }
        }

        // Totales
        if (preg_match('/MONTO NO FACTURABLE:\s*(-?[\d\.,]+)/i', $texto, $m)) {
             $datos['monto_no_facturable'] = $this->parseMonto($m[1]);
        }
        if (preg_match('/MONTO TOTAL\.:\s*(-?[\d\.,]+)/i', $texto, $m)) {
             $datos['monto_total'] = $this->parseMonto($m[1]);
        }
        if (preg_match('/TOTAL A PAGAR:\s*(-?[\d\.,]+)/i', $texto, $m)) {
             $datos['total_a_pagar'] = $this->parseMonto($m[1]);
        }

        // Items (Bloque entre DETALLE... y MONTO NO FACTURABLE / MONTO TOTAL)
        if (preg_match('/DETALLE DESCRIPCI.N CANT\. PRECIO DESC\. REC\. IMPORTE\s*\n(.*?)(?=\s*\n\s*MONTO (?:NO FACTURABLE|TOTAL))/isu', $texto, $m)) {
            $itemsBlock = trim($m[1]);
            $lines = explode("\n", $itemsBlock);
            
            $currentItem = null;
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Tratar de hacer match al final de la línea que contiene los montos
                // Ej: "1,000 (Unid) 7.392,00 7.392,00" o "1,000 (Unid) 589,00 -179.056,00"
                if (preg_match('/(-?[\d\.,]+)\s*\((.*?)\)\s+(-?[\d\.,]+)(?:\s+(-?[\d\.,]+))?(?:\s+(-?[\d\.,]+))?\s+(-?[\d\.,]+)$/u', $line, $im)) {
                     // The match $im has the amounts. 
                     $cantidad = $this->parseMonto($im[1]);
                     // The text before the quantity is the part of description or it was on previous lines
                     $prefix = trim(substr($line, 0, strpos($line, $im[1])));
                     
                     if ($currentItem === null) {
                         $currentItem = ['detalle' => $prefix, 'descripcion' => '', 'cantidad' => $cantidad, 'precio' => 0, 'descuento' => 0, 'recargo' => 0, 'importe' => 0];
                     } else {
                         if (!empty($prefix)) {
                             $currentItem['descripcion'] .= " " . $prefix;
                         }
                         $currentItem['cantidad'] = $cantidad;
                     }
                     
                     // Resolving parsed amounts
                     $matchesCount = count($im) - 1;
                     $currentItem['importe'] = $this->parseMonto($im[$matchesCount]);
                     
                     if ($matchesCount == 6) { // cantidad, uni, precio, desc, rec, importe
                         $currentItem['precio'] = $this->parseMonto($im[3]);
                         $currentItem['descuento'] = $this->parseMonto($im[4]);
                         $currentItem['recargo'] = $this->parseMonto($im[5]);
                     } elseif ($matchesCount == 5) {
                         $currentItem['precio'] = $this->parseMonto($im[3]);
                         $currentItem['descuento'] = $this->parseMonto($im[4]);
                     } elseif ($matchesCount == 4) {
                         $currentItem['precio'] = $this->parseMonto($im[3]);
                     }
                     
                     $currentItem['descripcion'] = trim($currentItem['descripcion']);
                     $datos['items'][] = $currentItem;
                     $currentItem = null; // reset
                } else {
                     // Lína de texto descriptivo
                     if ($currentItem === null) {
                         $currentItem = ['detalle' => $line, 'descripcion' => '', 'cantidad' => 1, 'precio' => 0, 'descuento' => 0, 'recargo' => 0, 'importe' => 0];
                     } else {
                         $currentItem['descripcion'] .= " " . $line;
                     }
                }
            }
            if ($currentItem !== null) {
                // If the loop ended with an incomplete item, just add it
                $currentItem['descripcion'] = trim($currentItem['descripcion']);
                $datos['items'][] = $currentItem;
            }
        }

        // Medios de pago
        if (preg_match('/TOTAL A PAGAR:[\s\d\.,]+\n(.*?)(?=\s*\n\s*REFERENCIAS:)/isu', $texto, $m)) {
            $bloqueMp = trim($m[1]);
            $lines = explode("\n", $bloqueMp);
            $lastLabel = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/^(?:([^:]+):)?\s*(?:UYU|USD|\$)?\s*([\d\.,]+)$/ui', $line, $mp)) {
                    $tipo = !empty($mp[1]) ? trim($mp[1]) : (!empty($lastLabel) ? $lastLabel : 'Medio de pago');
                    $datos['medios_pago'][] = [
                        'tipo' => $tipo,
                        'valor' => $this->parseMonto($mp[2])
                    ];
                    $lastLabel = '';
                } elseif (preg_match('/^(.+?)\s+(?:UYU|USD|\$)?\s*([\d\.,]+)$/ui', $line, $mp)) {
                    $tipo = trim($mp[1]);
                    $datos['medios_pago'][] = [
                        'tipo' => $tipo,
                        'valor' => $this->parseMonto($mp[2])
                    ];
                    $lastLabel = '';
                } else {
                    $lastLabel = rtrim($line, ': ');
                }
            }
        }

        // El pie de página (número, Fecha de Vencimiento, Fecha emisor...) marca el fin del contenido útil
        $footerPattern = '\s*\n\s*(?:\d+\s*\n)?\s*(?:Fecha\s+de\s+Vencimiento|Fecha\s+emisor|Puede\s+verificar|I\.V\.A\.|NÚMERO\s+DE\s+CAE)';

        // Referencias: desde "REFERENCIAS:" hasta "ADENDA" o el pie de página
        if (preg_match('/REFERENCIAS:\s*\n(.*?)(?=\s*\n\s*ADENDA|' . $footerPattern . '|$)/isu', $texto, $m)) {
            $ref = trim($m[1]);
            // Limpiar líneas que ya pertenecen al pie (ej: "UYU 7.685,00", " 1")
            $ref = preg_replace('/\n?\s*(?:UYU|USD)[\d\s\.\,]+$/u', '', $ref);
            $datos['referencias'] = trim($ref);
        }

        // Adenda: desde "ADENDA" hasta el pie de página
        if (preg_match('/ADENDA\s*\n(.*?)(?=' . $footerPattern . '|$)/isu', $texto, $m)) {
            $datos['adenda'] = trim($m[1]);
        }

        return $datos;
    }

    private function parseMonto(string $monto): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $monto);
    }
}
