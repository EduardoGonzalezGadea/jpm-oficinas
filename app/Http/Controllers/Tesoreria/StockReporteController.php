<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StockReporteController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf',
        ]);

        $file = $request->file('pdf');
        $filename = 'stock_valores_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        // Asegurar que el directorio existe
        $path = public_path('.docs/stock-valores');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file->move($path, $filename);

        return response()->json(['success' => true, 'filename' => $filename]);
    }

    public function download($filename)
    {
        $path = public_path('.docs/stock-valores/' . $filename);

        if (!File::exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
