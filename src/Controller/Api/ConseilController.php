<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/api/conseil')]
final class ConseilController extends AbstractController
{
    /**
     * @return JsonResponse
     *
     * GET /api/conseil/
     * Récupère les conseils du mois en cours pour un utilisateur connecté.
     */
    #[Route('/', name: 'conseil.index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $conseils = $this->conseilRepository->findForCurrentMonth();

        $data = array_map(function ($conseil) {
            return [
                'id' => $conseil->getId(),
                'texte' => $conseil->getTexte(),
                'mois' => $conseil->getMois(),
                'annee' => $conseil->getAnnee(),
                'created_at' => $conseil->getCreatedAt()->format('Y-m-d H:i:s'),
                'user_email' => $conseil->getUser()->getEmail(),
            ];
        }, $conseils);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * GET /api/conseil/{mois}/{annee}
     * Récupère tous les conseils du mois et de l’année spécifiés.
     */
    #[Route('/{mois}/{annee}', name: 'api_conseil_by_mois_annee', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getByMoisAnnee(int $mois, int $annee): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Mois invalide (1-12 attendu)'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $conseils = $this->conseilRepository->findByMoisAnnee($mois, $annee);

        if (!$conseils) {
            return new JsonResponse(['message' => 'Aucun conseil trouvé pour cette période'], Response::HTTP_NOT_FOUND);
        }

        $data = array_map(function ($conseil) {
            return [
                'id' => $conseil->getId(),
                'texte' => $conseil->getTexte(),
                'mois' => $conseil->getMois(),
                'annee' => $conseil->getAnnee(),
                'created_at' => $conseil->getCreatedAt()->format('Y-m-d H:i:s'),
                'user_email' => $conseil->getUser()->getEmail(),
            ];
        }, $conseils);

        return new JsonResponse($data, Response::HTTP_OK);
    }
}
