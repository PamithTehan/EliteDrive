<?php
$dir = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        // Skip API files that output JSON or the fix script itself
        if (strpos($path, 'api') !== false) continue;
        if (strpos($path, 'fix.php') !== false) continue;
        
        $content = file_get_contents($path);
        $original = $content;
        
        // Match standard links that start with / (but avoid ones that already have <?= baseUrl)
        $content = preg_replace('/href="\/([^"<]*)"/', 'href="<?= baseUrl(\'/$1\') ?>"', $content);
        $content = preg_replace('/action="\/([^"<]*)"/', 'action="<?= baseUrl(\'/$1\') ?>"', $content);
        
        // JS replace
        $content = preg_replace('/fetch\(\'\/([^\']*)\'/', 'fetch(\'<?= baseUrl(\'/$1\') ?>\'', $content);
        $content = preg_replace('/window\.location\.href\s*=\s*\'\/([^\']*)\'/', 'window.location.href = \'<?= baseUrl(\'/$1\') ?>\'', $content);
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: " . $file->getFilename() . "<br>\n";
            $count++;
        }
    }
}
echo "Done replacing in $count files.";
