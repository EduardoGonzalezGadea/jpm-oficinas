<?php
$baseDir = dirname(__DIR__);
$appFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir . '/app'));
$events = [];

foreach ($appFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match_all('/(?:dispatch|emit)\(\s*[\'"]([a-zA-Z0-9_\-:\.]+?)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $ev) {
                $events[$ev][] = str_replace('\\', '/', substr($file->getPathname(), strlen($baseDir) + 1));
            }
        }
    }
}

ksort($events);

$resFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir . '/resources'));
$resContent = '';
foreach ($resFiles as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['php', 'js', 'blade'])) {
        $resContent .= "\n" . file_get_contents($file->getPathname());
    }
}

echo "=== DISPATCHED EVENTS ANALYSIS ===\n\n";

$missingListeners = [];

foreach ($events as $event => $locations) {
    // Check if event is handled in JS (addEventListener or Livewire.on or #[On] in PHP)
    $hasJsListener = str_contains($resContent, "'$event'") || str_contains($resContent, "\"$event\"");
    
    // Check if event has a PHP #[On] listener
    $hasPhpListener = false;
    foreach ($appFiles as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $c = file_get_contents($file->getPathname());
            if (str_contains($c, "#[On('$event')]") || str_contains($c, "#[On(\"$event\")]")) {
                $hasPhpListener = true;
                break;
            }
        }
    }

    $status = ($hasJsListener || $hasPhpListener) ? "OK" : "MISSING";
    if ($status === "MISSING") {
        $missingListeners[$event] = $locations;
        echo "[MISSING] $event (" . count($locations) . " files)\n";
        foreach (array_unique($locations) as $loc) {
            echo "   -> $loc\n";
        }
    }
}

echo "\nTotal events scanned: " . count($events) . "\n";
echo "Total missing listeners: " . count($missingListeners) . "\n";
