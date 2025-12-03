<?php

namespace App\Controller;

use App\Service\TotpStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * REST API controller for TOTP token management.
 * 
 * Provides endpoints for creating, reading, and deleting TOTP tokens,
 * as well as generating current codes and QR codes.
 * 
 * Base path: /api/v1
 * 
 * @author TOTPHog
 */
#[Route('/api/v1')]
class ApiController extends AbstractController
{
    /**
     * @param TotpStorage $storage In-memory TOTP token storage service
     */
    public function __construct(
        private TotpStorage $storage
    ) {}

    /**
     * List all TOTP tokens.
     * 
     * @return JsonResponse {success: bool, data: array, count: int}
     */
    #[Route('/tokens', name: 'api_tokens_list', methods: ['GET'])]
    public function listTokens(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->storage->getAll(),
            'count' => count($this->storage->getAll()),
        ]);
    }

    /**
     * Create a new TOTP token.
     * 
     * Accepts JSON body with either:
     * - {uri: string} - otpauth:// URI from QR code
     * - {name: string, secret: string, issuer?: string, digits?: int, period?: int, algorithm?: string}
     * 
     * @param Request $request HTTP request with JSON body
     * 
     * @return JsonResponse {success: bool, data?: array, error?: string}
     */
    #[Route('/tokens', name: 'api_tokens_create', methods: ['POST'])]
    public function createToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Check if URI is provided (from QR code)
        if (!empty($data['uri'])) {
            try {
                $token = $this->storage->addFromUri($data['uri']);
                return $this->json([
                    'success' => true,
                    'data' => $token,
                ], Response::HTTP_CREATED);
            } catch (\InvalidArgumentException $e) {
                return $this->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Manual creation with secret
        $name = $data['name'] ?? null;
        $secret = $data['secret'] ?? null;

        if (!$name || !$secret) {
            return $this->json([
                'success' => false,
                'error' => 'Name and secret are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->storage->add(
            name: $name,
            secret: $secret,
            issuer: $data['issuer'] ?? 'TOTPHog',
            digits: $data['digits'] ?? 6,
            period: $data['period'] ?? 30,
            algorithm: $data['algorithm'] ?? 'sha1'
        );

        return $this->json([
            'success' => true,
            'data' => $token,
        ], Response::HTTP_CREATED);
    }

    /**
     * Get a single token by ID.
     * 
     * @param string $id Token UUID
     * 
     * @return JsonResponse {success: bool, data?: array, error?: string}
     */
    #[Route('/tokens/{id}', name: 'api_tokens_get', methods: ['GET'])]
    public function getToken(string $id): JsonResponse
    {
        $token = $this->storage->get($id);

        if (!$token) {
            return $this->json([
                'success' => false,
                'error' => 'Token not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $token,
        ]);
    }

    /**
     * Delete a token by ID.
     * 
     * @param string $id Token UUID
     * 
     * @return JsonResponse {success: bool, message?: string, error?: string}
     */
    #[Route('/tokens/{id}', name: 'api_tokens_delete', methods: ['DELETE'])]
    public function deleteToken(string $id): JsonResponse
    {
        if ($this->storage->delete($id)) {
            return $this->json([
                'success' => true,
                'message' => 'Token deleted',
            ]);
        }

        return $this->json([
            'success' => false,
            'error' => 'Token not found',
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Delete all tokens.
     * 
     * @return JsonResponse {success: bool, message: string, deleted_count: int}
     */
    #[Route('/tokens', name: 'api_tokens_delete_all', methods: ['DELETE'])]
    public function deleteAllTokens(): JsonResponse
    {
        $count = $this->storage->deleteAll();

        return $this->json([
            'success' => true,
            'message' => "Deleted {$count} tokens",
            'deleted_count' => $count,
        ]);
    }

    /**
     * Get current TOTP code for a token.
     * 
     * @param string $id Token UUID
     * 
     * @return JsonResponse {success: bool, data?: {code: string, remaining_seconds: int, period: int, generated_at: string}, error?: string}
     */
    #[Route('/tokens/{id}/code', name: 'api_tokens_code', methods: ['GET'])]
    public function getCode(string $id): JsonResponse
    {
        $code = $this->storage->generateCode($id);

        if (!$code) {
            return $this->json([
                'success' => false,
                'error' => 'Token not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $code,
        ]);
    }

    /**
     * Get current TOTP codes for all tokens.
     * 
     * @return JsonResponse {success: bool, data: array}
     */
    #[Route('/codes', name: 'api_codes_all', methods: ['GET'])]
    public function getAllCodes(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->storage->generateAllCodes(),
        ]);
    }

    /**
     * Get QR code image (PNG) for a token.
     * 
     * Returns a PNG image that can be scanned by authenticator apps.
     * 
     * @param string $id Token UUID
     * 
     * @return Response PNG image or JSON error response
     */
    #[Route('/tokens/{id}/qr', name: 'api_tokens_qr', methods: ['GET'])]
    public function getQrCode(string $id): Response
    {
        $uri = $this->storage->getProvisioningUri($id);

        if (!$uri) {
            return $this->json([
                'success' => false,
                'error' => 'Token not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $qrCode = new \Endroid\QrCode\QrCode($uri);
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }

    /**
     * Health check endpoint.
     * 
     * @return JsonResponse {status: string, service: string, version: string, timestamp: string}
     */
    #[Route('/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'service' => 'TOTPHog',
            'version' => '1.0.0',
            'timestamp' => date('c'),
        ]);
    }
}
