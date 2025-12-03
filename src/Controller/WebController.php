<?php

namespace App\Controller;

use App\Service\TotpStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Web interface controller for TOTPHog.
 * 
 * Provides a simple HTML interface for managing TOTP tokens.
 * 
 * @author TOTPHog
 */
class WebController extends AbstractController
{
    /**
     * @param TotpStorage $storage In-memory TOTP token storage service
     */
    public function __construct(
        private TotpStorage $storage
    ) {}

    /**
     * Display the main dashboard with all tokens and their current codes.
     * 
     * @return Response Rendered Twig template
     */
    #[Route('/', name: 'web_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'tokens' => $this->storage->generateAllCodes(),
        ]);
    }
}
