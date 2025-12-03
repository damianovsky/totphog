<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration tests for ApiController.
 */
class ApiControllerTest extends WebTestCase
{
    private const TEST_SECRET = 'JBSWY3DPEHPK3PXP';

    private function cleanupTokens($client): void
    {
        $client->request('DELETE', '/api/v1/tokens');
    }

    public function testHealthEndpoint(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $client->request('GET', '/api/v1/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('TOTPHog', $data['service']);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function testListTokensEmpty(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $client->request('GET', '/api/v1/tokens');

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertEquals(0, $data['count']);
    }

    public function testCreateToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestAccount',
            'secret' => self::TEST_SECRET,
            'issuer' => 'TestIssuer',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('TestAccount', $data['data']['name']);
        $this->assertEquals(self::TEST_SECRET, $data['data']['secret']);
        $this->assertEquals('TestIssuer', $data['data']['issuer']);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testCreateTokenWithUri(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $uri = 'otpauth://totp/GitHub:testuser@example.com?secret=' . self::TEST_SECRET . '&issuer=GitHub';
        
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'uri' => $uri,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals('testuser@example.com', $data['data']['name']);
        $this->assertEquals('GitHub', $data['data']['issuer']);
    }

    public function testCreateTokenWithInvalidUri(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'uri' => 'invalid://uri',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
    }

    public function testCreateTokenMissingRequiredFields(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestAccount',
            // Missing 'secret'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Name and secret are required', $data['error']);
    }

    public function testGetToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // First create a token
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestAccount',
            'secret' => self::TEST_SECRET,
        ]));
        
        $createData = json_decode($client->getResponse()->getContent(), true);
        $tokenId = $createData['data']['id'];

        // Now get the token
        $client->request('GET', '/api/v1/tokens/' . $tokenId);

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals($tokenId, $data['data']['id']);
        $this->assertEquals('TestAccount', $data['data']['name']);
    }

    public function testGetNonExistentToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('GET', '/api/v1/tokens/non-existent-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Token not found', $data['error']);
    }

    public function testDeleteToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // First create a token
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestAccount',
            'secret' => self::TEST_SECRET,
        ]));
        
        $createData = json_decode($client->getResponse()->getContent(), true);
        $tokenId = $createData['data']['id'];

        // Delete the token
        $client->request('DELETE', '/api/v1/tokens/' . $tokenId);

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals('Token deleted', $data['message']);

        // Verify token is deleted
        $client->request('GET', '/api/v1/tokens/' . $tokenId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('DELETE', '/api/v1/tokens/non-existent-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Token not found', $data['error']);
    }

    public function testDeleteAllTokens(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // Create multiple tokens
        for ($i = 1; $i <= 3; $i++) {
            $client->request('POST', '/api/v1/tokens', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'name' => "Account{$i}",
                'secret' => str_pad(self::TEST_SECRET, 16, chr(64 + $i)),
            ]));
        }

        // Delete all tokens
        $client->request('DELETE', '/api/v1/tokens');

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['deleted_count']);

        // Verify all tokens are deleted
        $client->request('GET', '/api/v1/tokens');
        $listData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $listData['count']);
    }

    public function testGetCode(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // First create a token
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestAccount',
            'secret' => self::TEST_SECRET,
        ]));
        
        $createData = json_decode($client->getResponse()->getContent(), true);
        $tokenId = $createData['data']['id'];

        // Get the code
        $client->request('GET', '/api/v1/tokens/' . $tokenId . '/code');

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('code', $data['data']);
        $this->assertArrayHasKey('remaining_seconds', $data['data']);
        $this->assertArrayHasKey('period', $data['data']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $data['data']['code']);
    }

    public function testGetCodeForNonExistentToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('GET', '/api/v1/tokens/non-existent-id/code');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Token not found', $data['error']);
    }

    public function testGetAllCodes(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // Create multiple tokens
        for ($i = 1; $i <= 2; $i++) {
            $client->request('POST', '/api/v1/tokens', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'name' => "Account{$i}",
                'secret' => str_pad(self::TEST_SECRET, 16, chr(64 + $i)),
            ]));
        }

        // Get all codes
        $client->request('GET', '/api/v1/codes');

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']);
        
        foreach ($data['data'] as $tokenWithCode) {
            $this->assertArrayHasKey('current_code', $tokenWithCode);
            $this->assertArrayHasKey('code', $tokenWithCode['current_code']);
        }
    }

    public function testGetQrCode(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // First create a token
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestAccount',
            'secret' => self::TEST_SECRET,
        ]));
        
        $createData = json_decode($client->getResponse()->getContent(), true);
        $tokenId = $createData['data']['id'];

        // Get the QR code
        $client->request('GET', '/api/v1/tokens/' . $tokenId . '/qr');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'image/png');
        
        // Verify it's a valid PNG (starts with PNG signature)
        $content = $client->getResponse()->getContent();
        $this->assertStringStartsWith("\x89PNG", $content);
    }

    public function testGetQrCodeForNonExistentToken(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('GET', '/api/v1/tokens/non-existent-id/qr');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListTokensAfterCreation(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // Create multiple tokens
        $createdIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $client->request('POST', '/api/v1/tokens', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'name' => "Account{$i}",
                'secret' => str_pad(self::TEST_SECRET, 16, chr(64 + $i)),
            ]));
            
            $createData = json_decode($client->getResponse()->getContent(), true);
            $createdIds[] = $createData['data']['id'];
        }

        // List all tokens
        $client->request('GET', '/api/v1/tokens');

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['count']);
        $this->assertCount(3, $data['data']);
        
        $returnedIds = array_column($data['data'], 'id');
        foreach ($createdIds as $id) {
            $this->assertContains($id, $returnedIds);
        }
    }

    public function testCreateTokenWithCustomParameters(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'CustomAccount',
            'secret' => self::TEST_SECRET,
            'issuer' => 'CustomIssuer',
            'digits' => 8,
            'period' => 60,
            'algorithm' => 'sha256',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals(8, $data['data']['digits']);
        $this->assertEquals(60, $data['data']['period']);
        $this->assertEquals('sha256', $data['data']['algorithm']);
    }
}
