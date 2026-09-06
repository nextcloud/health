<?php

declare(strict_types=1);

/** @var array<string, string> $_ */
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#171819" media="(prefers-color-scheme: dark)">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="Health">
	<title>Health</title>
	<link rel="manifest" href="manifest.webmanifest">
	<link rel="icon" href="<?= p($_['iconUrl']) ?>">
	<link rel="stylesheet" href="<?= p($_['styleUrl']) ?>">
</head>
<body>
	<div id="health-pwa"
		data-health-pwa-build="<?= p($_['buildVersion']) ?>"
		data-icon-url="<?= p($_['iconUrl']) ?>"
		data-icon-dark-url="<?= p($_['darkIconUrl']) ?>"></div>
	<script nonce="<?= p($_['cspNonce']) ?>" type="module" src="<?= p($_['scriptUrl']) ?>"></script>
</body>
</html>
