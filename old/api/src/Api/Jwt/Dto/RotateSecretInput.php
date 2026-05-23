<?php

namespace App\Api\Jwt\Dto;

final class RotateSecretInput
{
    /**
     * Optionnel : permet de forcer la révocation immédiate
     * des anciens secrets (sinon rotation douce avec coexistence)
     */
    public ?bool $revokePrevious = false;
}
