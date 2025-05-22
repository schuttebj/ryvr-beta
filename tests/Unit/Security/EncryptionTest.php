<?php
declare(strict_types=1);

namespace Ryvr\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Ryvr\Security\Encryption;

class EncryptionTest extends TestCase
{
    /**
     * Test that encryption and decryption work correctly.
     */
    public function testEncryptAndDecrypt(): void
    {
        // Skip if sodium_crypto_secretbox is not available
        if (!function_exists('sodium_crypto_secretbox')) {
            $this->markTestSkipped('Libsodium is not available');
            return;
        }
        
        $originalText = 'This is a test string to encrypt and decrypt';
        
        // Encrypt the text
        $encryptedText = Encryption::encrypt($originalText);
        
        // Encrypted text should be different from original
        $this->assertNotEquals($originalText, $encryptedText);
        
        // Decrypt the text
        $decryptedText = Encryption::decrypt($encryptedText);
        
        // Decrypted text should match original
        $this->assertEquals($originalText, $decryptedText);
    }
    
    /**
     * Test that the encryption creates different outputs for multiple encryptions of the same text.
     */
    public function testEncryptionVariability(): void
    {
        // Skip if sodium_crypto_secretbox is not available
        if (!function_exists('sodium_crypto_secretbox')) {
            $this->markTestSkipped('Libsodium is not available');
            return;
        }
        
        $originalText = 'This is a test string to encrypt multiple times';
        
        // Encrypt the text twice
        $encryptedText1 = Encryption::encrypt($originalText);
        $encryptedText2 = Encryption::encrypt($originalText);
        
        // The two encrypted strings should be different (due to different nonces)
        $this->assertNotEquals($encryptedText1, $encryptedText2);
        
        // But both should decrypt to the original text
        $decryptedText1 = Encryption::decrypt($encryptedText1);
        $decryptedText2 = Encryption::decrypt($encryptedText2);
        
        $this->assertEquals($originalText, $decryptedText1);
        $this->assertEquals($originalText, $decryptedText2);
    }
    
    /**
     * Test that the encryption handles empty strings correctly.
     */
    public function testEncryptEmptyString(): void
    {
        // Skip if sodium_crypto_secretbox is not available
        if (!function_exists('sodium_crypto_secretbox')) {
            $this->markTestSkipped('Libsodium is not available');
            return;
        }
        
        $originalText = '';
        
        // Encrypt the empty string
        $encryptedText = Encryption::encrypt($originalText);
        
        // Encrypted text should not be empty
        $this->assertNotEmpty($encryptedText);
        
        // Decrypt the text
        $decryptedText = Encryption::decrypt($encryptedText);
        
        // Decrypted text should be empty
        $this->assertEquals($originalText, $decryptedText);
    }
    
    /**
     * Test that decryption fails with invalid data.
     */
    public function testDecryptInvalidData(): void
    {
        // Skip if sodium_crypto_secretbox is not available
        if (!function_exists('sodium_crypto_secretbox')) {
            $this->markTestSkipped('Libsodium is not available');
            return;
        }
        
        // Invalid base64 string
        $invalidData = 'This is not a valid encrypted string';
        
        // Decryption should fail (return false)
        $result = Encryption::decrypt($invalidData);
        $this->assertFalse($result);
    }
} 