<?php

namespace App\Api\Jwt\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminAuthController extends AbstractController
{
   #[Route('/test-admin')]
public function test(): Response
{
    return new Response('OK');
}
    #[Route('/login', name: 'admin_login')]
    public function login(Request $request): Response
    {
        if ($request->isMethod('POST')) {

            $user = $request->request->get('user');
            $password = $request->request->get('password');

            if (
                $user === $_ENV['ADMIN_USER'] &&
                password_verify($password, $_ENV['ADMIN_PASSWORD_HASH'])
            ) {
                $request->getSession()->set('admin', true);

                return $this->redirect('/admin/consumers');
            }

            return $this->render('admin/login.html.twig', [
                'error' => 'Invalid credentials'
            ]);
        }

        return $this->render('admin/login.html.twig');
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->remove('admin');

        return $this->redirect('/admin/login');
    }
}