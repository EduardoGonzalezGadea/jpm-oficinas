<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Controlador para la descarga de extensiones del navegador.
 * Construye un ZIP al vuelo desde el directorio de la extensión y lo envía.
 */
class ExtensionController extends Controller
{
    /**
     * Descarga la extensión CFE Detect como archivo ZIP.
     */
    public function downloadCfeDetect(): Response|string
    {
        return $this->buildAndDownload(
            directorio: base_path('extension-cfe-detect'),
            zipNombre:  'extension-cfe-detect.zip'
        );
    }

    /**
     * Descarga la extensión Text Replacer como archivo ZIP.
     */
    public function downloadTextReplacer(): Response|string
    {
        return $this->buildAndDownload(
            directorio: base_path('extension-text-replacer'),
            zipNombre:  'extension-text-replacer.zip'
        );
    }

    /**
     * Construye el ZIP y devuelve la respuesta de descarga.
     *
     * @param string $directorio Ruta absoluta al directorio fuente
     * @param string $zipNombre  Nombre del archivo ZIP resultante
     */
    private function buildAndDownload(string $directorio, string $zipNombre): Response|string
    {
        if (!extension_loaded('zip')) {
            return 'La extensión PHP ZIP no está habilitada en este servidor.';
        }

        $zipPath = storage_path($zipNombre);
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $archivos = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directorio),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($archivos as $archivo) {
                if (!$archivo->isDir()) {
                    $rutaAbsoluta = $archivo->getRealPath();
                    $rutaRelativa = substr($rutaAbsoluta, strlen($directorio) + 1);
                    $zip->addFile($rutaAbsoluta, $rutaRelativa);
                }
            }

            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
