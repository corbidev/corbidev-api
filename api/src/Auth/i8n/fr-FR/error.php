<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Server errors
    |--------------------------------------------------------------------------
    */

    'SERV_001' => [
        'public' => 'Une erreur interne est survenue.',
        'log' => 'Une erreur inattendue est survenue.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication errors
    |--------------------------------------------------------------------------
    */

    'AUTH_001' => [
        'public' => 'Client API inconnu.',
    ],

    'AUTH_002' => [
        'public' => 'Signature HMAC invalide.',
    ],

    'AUTH_003' => [
        'public' => 'Horodatage invalide.',
    ],

    'AUTH_004' => [
        'public' => 'Requête expirée.',
    ],

    'AUTH_005' => [
        'public' => 'Nonce invalide.',
    ],

    'AUTH_006' => [
        'public' => 'Nonce déjà utilisé.',
    ],

    'AUTH_007' => [
        'public' => 'Client API désactivé.',
    ],

    'AUTH_008' => [
        'public' => 'Secret API invalide.',
    ],

    'AUTH_009' => [
        'public' => 'Client API non autorisé.',
    ],

    'AUTH_010' => [
        'public' => 'Scope insuffisant: %s.',
        'log' => 'Le client API "%s" ne possède pas le scope requis "%s".',
    ],

];