<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:administrar_sistema,web');
    }

    public function download($file)
    {
        if (!Storage::disk('local')->exists($file)) {
            abort(404);
        }
        return Storage::disk('local')->download($file);
    }

    public function index()
    {
        // Obtener lista de respaldos disponibles
        $backups = collect(Storage::disk('local')->files('JPM Oficinas'))
            ->filter(function($file) {
                return str_ends_with($file, '.zip');
            })
            ->map(function($file) {
                return [
                    'file' => $file,
                    'name' => basename($file),
                    'size' => Storage::disk('local')->size($file),
                    'date' => Storage::disk('local')->lastModified($file),
                ];
            })
            ->sortByDesc('date');

        return view('system.backups.index', compact('backups'));
    }

    public function create(Request $request)
    {
        try {
            $process = new Process(['powershell.exe', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts/backup.ps1')]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            if ($request->ajax()) {
                return response()->json(['message' => 'Respaldo creado exitosamente.']);
            }

            return redirect()->route('system.backups.index')
                ->with('success', 'Respaldo creado exitosamente.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Error al crear el respaldo: ' . $e->getMessage()], 500);
            }
            return redirect()->route('system.backups.index')
                ->with('error', 'Error al crear el respaldo: ' . $e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup' => 'required|string',
        ]);

        try {
            // Obtener el archivo de respaldo
            $backupPath = $request->input('backup');
            if (!Storage::disk('local')->exists($backupPath)) {
                throw new \Exception('Archivo de respaldo no encontrado');
            }

            // Restaurar la base de datos
            $this->restoreDatabase($backupPath);

            if ($request->ajax()) {
                return response()->json(['message' => 'Base de datos restaurada exitosamente.']);
            }

            return redirect()->route('system.backups.index')
                ->with('success', 'Base de datos restaurada exitosamente.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Error al restaurar la base de datos: ' . $e->getMessage()], 500);
            }
            return redirect()->route('system.backups.index')
                ->with('error', 'Error al restaurar la base de datos: ' . $e->getMessage());
        }
    }


    private function restoreDatabase($backupPath)
    {
        $filesystem = new \Illuminate\Filesystem\Filesystem();
        $tempPath = storage_path('app/temp_restore');

        try {
            // Ensure temp directory is clean and exists
            if ($filesystem->isDirectory($tempPath)) {
                $filesystem->deleteDirectory($tempPath);
            }
            $filesystem->makeDirectory($tempPath, 0755, true);

            // Extraer el archivo SQL del zip
            $zip = new \ZipArchive();
            $zipPath = storage_path('app/' . $backupPath);

            if (!$zip->open($zipPath)) {
                throw new \Exception('No se pudo abrir el archivo ZIP: ' . $zipPath);
            }

            $sqlFile = null;

            // Buscar el archivo SQL en el zip
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (str_ends_with($filename, '.sql') && strpos($filename, 'db-dumps/') !== false) {
                    $sqlFile = $filename;
                    break;
                }
            }

            if (!$sqlFile) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (str_ends_with($filename, '.sql')) {
                        $sqlFile = $filename;
                        break;
                    }
                }
            }

            if (!$sqlFile) {
                $zip->close();
                throw new \Exception('No se encontró el archivo SQL en el respaldo');
            }

            // Extraer el archivo SQL
            $zip->extractTo($tempPath, $sqlFile);
            $zip->close();

            // Configuración de la base de datos
            $host = config('database.connections.mysql.host');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $sqlFilePath = $tempPath . '/' . $sqlFile;

            // Get the path to the mysql executable from the config
            $mysqlPath = config('database.connections.mysql.dump.mysql_binary_path');
            if ($mysqlPath) {
                $mysqlPath = rtrim($mysqlPath, '/\\') . DIRECTORY_SEPARATOR;
            }

            // Construir el comando mysql
            $command = sprintf(
                '"%smysql" -h %s -u %s -p%s %s < %s',
                $mysqlPath,
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($sqlFilePath)
            );

            // Ejecutar el comando
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } finally {
            // Limpiar archivos temporales
            if ($filesystem->isDirectory($tempPath)) {
                $filesystem->deleteDirectory($tempPath);
            }
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'backup' => 'required|string',
        ]);

        try {
            $backupPath = $request->input('backup');

            if (!Storage::disk('local')->exists($backupPath)) {
                throw new \Exception('Archivo de respaldo no encontrado.');
            }

            Storage::disk('local')->delete($backupPath);

            if ($request->ajax()) {
                return response()->json(['message' => 'Respaldo eliminado exitosamente.']);
            }

            return redirect()->route('system.backups.index')
                ->with('success', 'Respaldo eliminado exitosamente.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Error al eliminar el respaldo: ' . $e->getMessage()], 500);
            }
            return redirect()->route('system.backups.index')
                ->with('error', 'Error al eliminar el respaldo: ' . $e->getMessage());
        }
    }
}
