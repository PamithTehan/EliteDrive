<?php
$dir = __DIR__ . '/public';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        // Skip API files that only output JSON
        if (strpos($path, 'api') !== false) continue;
        
        $content = file_get_contents($path);
        $original = $content;
        
        // 0. Clean up user's manual edits
        $content = str_replace('href="/Vehical rental system/public/', 'href="/', $content);
        $content = str_replace('href="vehical rental system\public\\', 'href="/', $content);
        
        // 1. HTML href="/..."
        // We ensure we don't double replace if baseUrl is already there. 
        // We match href="/..." where ... doesn't contain a quote or <
        $content = preg_replace('/href="\/([^"<]*)"/', 'href="<?= baseUrl(\'/$1\') ?>"', $content);
        
        // 2. HTML action="/..."
        $content = preg_replace('/action="\/([^"<]*)"/', 'action="<?= baseUrl(\'/$1\') ?>"', $content);
        
        // 3. JS fetch('/...')
        $content = preg_replace('/fetch\(\'\/([^\']*)\'/', 'fetch(\'<?= baseUrl(\'/$1\') ?>\'', $content);
        
        // 4. JS window.location.href = '/...'
        $content = preg_replace('/window\.location\.href\s*=\s*\'\/([^\']*)\'/', 'window.location.href = \'<?= baseUrl(\'/$1\') ?>\'', $content);
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: " . $file->getFilename() . "\n";
            $count++;
        }
    }
}
echo "Done replacing in $count files.\n";
