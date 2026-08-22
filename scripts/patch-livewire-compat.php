#!/usr/bin/env php
<?php
/**
 * Parchea livewire.min.js para compatibilidad con Chrome 83 (Zorin OS).
 *
 * Livewire 4 usa features ES2021+ que Chrome 83 no soporta:
 * 1. ??= (nullish coalescing assignment, Chrome 85+)
 * 2. String.prototype.replaceAll (Chrome 85+)
 *
 * Se ejecuta automáticamente tras composer install / composer update.
 */

$base = __DIR__ . '/..';
$files = [
    $base . '/vendor/livewire/livewire/dist/livewire.min.js',
    $base . '/public/vendor/livewire/livewire.min.js',
];

$polyfill = 'if(!String.prototype.replaceAll){String.prototype.replaceAll=function(s,r){if(s instanceof RegExp){if(!s.global)s=new RegExp(s.source,s.flags+"g");return this.replace(s,r)}return this.replace(new RegExp(s.replace(/[.*+?^${}()|[\\]\\\\]/g,"\\\\$&"),"g"),r)}};';

$replacements = [
    'e.__errorsState??=_.reactive({clientErrors:null})'
        => 'e.__errorsState??(e.__errorsState=_.reactive({clientErrors:null}))',
    'e.__lastErrorsSnapshot??=e.snapshot,'
        => 'e.__lastErrorsSnapshot??(e.__lastErrorsSnapshot=e.snapshot),',
    't.clientErrors??=e.snapshot.memo.errors},'
        => 't.clientErrors??(t.clientErrors=e.snapshot.memo.errors)},',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        continue;
    }

    $content = file_get_contents($file);
    $original = $content;

    // 1. Reemplazar ??= por ?? (x = y) — reemplazos exactos
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }

    // 2. Agregar polyfill de replaceAll SOLO al inicio del IIFE principal
    if (strpos($content, 'String.prototype.replaceAll') === false) {
        // Buscar solo la PRIMERA ocurrencia de (()=>{
        $pos = strpos($content, '(()=>{');
        if ($pos !== false) {
            $content = substr_replace($content, '(()=>{' . $polyfill, $pos, strlen('(()=>{'));
        }
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "[patch] OK: " . basename($file) . "\n";
    } else {
        echo "[patch] Sin cambios: " . basename($file) . "\n";
    }
}

echo "[patch] Listo.\n";
