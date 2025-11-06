<?php

namespace App\Controller\Api;

use App\Entity\Conseil;
use App\Entity\tempsConseil;
use App\Repository\ConseilRepository;
use App\Repository\tempsConseilRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        $data = array_map(static fn($conseil) => $conseil->toApiArray(), $conseils);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * GET /api/conseil/{mois}/{annee}
     * Récupère tous les conseils du mois et de l’année spécifiés.
     */
    #[Route('/api/conseil/{mois}/{annee?}', name: 'api.conseil', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getByMoisAnnee(int $mois, ?int $annee,ConseilRepository $conseilRepository): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Mois invalide (1-12 attendu)'], Response::HTTP_BAD_REQUEST);
        }
        if ($annee !== null && ($annee < 1970 || $annee > 2100)) {
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

            // Sinon, on construit un tableau minimal + email de l’auteur
            return [
                'id'          => $c->getId(),
                'contenu'     => method_exists($c, 'getContenu') ? $c->getContenu() : null,
                'auteurEmail' => method_exists($c, 'getUser') ? ($c->getUser()?->getEmail()) : null,
                // on renvoie les périodes (il peut y en avoir plusieurs,)
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

    /**
     * @Route("/api/auth/conseil", name="api_conseil_add", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    #[Route('/api/auth/conseil', name:'api.Addconseil', methods:["POST"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addConseil(Request $request, EntityManagerInterface $em): Response
    {
        // Vérification de l'utilisateur JWT
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Décodage du JSON reçu
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['texte']) || !isset($data['mois'])) {
            return $this->json([
                'error' => 'Les champs "texte" et "mois" sont obligatoires.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Création du conseil
            $conseil = new Conseil();
            $conseil->setContenu($data['texte']);
            // Protection, car symfony pourrait renvoyer n’importe quel objet implémentant
            if ($user instanceof \App\Entity\User) {
                $conseil->setUser($user);
            }

            $conseil->setCreatedAt(new DateTimeImmutable());

            $em->persist($conseil);

            // Ajout des mois/années associés dans TempsConseil
            foreach ($data['mois'] as $moisInfo) {
                if (!isset($moisInfo['mois']) || !isset($moisInfo['annee'])) {
                    continue;
                }

                $tempsConseil = new tempsConseil();
                $tempsConseil->setMois((int) $moisInfo['mois']);
                $tempsConseil->setAnnee((int) $moisInfo['annee']);
                $conseil->addTempsConseil($tempsConseil); // relation synchronisée dans les deux sens

                $em->persist($tempsConseil);
            }

            $em->flush();

            return $this->json([
                'message' => 'Conseil ajouté avec succès',
                'conseil' => [
                    'id' => $conseil->getId(),
                    'texte' => $conseil->getcontenu(),
                    'user' => $user->getEmail(),
                    'mois_associes' => array_map(function ($tc) {
                        return [
                            'mois' => $tc->getMois(),
                            'annee' => $tc->getAnnee(),
                        ];
                    }, $conseil->getTempsConseils()->toArray())
                ]
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l’ajout du conseil : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/conseil/{id}', name: 'api.Delconseil', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteConseil(
        int $id,
        EntityManagerInterface $em,
        ConseilRepository $conseilRepository
    ): JsonResponse {
        // Vérification de l'authentification (JWT déjà géré par firewall)
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Recherche du conseil à supprimer
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            return $this->json([
                'error' => 'Conseil introuvable.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Si besoin, on vérifie que seul l’admin ou le propriétaire peut supprimer
        if (!$this->isGranted('ROLE_ADMIN') && $conseil->getUser() !== $user) {
            return $this->json([
                'error' => 'Accès refusé. Vous ne pouvez pas supprimer ce conseil.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Suppression en cascade manuelle pour respecter l’intégrité
        foreach ($conseil->getTempsConseils() as $tempsConseil) {
            $conseil->removeTempsConseil($tempsConseil);
            $em->remove($tempsConseil);
        }

        // Suppression du conseil
        $em->remove($conseil);
        $em->flush();

        return $this->json([
            'message' => 'Conseil supprimé avec succès.',
            'id_supprime' => $id
        ], Response::HTTP_OK);
    }

    #[Route('/api/auth/conseil/{id}', name: 'api.Updconseil', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        tempsConseilRepository $tempsRepo
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $conseil = $em->getRepository(Conseil::class)->find($id);
        if (!$conseil) {
            return $this->json(['error' => 'Conseil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Mise à jour du texte si fourni
        if (array_key_exists('texte', $data)) {
            $texte = trim((string)($data['texte'] ?? ''));
            if ($texte === '') {
                return $this->json(['error' => 'Le champ "texte" ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $conseil->setContenu($texte);
        }

        $changes = [
            'added' => [],
            'removed' => [],
            'skipped' => [], // pour transparence (invalide, doublon, inexistant à supprimer)
        ];

        if (isset($data['mois']) && is_array($data['mois'])) {
            foreach ($data['mois'] as $i => $row) {
                // Validation structurelle minimale
                if (!is_array($row) || !isset($row['mois'])) {
                    $changes['skipped'][] = [
                        'index' => $i,
                        'reason' => 'Entrée mois invalide (champ "mois" manquant).'
                    ];
                    continue;
                }

                $moisSigned = (int)$row['mois'];
                $annee = isset($row['annee']) ? (int)$row['annee'] : (int)date('Y'); // 👈 année auto si absente
                $absMois = abs($moisSigned);    // On passe le mois en valeur absolue pour faciliter la recherche

                // Garde-fous
                if ($absMois < 1 || $absMois > 12) {
                    $changes['skipped'][] = [
                        'index' => $i,
                        'reason' => 'Mois hors plage 1..12.'
                    ];
                    continue;
                }

                if ($annee < 1970 || $annee > 2100) {
                    $changes['skipped'][] = [
                        'mois' => $absMois,
                        'annee' => $annee,
                        'reason' => 'Année hors plage 1900..2100.'
                    ];
                    continue;
                }

                // ---- CONTRÔLE D’EXISTENCE Repository TempsConseil----
                $existant = $tempsRepo->findOneBy([
                    'conseil' => $conseil,
                    'mois'    => $absMois,
                    'annee'   => $annee,
                ]);

                if ($moisSigned < 0) {
                    // Suppression
                    if ($existant) {
                        $em->remove($existant);
                        $changes['removed'][] = [
                            'mois' => $absMois,
                            'annee' => $annee
                        ];
                    } else {
                        $changes['skipped'][] = [
                            'mois' => $absMois,
                            'annee' => $annee,
                            'reason' => 'Inexistant — rien à supprimer.'
                        ];
                    }
                    continue;
                }

                // Ajout
                if ($moisSigned > 0) {
                    if ($existant) {
                        $changes['skipped'][] = [
                            'mois' => $absMois,
                            'annee' => $annee,
                            'reason' => 'Déjà présent — pas de doublon.'
                        ];
                    } else {
                        $t = new tempsConseil();
                        $t->setConseil($conseil);
                        $t->setMois($absMois);
                        $t->setAnnee($annee);
                        $em->persist($t);
                        $changes['added'][] = [
                            'mois' => $absMois,
                            'annee' => $annee,
                            'source' => isset($row['annee']) ? 'fourni' : 'auto'
                        ];
                    }
                }
            }
        }
        // enregistrement des modifications
        $em->flush();

        return $this->json([
            'message' => 'Conseil mis à jour avec succès',
            'changes' => $changes,
            'conseil' => [
                'id' => $conseil->getId(),
                'contenu' => $conseil->getContenu(),
                'mois_associes' => array_map(
                    fn(tempsConseil $t) => ['mois' => $t->getMois(), 'annee' => $t->getAnnee()],
                    $conseil->getTempsConseils()->toArray()
                ),
            ],
        ], Response::HTTP_OK);
    }
}
