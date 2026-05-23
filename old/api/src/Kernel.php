<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Noyau principal de l'application Symfony.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
