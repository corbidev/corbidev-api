<?php

namespace App\Auth\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/login', name: 'auth_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'service' => 'auth',
            'message' => 'Auth route works',
        ]);
    }
}