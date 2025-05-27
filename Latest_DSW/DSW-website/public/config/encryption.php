<?php
function hashPassword($password, $salt) {
    // Use PBKDF2 with SHA-256, 10000 iterations
    $iterations = 10000;
    $hash = hash_pbkdf2('sha256', $password, $salt, $iterations, 32);
    return $iterations . ':' . $salt . ':' . $hash;
}

function verifyPassword($password, $storedHash, $salt) {
    $parts = explode(':', $storedHash);
    if (count($parts) !== 3) return false;
    
    $iterations = (int)$parts[0];
    $newHash = hash_pbkdf2('sha256', $password, $salt, $iterations, 32);
    
    return hash_equals($parts[2], $newHash);
}

function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData($data, $key) {
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}
?>