<?php
// Simple helper to generate VAPID keys using Minishlink WebPush (run after composer install)
require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\VAPID;

// Try to generate a new keypair using the library and OpenSSL. If the environment
// doesn't support EC key generation, fall back to a precomputed test keypair.
try {
	$keys = VAPID::createVapidKeys();
} catch (Throwable $e) {
	$keys = [
		'publicKey' => 'BCbGN_XdT5P0mwRDNdmKT_JCvh6YNHJJfTi8ywMHj4KJ8I-BRkv88nWYFgLdLLKcPVJJnEtB9f5H8n1GJmV6lKs',
		'privateKey' => 'Bl1d_TZ7dkQhGiP_HjMlKFLILz8w_JcU9QhGdRUKVHk'
	];
	echo "Warning: Unable to generate a VAPID key pair using OpenSSL; using fallback test key pair.\n";
}

echo "Public: " . $keys['publicKey'] . PHP_EOL;
echo "Private: " . $keys['privateKey'] . PHP_EOL;

echo "\nAdd these to your lowk .env as:\nVAPID_PUBLIC_KEY=YOUR_PUBLIC_KEY\nVAPID_PRIVATE_KEY=YOUR_PRIVATE_KEY\n";
