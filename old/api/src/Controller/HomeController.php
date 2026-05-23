<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_defaut')]
    public function index(): Response
    {
        return $this->render('defaut/index.html.twig', [
            'controller_name' => 'DefautController',
        ]);
    }
}
