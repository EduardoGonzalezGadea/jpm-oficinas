<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PendriveController extends Controller
{
    private $disk = 'public_files';
    private $thumbnailDisk = 'public_thumbnails';
    private $thumbnailSize = [80, 80]; // Tamaño de las miniaturas

    public function index()
    {
        $files = Storage::disk($this->disk)->files();
        return view('pendrive.index', compact('files'));
    }

    public function upload(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // Tamaño máximo: 100MB por archivo
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        try {
            // Guardar el archivo original
            Storage::disk($this->disk)->putFileAs('', $file, $fileName);

            // Generar miniatura si es posible
            $this->generateThumbnail($file, $fileName);

            // Si todo fue bien, devolver éxito
            return response()->json(['success' => 'Archivo subido correctamente.']);

        } catch (FileException $e) {
            Log::error('Error de archivo al subir: ' . $e->getMessage());
            return response()->json(['error' => 'Error al escribir el archivo en el disco.'], 500);
        } catch (\Exception $e) {
            Log::error('Error inesperado al subir archivo: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado: ' . $e->getMessage()], 500);
        }
    }

    private function generateThumbnail($file, $fileName)
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $thumbnailName = 'thumb_' . pathinfo($fileName, PATHINFO_FILENAME) . '.' . $extension;

            // Crear directorio de miniaturas si no existe
            Storage::disk($this->thumbnailDisk)->makeDirectory('');

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                // Para imágenes, generar miniatura real
                $imageManager = new ImageManager('gd');
                $image = $imageManager->make($file->getRealPath());
                $image->resize($this->thumbnailSize[0], $this->thumbnailSize[1], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                Storage::disk($this->thumbnailDisk)->put($thumbnailName, (string) $image->encode());
            } else {
                // Para otros archivos, guardar un icono genérico
                $this->createGenericThumbnail($extension, $thumbnailName);
            }
        } catch (\Exception $e) {
            // Si falla la generación de miniatura, no es crítico
            // Solo registramos el error y continuamos
            Log::error('Error al generar miniatura: ' . $e->getMessage());
            // No lanzamos la excepción para que no afecte la subida del archivo
        }
    }

    private function createGenericThumbnail($extension, $thumbnailName)
    {
        // Crear una miniatura genérica basada en la extensión del archivo
        $imageManager = new ImageManager('gd');
        $image = $imageManager->canvas($this->thumbnailSize[0], $this->thumbnailSize[1], '#f8f9fa');

        // Color según tipo de archivo
        $colors = [
            'pdf' => '#dc3545',
            'doc' => '#0d6efd',
            'docx' => '#0d6efd',
            'xls' => '#198754',
            'xlsx' => '#198754',
            'ppt' => '#fd7e14',
            'pptx' => '#fd7e14',
            'zip' => '#6f42c1',
            'rar' => '#6f42c1',
            '7z' => '#6f42c1',
            'mp4' => '#e83e8c',
            'avi' => '#e83e8c',
            'mov' => '#e83e8c',
            'mp3' => '#20c997',
            'wav' => '#20c997',
            'txt' => '#6c757d'
        ];

        $color = $colors[$extension] ?? '#6c757d';
        $image->rectangle(0, 0, $this->thumbnailSize[0], $this->thumbnailSize[1], function ($draw) use ($color) {
            $draw->background($color);
        });

        // Añadir texto
        $image->text(strtoupper($extension), $this->thumbnailSize[0]/2, $this->thumbnailSize[1]/2, function ($font) {
            $font->file(5);
            $font->size(20);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        Storage::disk($this->thumbnailDisk)->put($thumbnailName, (string) $image->encode('png'));
    }

    public function getThumbnail($filename)
    {
        $sanitizedFilename = basename($filename);
        $extension = strtolower(pathinfo($sanitizedFilename, PATHINFO_EXTENSION));
        $thumbnailName = 'thumb_' . pathinfo($sanitizedFilename, PATHINFO_FILENAME) . '.' . $extension;

        if (Storage::disk($this->thumbnailDisk)->exists($thumbnailName)) {
            return response()->file(Storage::disk($this->thumbnailDisk)->path($thumbnailName));
        }

        // Si no existe la miniatura, crearla y devolverla
        if (Storage::disk($this->disk)->exists($sanitizedFilename)) {
            $this->generateThumbnailFromExisting($sanitizedFilename);
            if (Storage::disk($this->thumbnailDisk)->exists($thumbnailName)) {
                return response()->file(Storage::disk($this->thumbnailDisk)->path($thumbnailName));
            }
        }

        // Devolver miniatura genérica por defecto
        return $this->getDefaultThumbnail($extension);
    }

    private function generateThumbnailFromExisting($filename)
    {
        try { // @phpstan-ignore-next-line
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $thumbnailName = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $path = Storage::disk($this->disk)->path($filename);
                $imageManager = new ImageManager('gd');
                $image = $imageManager->make($path);
                $image->resize($this->thumbnailSize[0], $this->thumbnailSize[1], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                Storage::disk($this->thumbnailDisk)->put($thumbnailName, (string) $image->encode());
            } else {
                $this->createGenericThumbnail($extension, $thumbnailName);
            }
        } catch (\Exception $e) {
            Log::error('Error al generar miniatura desde archivo existente: ' . $e->getMessage());
            // No lanzamos la excepción para que no afecte la visualización
        }
    }

    private function getDefaultThumbnail($extension)
    {
        $imageManager = new ImageManager('gd');
        $image = $imageManager->canvas($this->thumbnailSize[0], $this->thumbnailSize[1], '#e9ecef');
        $image->text('?', $this->thumbnailSize[0]/2, $this->thumbnailSize[1]/2, function ($font) {
            $font->file(5);
            $font->size(40);
            $font->color('#6c757d');
            $font->align('center');
            $font->valign('middle');
        });

        return response()->make((string) $image->encode('png'), 200, [
            'Content-Type' => 'image/png'
        ]);
    }

    public function destroy($filename)
    {
        // Sanitize the filename to prevent directory traversal attacks
        $sanitizedFilename = basename($filename);

        if ($filename !== $sanitizedFilename) {
            return response()->json(['error' => 'Nombre de archivo no válido.'], 400);
        }

        if (!Storage::disk($this->disk)->exists($sanitizedFilename)) {
            return response()->json(['error' => 'El archivo no existe.'], 404);
        }

        try {
            // Eliminar archivo original
            Storage::disk($this->disk)->delete($sanitizedFilename);

            // Eliminar miniatura si existe
            $extension = strtolower(pathinfo($sanitizedFilename, PATHINFO_EXTENSION));
            $thumbnailName = 'thumb_' . pathinfo($sanitizedFilename, PATHINFO_FILENAME) . '.' . $extension;
            Storage::disk($this->thumbnailDisk)->delete($thumbnailName);

        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el archivo.'], 500);
        }

        return response()->json(['success' => 'Archivo eliminado correctamente.']);
    }

    public function download($filename)
    {
        // Sanitize the filename to prevent directory traversal attacks
        $sanitizedFilename = basename($filename);

        if ($filename !== $sanitizedFilename) {
            abort(400, 'Nombre de archivo no válido.');
        }

        if (!Storage::disk($this->disk)->exists($sanitizedFilename)) {
            abort(404, 'El archivo no existe.');
        }

        try {
            $path = Storage::disk($this->disk)->path($sanitizedFilename);
            return response()->download($path, $sanitizedFilename);
        } catch (\Exception $e) {
            abort(500, 'No se pudo descargar el archivo.');
        }
    }
}
