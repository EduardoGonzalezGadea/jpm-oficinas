<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PendriveController extends Controller
{
    private $disk = 'public_files';

    public function index()
    {
        $files = Storage::disk($this->disk)->files();
        return view('pendrive.index', compact('files'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        try {
            Storage::disk($this->disk)->putFileAs('', $file, $fileName);
        } catch (FileException $e) {
            return back()->with('error', 'Error al subir el archivo.');
        }

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function download($filename)
    {
        $filePath = Storage::disk($this->disk)->path($filename);

        if (!Storage::disk($this->disk)->exists($filename)) {
            abort(404);
        }

        return response()->download($filePath);
    }
}
