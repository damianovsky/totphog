<?php

namespace App\Service;

use OTPHP\TOTP;
use Symfony\Component\Uid\Uuid;

/**
 * File-based storage for TOTP tokens.
 * 
 * This service manages TOTP tokens for development/testing purposes.
 * Data is stored in a JSON file to persist between requests.
 * 
 * @author TOTPHog
 */
class TotpStorage
{
    /**
     * Path to the JSON storage file.
     */
    private string $storagePath;

    /**
     * Storage for TOTP tokens indexed by UUID.
     * 
     * @var array<string, array{id: string, name: string, secret: string, issuer: string, digits: int, period: int, algorithm: string, created_at: string}>
     */
    private array $tokens = [];

    public function __construct(string $projectDir)
    {
        $this->storagePath = $projectDir . '/var/tokens.json';
        $this->load();
    }

    /**
     * Load tokens from JSON file.
     */
    private function load(): void
    {
        if (file_exists($this->storagePath)) {
            $data = file_get_contents($this->storagePath);
            $this->tokens = json_decode($data, true) ?? [];
        }
    }

    /**
     * Save tokens to JSON file.
     */
    private function save(): void
    {
        $dir = dirname($this->storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->storagePath, json_encode($this->tokens, JSON_PRETTY_PRINT));
    }

    /**
     * Add a new TOTP token to storage.
     * 
     * @param string $name      Display name for the token (e.g., account name)
     * @param string $secret    Base32-encoded secret key
     * @param string $issuer    Service/application name (default: TOTPHog)
     * @param int    $digits    Number of digits in generated code (default: 6)
     * @param int    $period    Code validity period in seconds (default: 30)
     * @param string $algorithm Hash algorithm: sha1, sha256, sha512 (default: sha1)
     * 
     * @return array{id: string, name: string, secret: string, issuer: string, digits: int, period: int, algorithm: string, created_at: string} Created token data
     */
    public function add(string $name, string $secret, string $issuer = 'TOTPHog', int $digits = 6, int $period = 30, string $algorithm = 'sha1'): array
    {
        $id = Uuid::v4()->toRfc4122();
        
        $this->tokens[$id] = [
            'id' => $id,
            'name' => $name,
            'secret' => $secret,
            'issuer' => $issuer,
            'digits' => $digits,
            'period' => $period,
            'algorithm' => $algorithm,
            'created_at' => date('c'),
        ];

        $this->save();
        return $this->tokens[$id];
    }

    /**
     * Add a TOTP token from an otpauth:// URI (typically from QR code).
     * 
     * @param string $uri OTPAuth URI (e.g., otpauth://totp/GitHub:user@example.com?secret=XXX&issuer=GitHub)
     * 
     * @return array{id: string, name: string, secret: string, issuer: string, digits: int, period: int, algorithm: string, created_at: string} Created token data
     * 
     * @throws \InvalidArgumentException If URI is invalid or missing required parameters
     */
    public function addFromUri(string $uri): array
    {
        $totp = TOTP::createFromSecret('temp');
        
        // Parse otpauth URI
        $parsed = parse_url($uri);
        if ($parsed === false || ($parsed['scheme'] ?? '') !== 'otpauth') {
            throw new \InvalidArgumentException('Invalid otpauth URI');
        }

        parse_str($parsed['query'] ?? '', $params);
        
        $secret = $params['secret'] ?? throw new \InvalidArgumentException('Missing secret in URI');
        $issuer = $params['issuer'] ?? 'Unknown';
        $name = ltrim($parsed['path'] ?? '/Unknown', '/');
        
        // Remove issuer prefix from name if present
        if (str_contains($name, ':')) {
            $name = explode(':', $name, 2)[1];
        }

        $digits = (int)($params['digits'] ?? 6);
        $period = (int)($params['period'] ?? 30);
        $algorithm = $params['algorithm'] ?? 'sha1';

        return $this->add($name, $secret, $issuer, $digits, $period, $algorithm);
    }

    /**
     * Get a token by its ID.
     * 
     * @param string $id Token UUID
     * 
     * @return array{id: string, name: string, secret: string, issuer: string, digits: int, period: int, algorithm: string, created_at: string}|null Token data or null if not found
     */
    public function get(string $id): ?array
    {
        return $this->tokens[$id] ?? null;
    }

    /**
     * Get all stored tokens.
     * 
     * @return array<int, array{id: string, name: string, secret: string, issuer: string, digits: int, period: int, algorithm: string, created_at: string}> List of all tokens
     */
    public function getAll(): array
    {
        return array_values($this->tokens);
    }

    /**
     * Delete a token by its ID.
     * 
     * @param string $id Token UUID
     * 
     * @return bool True if token was deleted, false if not found
     */
    public function delete(string $id): bool
    {
        if (isset($this->tokens[$id])) {
            unset($this->tokens[$id]);
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Delete all stored tokens.
     * 
     * @return int Number of tokens deleted
     */
    public function deleteAll(): int
    {
        $count = count($this->tokens);
        $this->tokens = [];
        $this->save();
        return $count;
    }

    /**
     * Generate current TOTP code for a token.
     * 
     * @param string $id Token UUID
     * 
     * @return array{code: string, remaining_seconds: int, period: int, generated_at: string}|null Code data or null if token not found
     */
    public function generateCode(string $id): ?array
    {
        $token = $this->tokens[$id] ?? null;
        if (!$token) {
            return null;
        }

        $totp = TOTP::createFromSecret($token['secret']);
        $totp->setDigits($token['digits']);
        $totp->setPeriod($token['period']);
        
        $now = time();
        $remainingSeconds = $token['period'] - ($now % $token['period']);

        return [
            'code' => $totp->now(),
            'remaining_seconds' => $remainingSeconds,
            'period' => $token['period'],
            'generated_at' => date('c'),
        ];
    }

    /**
     * Generate current TOTP codes for all tokens.
     * 
     * @return array<int, array{id: string, name: string, secret: string, issuer: string, digits: int, period: int, algorithm: string, created_at: string, current_code: array{code: string, remaining_seconds: int, period: int, generated_at: string}}> All tokens with their current codes
     */
    public function generateAllCodes(): array
    {
        $result = [];
        foreach ($this->tokens as $id => $token) {
            $codeData = $this->generateCode($id);
            $result[] = array_merge($token, ['current_code' => $codeData]);
        }
        return $result;
    }

    /**
     * Get the otpauth:// provisioning URI for a token (for QR code generation).
     * 
     * @param string $id Token UUID
     * 
     * @return string|null Provisioning URI or null if token not found
     */
    public function getProvisioningUri(string $id): ?string
    {
        $token = $this->tokens[$id] ?? null;
        if (!$token) {
            return null;
        }

        $totp = TOTP::createFromSecret($token['secret']);
        $totp->setLabel($token['name']);
        $totp->setIssuer($token['issuer']);
        $totp->setDigits($token['digits']);
        $totp->setPeriod($token['period']);

        return $totp->getProvisioningUri();
    }
}
