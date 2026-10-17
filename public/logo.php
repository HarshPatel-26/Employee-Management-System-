<?php
$logoPath = __DIR__ . '/../LogowithoutBG.png';

if (!is_file($logoPath) || !is_readable($logoPath)) {
	http_response_code(404);
	exit;
}

$image = file_get_contents($logoPath);

if ($image === false) {
	http_response_code(500);
	exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
echo $image;
