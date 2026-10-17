<?php

function loadEnvFile(string $path): void {
	if (!file_exists($path)) {
		return;
	}

	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if (!is_array($lines)) {
		return;
	}

	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
			continue;
		}

		$eqPos = strpos($line, '=');
		if ($eqPos === false) {
			continue;
		}

		$key = trim(substr($line, 0, $eqPos));
		$val = trim(substr($line, $eqPos + 1));

		// Strip wrapping quotes if present.
		if (
			(strlen($val) >= 2) &&
			(($val[0] === '"' && $val[strlen($val) - 1] === '"') || ($val[0] === "'" && $val[strlen($val) - 1] === "'"))
		) {
			$val = substr($val, 1, -1);
		}

		// Only set it if PHP doesn't already have the env var.
		$current = getenv($key);
		if ($current === false) {
			putenv($key . '=' . $val);
			$_ENV[$key] = $val;
			$_SERVER[$key] = $val;
		}
	}
}

function env(string $key, $default = '') {
	$val = getenv($key);
	if ($val === false) {
		return $default;
	}
	return $val;
}

// Load repo-local .env for local development.
loadEnvFile(__DIR__ . '/../../.env');
