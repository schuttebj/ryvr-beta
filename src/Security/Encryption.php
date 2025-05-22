<?php
declare(strict_types=1);

namespace Ryvr\Security;

/**
 * Encryption utility for Ryvr.
 *
 * @since 1.0.0
 */
class Encryption
{
    /**
     * Encrypt a string.
     *
     * @param string $value String to encrypt.
     *
     * @return string Encrypted string.
     *
     * @since 1.0.0
     */
    public static function encrypt(string $value): string
    {
        if (function_exists('sodium_crypto_secretbox')) {
            // Use libsodium if available (PHP 7.2+)
            $key = self::get_encryption_key();
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $encrypted = sodium_crypto_secretbox($value, $nonce, $key);
            
            return base64_encode($nonce . $encrypted);
        }
        
        // Fallback to WordPress's encrypt function
        return wp_hash_password($value);
    }
    
    /**
     * Decrypt a string.
     *
     * @param string $encrypted_value Encrypted string.
     *
     * @return string|false Decrypted string or false on failure.
     *
     * @since 1.0.0
     */
    public static function decrypt(string $encrypted_value): string|false
    {
        if (function_exists('sodium_crypto_secretbox_open')) {
            // Use libsodium if available (PHP 7.2+)
            $key = self::get_encryption_key();
            $decoded = base64_decode($encrypted_value);
            
            if ($decoded === false) {
                return false;
            }
            
            $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
            $encrypted = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
            
            $decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
            
            if ($decrypted === false) {
                return false;
            }
            
            return $decrypted;
        }
        
        // If it's encrypted with wp_hash_password, we can't decrypt it
        return false;
    }
    
    /**
     * Get or generate encryption key.
     *
     * @return string Encryption key.
     *
     * @since 1.0.0
     */
    private static function get_encryption_key(): string
    {
        $key = get_option('ryvr_encryption_key');
        
        if (!$key) {
            if (defined('RYVR_ENCRYPTION_KEY') && RYVR_ENCRYPTION_KEY) {
                $key = RYVR_ENCRYPTION_KEY;
            } else {
                $key = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
                update_option('ryvr_encryption_key', $key);
            }
        }
        
        return base64_decode($key);
    }
} 