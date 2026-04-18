<?php

namespace App\Auth\Controller

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'message' => 'AUTH Corbisier opérationnelle 🚀'
        ]);
    }
}