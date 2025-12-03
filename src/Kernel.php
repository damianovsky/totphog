<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * TOTPHog Application Kernel.
 * 
 * Symfony micro-kernel for the TOTP code manager application.
 * 
 * @author TOTPHog
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
