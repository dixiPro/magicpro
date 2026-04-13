<?php

class CryptHelper
{

    public static function crypt(array $data, string $key): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
        $result = base64_encode($iv . $encrypted);
        return $result;
    }


    public static function decrypt(string $data, string $key): array
    {
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        $result = $decrypted ? json_decode($decrypted, true) : [];
        return $result;
    }
}
