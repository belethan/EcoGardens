<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/user', name: 'api.user')]
final class UserController extends AbstractController
{
    /**
     * @throws JsonException
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (!$data) {
            return $this->json(['error' => 'Requête invalide (JSON manquant ou incorrect).'], 400);
        }

        // Vérification champs obligatoires
        $required = ['email', 'password', 'ville'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'error' => "Le champ '$field' est obligatoire."
                ], 400);
            }
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setVille($data['ville']);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $data['password'])
        );

        // Code postal facultatif
        if (!empty($data['code_postal'])) {
            $user->setCodePostal($data['code_postal']);
        }

        // Validation Symfony (contraintes Assert)
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json([
                'error' => "Un utilisateur avec cet email existe déjà."
            ], 409);
        } catch (\Exception $e) {
            return $this->json([
                'error' => "Erreur interne du serveur : " . $e->getMessage()
            ], 500);
        }

        return $this->json([
            'message' => 'Utilisateur créé avec succès.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'ville' => $user->getVille(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }
}
