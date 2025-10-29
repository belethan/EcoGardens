<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


final class UserController extends AbstractController
{

    #[Route('/api/user', name: 'api.usercreate', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

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
                'code_postal' => $user->getCodePostal(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }

    #[Route('/api/user/{id}', name: 'api.userupdate', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateUser(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Récupération de l'utilisateur connecté
        $currentUser = $this->getUser();

        // Vérifie si l'utilisateur est authentifié
        if (!$currentUser) {
            return $this->json(['error' => 'JWT Token non valide ou manquant'], Response::HTTP_UNAUTHORIZED);
        }

        // Recherche du compte à mettre à jour
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Seul l'utilisateur lui-même ou un admin peut modifier
        if ($currentUser->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Données JSON envoyées dans le body
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Requête invalide ou corps vide'], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour des champs (seuls ceux présents sont modifiés)
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        if (isset($data['ville'])) {
            $user->setVille($data['ville']);
        }

        if (isset($data['codePostal'])) {
            $user->setCodePostal($data['codePostal']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Compte mis à jour avec succès.Pensez à générer un nouveau Token.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'ville' => $user->getVille(),
                'code_postal' => $user->getCodePostal(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/api/user/{id}', name: 'api.userdelete', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        // Vérifie que l’utilisateur est bien authentifié
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json([
                'error' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non trouvé.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Seul l’admin ou l’utilisateur lui-même peut supprimer le compte
        if ($user !== $currentUser && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Accès refusé.'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $em->remove($user);
            $em->flush();

            return $this->json([
                'message' => 'Compte utilisateur supprimé avec succès.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
