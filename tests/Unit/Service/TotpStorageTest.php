<?php

namespace App\Tests\Unit\Service;

use App\Service\TotpStorage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TotpStorage service.
 */
class TotpStorageTest extends TestCase
{
    private string $tempDir;
    private TotpStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/totphog_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->storage = new TotpStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        $tokensFile = $this->tempDir . '/var/tokens.json';
        if (file_exists($tokensFile)) {
            unlink($tokensFile);
        }
        if (is_dir($this->tempDir . '/var')) {
            rmdir($this->tempDir . '/var');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testAddToken(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP',
            issuer: 'TestIssuer',
            digits: 6,
            period: 30,
            algorithm: 'sha1'
        );

        $this->assertArrayHasKey('id', $token);
        $this->assertEquals('TestAccount', $token['name']);
        $this->assertEquals('JBSWY3DPEHPK3PXP', $token['secret']);
        $this->assertEquals('TestIssuer', $token['issuer']);
        $this->assertEquals(6, $token['digits']);
        $this->assertEquals(30, $token['period']);
        $this->assertEquals('sha1', $token['algorithm']);
        $this->assertArrayHasKey('created_at', $token);
    }

    public function testAddTokenWithDefaults(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP'
        );

        $this->assertEquals('TOTPHog', $token['issuer']);
        $this->assertEquals(6, $token['digits']);
        $this->assertEquals(30, $token['period']);
        $this->assertEquals('sha1', $token['algorithm']);
    }

    public function testGetToken(): void
    {
        $addedToken = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP'
        );

        $retrievedToken = $this->storage->get($addedToken['id']);

        $this->assertNotNull($retrievedToken);
        $this->assertEquals($addedToken['id'], $retrievedToken['id']);
        $this->assertEquals('TestAccount', $retrievedToken['name']);
    }

    public function testGetNonExistentToken(): void
    {
        $token = $this->storage->get('non-existent-id');
        $this->assertNull($token);
    }

    public function testGetAllTokens(): void
    {
        $this->storage->add(name: 'Account1', secret: 'SECRET1AAAAAAAAA');
        $this->storage->add(name: 'Account2', secret: 'SECRET2BBBBBBBBBB');
        $this->storage->add(name: 'Account3', secret: 'SECRET3CCCCCCCCCC');

        $allTokens = $this->storage->getAll();

        $this->assertCount(3, $allTokens);
    }

    public function testDeleteToken(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP'
        );

        $result = $this->storage->delete($token['id']);

        $this->assertTrue($result);
        $this->assertNull($this->storage->get($token['id']));
    }

    public function testDeleteNonExistentToken(): void
    {
        $result = $this->storage->delete('non-existent-id');
        $this->assertFalse($result);
    }

    public function testDeleteAllTokens(): void
    {
        $this->storage->add(name: 'Account1', secret: 'SECRET1AAAAAAAAA');
        $this->storage->add(name: 'Account2', secret: 'SECRET2BBBBBBBBBB');
        $this->storage->add(name: 'Account3', secret: 'SECRET3CCCCCCCCCC');

        $count = $this->storage->deleteAll();

        $this->assertEquals(3, $count);
        $this->assertCount(0, $this->storage->getAll());
    }

    public function testGenerateCode(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP'
        );

        $codeData = $this->storage->generateCode($token['id']);

        $this->assertNotNull($codeData);
        $this->assertArrayHasKey('code', $codeData);
        $this->assertArrayHasKey('remaining_seconds', $codeData);
        $this->assertArrayHasKey('period', $codeData);
        $this->assertArrayHasKey('generated_at', $codeData);
        $this->assertEquals(6, strlen($codeData['code']));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codeData['code']);
        $this->assertLessThanOrEqual(30, $codeData['remaining_seconds']);
        $this->assertGreaterThan(0, $codeData['remaining_seconds']);
    }

    public function testGenerateCodeForNonExistentToken(): void
    {
        $codeData = $this->storage->generateCode('non-existent-id');
        $this->assertNull($codeData);
    }

    public function testGenerateAllCodes(): void
    {
        $this->storage->add(name: 'Account1', secret: 'SECRETAAAAAAAAAA');
        $this->storage->add(name: 'Account2', secret: 'SECRETBBBBBBBBBB');

        $allCodes = $this->storage->generateAllCodes();

        $this->assertCount(2, $allCodes);
        foreach ($allCodes as $tokenWithCode) {
            $this->assertArrayHasKey('current_code', $tokenWithCode);
            $this->assertArrayHasKey('code', $tokenWithCode['current_code']);
        }
    }

    public function testGetProvisioningUri(): void
    {
        $token = $this->storage->add(
            name: 'testuser@example.com',
            secret: 'JBSWY3DPEHPK3PXP',
            issuer: 'TestService'
        );

        $uri = $this->storage->getProvisioningUri($token['id']);

        $this->assertNotNull($uri);
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=TestService', $uri);
    }

    public function testGetProvisioningUriForNonExistentToken(): void
    {
        $uri = $this->storage->getProvisioningUri('non-existent-id');
        $this->assertNull($uri);
    }

    public function testAddFromUri(): void
    {
        $uri = 'otpauth://totp/GitHub:user@example.com?secret=JBSWY3DPEHPK3PXP&issuer=GitHub&digits=6&period=30&algorithm=sha1';
        
        $token = $this->storage->addFromUri($uri);

        $this->assertArrayHasKey('id', $token);
        $this->assertEquals('user@example.com', $token['name']);
        $this->assertEquals('JBSWY3DPEHPK3PXP', $token['secret']);
        $this->assertEquals('GitHub', $token['issuer']);
        $this->assertEquals(6, $token['digits']);
        $this->assertEquals(30, $token['period']);
    }

    public function testAddFromUriWithDefaults(): void
    {
        $uri = 'otpauth://totp/ServiceName:myaccount?secret=JBSWY3DPEHPK3PXP&issuer=ServiceName';
        
        $token = $this->storage->addFromUri($uri);

        $this->assertEquals('myaccount', $token['name']);
        $this->assertEquals(6, $token['digits']);
        $this->assertEquals(30, $token['period']);
        $this->assertEquals('sha1', $token['algorithm']);
    }

    public function testAddFromUriInvalidScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid otpauth URI');
        
        $this->storage->addFromUri('https://example.com/totp');
    }

    public function testAddFromUriMissingSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing secret in URI');
        
        $this->storage->addFromUri('otpauth://totp/Test?issuer=TestIssuer');
    }

    public function testTokensPersistence(): void
    {
        // Add token with first storage instance
        $token = $this->storage->add(
            name: 'PersistentAccount',
            secret: 'JBSWY3DPEHPK3PXP'
        );

        // Create new storage instance (simulating app restart)
        $newStorage = new TotpStorage($this->tempDir);
        
        // Token should be loaded from file
        $retrievedToken = $newStorage->get($token['id']);
        
        $this->assertNotNull($retrievedToken);
        $this->assertEquals('PersistentAccount', $retrievedToken['name']);
    }

    public function testGenerateCodeWithDifferentDigits(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP',
            digits: 8
        );

        $codeData = $this->storage->generateCode($token['id']);

        $this->assertNotNull($codeData);
        $this->assertEquals(8, strlen($codeData['code']));
        $this->assertMatchesRegularExpression('/^\d{8}$/', $codeData['code']);
    }

    public function testGenerateCodeWithDifferentPeriod(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP',
            period: 60
        );

        $codeData = $this->storage->generateCode($token['id']);

        $this->assertNotNull($codeData);
        $this->assertEquals(60, $codeData['period']);
        $this->assertLessThanOrEqual(60, $codeData['remaining_seconds']);
    }

    public function testUniqueIdsForMultipleTokens(): void
    {
        $token1 = $this->storage->add(name: 'Account1', secret: 'SECRET1AAAAAAAAA');
        $token2 = $this->storage->add(name: 'Account2', secret: 'SECRET2BBBBBBBBBB');
        $token3 = $this->storage->add(name: 'Account3', secret: 'SECRET3CCCCCCCCCC');

        $this->assertNotEquals($token1['id'], $token2['id']);
        $this->assertNotEquals($token2['id'], $token3['id']);
        $this->assertNotEquals($token1['id'], $token3['id']);
    }

    public function testTokenIdIsValidUuid(): void
    {
        $token = $this->storage->add(
            name: 'TestAccount',
            secret: 'JBSWY3DPEHPK3PXP'
        );

        // UUID v4 format validation
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $token['id']
        );
    }
}
