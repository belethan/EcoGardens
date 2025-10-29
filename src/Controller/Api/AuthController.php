<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/api/auth', name: 'api.auth', methods: ['POST'])]
    public function auth(
        #[CurrentUser] ?User $user,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        // Si les identifiants Basic Auth sont invalides
        if (!$user) {
            return new JsonResponse([
                'error' => 'Identifiants invalides'
            ], 401);
        }

        // Création du token JWT à partir de l'utilisateur
        $token = $jwtManager->create($user);

        // Réponse JSON
        return new JsonResponse([
            'token' => $token,
            'email' => $user->getEmail(),
            'expires_in' => 3600
        ]);
    }
}
