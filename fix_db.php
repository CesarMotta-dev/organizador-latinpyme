<?php
$file = __DIR__ . '/database.sqlite.tmp';
if (file_exists($file)) {
    $content = file_get_contents($file);
    // Check if the content starts with the UTF-8 BOM (0xEF, 0xBB, 0xBF)
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "BOM detected. Stripping...\n";
        $content = substr($content, 3);
    } else {
        echo "No BOM detected.\n";
    }
    
    file_put_contents(__DIR__ . '/database.sqlite', $content);
    echo "database.sqlite has been rewritten.\n";
} else {
    echo "database.sqlite.tmp not found.\n";
}
