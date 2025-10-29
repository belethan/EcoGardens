<?php

namespace App\Controller\Api;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ConseilController extends AbstractController
{
    /**
     * @return JsonResponse
     *
     * GET /api/conseil/
     * Récupère les conseils du mois en cours pour un utilisateur connecté.
     */

    #[Route('/api/conseil', name: 'conseil.index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(ConseilRepository $conseilRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        $conseils = $conseilRepository->findForCurrentMonth();
        // 🔹 Utilisation directe de la méthode toApiArray() de l'entité Conseil
        $data = array_map(fn($conseil) => $conseil->toApiArray(), $conseils);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * GET /api/conseil/{mois}/{annee}
     * Récupère tous les conseils du mois et de l’année spécifiés.
     */
    #[Route('/api/conseil/{mois}/{annee?}', name: 'api_conseil_by_mois_annee', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getByMoisAnnee(int $mois, ?int $annee,ConseilRepository $conseilRepository): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Mois invalide (1-12 attendu)'], Response::HTTP_BAD_REQUEST);
        }
        if ($annee !== null && ($annee < 1970 || $annee > 2200)) {
            return $this->json(['error' => 'Année invalide'], 400);
        }
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $conseils = $conseilRepository->findByMoisAnnee($mois, $annee);

        if (!$conseils) {
            return new JsonResponse(['message' => 'Aucun conseil trouvé pour cette période'], Response::HTTP_NOT_FOUND);
        }

        $payload = array_map(static function($c) {
            // Création de la méthode toApiArray() sur Conseil, aorès code ci dessous:
            if (method_exists($c, 'toApiArray')) {
                return $c->toApiArray();
            }

            // Sinon on construit un tableau minimal + email de l’auteur
            return [
                'id'          => $c->getId(),
                'contenu'     => method_exists($c, 'getContenu') ? $c->getContenu() : null,
                'auteurEmail' => method_exists($c, 'getUser') ? ($c->getUser()?->getEmail()) : null,
                // on renvoie les périodes (il peut y en avoir plusieurs)
                'temps'       => array_map(static fn($t) => [
                    'mois'  => $t->getMois(),
                    'annee' => $t->getAnnee(),
                ], method_exists($c, 'getTempsConseils') ? $c->getTempsConseils()->toArray() : []),
                'created_at'  => method_exists($c, 'getCreatedAt') && $c->getCreatedAt()
                    ? $c->getCreatedAt()->format('Y-m-d H:i:s')
                    : null,
            ];
        }, $conseils);

        return new JsonResponse($payload, Response::HTTP_OK);
    }


}
