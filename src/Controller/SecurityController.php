<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Déjà connecté ? On file directement vers les offres
        if ($this->getUser()) {
            return $this->redirectToRoute('app_offre');
        }

        return $this->render('security/login.html.twig', [
            'dernier_identifiant' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        // Ce code ne s'exécute jamais : Symfony intercepte la déconnexion avant
        throw new \LogicException('Intercepté par le pare-feu.');
    }
}