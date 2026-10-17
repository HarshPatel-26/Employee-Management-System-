<?php
$svgPath = __DIR__ . '/../public/svg/';
$svgFiles = glob($svgPath . '*.svg');

foreach ($svgFiles as $file) {
    $content = file_get_contents($file);
    
    // Replace width and height attributes while preserving viewBox and other attributes
    $content = preg_replace('/(width=")[^"]*(")/i', 'width="15px"', $content);
    $content = preg_replace('/(height=")[^"]*(")/i', 'height="15px"', $content);
    
    file_put_contents($file, $content);
    echo "Updated " . basename($file) . "\n";
}

echo "All SVG files have been updated to 15px size.";
?>