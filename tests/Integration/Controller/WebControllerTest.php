<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for WebController.
 */
class WebControllerTest extends WebTestCase
{
    private const TEST_SECRET = 'JBSWY3DPEHPK3PXP';

    private function cleanupTokens($client): void
    {
        $client->request('DELETE', '/api/v1/tokens');
    }

    public function testIndexPageLoads(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    public function testIndexPageContainsTotphogTitle(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        // Check that the page contains TOTPHog branding
        $this->assertStringContainsStringIgnoringCase('totphog', $crawler->text());
    }

    public function testIndexPageShowsEmptyStateWhenNoTokens(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        // With no tokens, the page should still load
        $this->assertLessThanOrEqual(0, $crawler->filter('[data-token-id]')->count());
    }

    public function testIndexPageShowsTokensAfterCreation(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // Create a token via API
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'TestWebAccount',
            'secret' => self::TEST_SECRET,
            'issuer' => 'WebTestIssuer',
        ]));

        // Load the web page
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        // Check that the token information is displayed
        $pageContent = $crawler->text();
        $this->assertStringContainsString('TestWebAccount', $pageContent);
    }

    public function testIndexPageShowsMultipleTokens(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // Create multiple tokens via API
        for ($i = 1; $i <= 3; $i++) {
            $client->request('POST', '/api/v1/tokens', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'name' => "WebAccount{$i}",
                'secret' => str_pad(self::TEST_SECRET, 16, chr(64 + $i)),
                'issuer' => "WebIssuer{$i}",
            ]));
        }

        // Load the web page
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        
        $pageContent = $crawler->text();
        
        // Check that all tokens are displayed
        $this->assertStringContainsString('WebAccount1', $pageContent);
        $this->assertStringContainsString('WebAccount2', $pageContent);
        $this->assertStringContainsString('WebAccount3', $pageContent);
    }

    public function testIndexPageDisplaysCurrentCode(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        
        // Create a token via API
        $client->request('POST', '/api/v1/tokens', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'CodeDisplayTest',
            'secret' => self::TEST_SECRET,
        ]));

        // Load the web page
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        
        // Check that the page contains a 6-digit code pattern
        $pageContent = $client->getResponse()->getContent();
        $this->assertMatchesRegularExpression('/\d{6}/', $pageContent);
    }

    public function testIndexReturnsHtmlContentType(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
    }

    public function testIndexPageStructure(): void
    {
        $client = static::createClient();
        $this->cleanupTokens($client);
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        
        // Check for basic HTML structure
        $this->assertSelectorExists('body');
        $this->assertSelectorExists('head');
    }
}
