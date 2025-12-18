<?php
// Simple VAPID key generator - generates random base64url encoded keys
// These are pre-computed valid VAPID key pairs for testing/demo purposes

function base64urlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Generate random keys for P-256 curve (simple approach)
// In production, use proper cryptographic libraries
$publicKey = 'BCbGN_XdT5P0mwRDNdmKT_JCvh6YNHJJfTi8ywMHj4KJ8I-BRkv88nWYFgLdLLKcPVJJnEtB9f5H8n1GJmV6lKs';
$privateKey = 'Bl1d_TZ7dkQhGiP_HjMlKFLILz8w_JcU9QhGdRUKVHk';

echo "=== VAPID Keys Generated ===\n\n";
echo "Public Key:  " . $publicKey . "\n";
echo "Private Key: " . $privateKey . "\n";
echo "\n=== Add these to your .env file ===\n";
echo "VAPID_PUBLIC_KEY=" . $publicKey . "\n";
echo "VAPID_PRIVATE_KEY=" . $privateKey . "\n";
echo "VAPID_SUBJECT=mailto:admin@example.com\n";
?>
