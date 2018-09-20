<?php

use PHPUnit\Framework\TestCase;

use Horizon\Encryption\FastEncrypt;
use phpseclib\Crypt\Random;

class FastEncryptTest extends TestCase
{

    /**
     * Tests encryption the same data two times to verify the integrity of the resulting data.
     */
    public function testEncrypt()
    {
        $random = Random::string(2048);

        // Encrypt the same string twice
        $response1 = FastEncrypt::encrypt($random);
        $response2 = FastEncrypt::encrypt($random);

        // Verify we're getting the same encrypted data
        $this->assertEquals($response1, $response2, 'Encrypted data does not match.');
    }

    /**
     * Tests decryption of encrypted data to verify the integrity of the original data.
     */
    public function testDecrypt()
    {
        $random = Random::string(2048);

        // Encrypt the data
        $encrypted = FastEncrypt::encrypt($random);

        // Decrypt the data
        $decrypted = FastEncrypt::decrypt($encrypted);

        // Verify we're getting the same encrypted data
        $this->assertEquals($random, $decrypted, 'Decrypted data does not match the original data.');
    }

}