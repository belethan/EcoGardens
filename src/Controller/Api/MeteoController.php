<?php

namespace App\Controller\Api;

use App\Service\MeteoClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/meteo', name: 'api_meteo_')]
class MeteoController extends AbstractController
{
    private MeteoClient $meteoClient;

    public function __construct(MeteoClient $meteoClient)
    {
        $this->meteoClient = $meteoClient;
    }

    #[Route('/{ville?}', name: 'get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getMeteo(?string $ville): JsonResponse
    {
        // Étape 1 : si la ville n’est pas fournie, on prend celle de l’utilisateur
        $user = $this->getUser();
        // Vérifie d'abord que l'utilisateur est bien authentifié
        if (!$user) {
            return $this->json([
                'error' => "Utilisateur non authentifié."
            ], Response::HTTP_UNAUTHORIZED);
        }
        if (!$ville) {
            if (method_exists($user, 'getVille') && $user->getVille()) {
                $ville = $user->getVille();
            } else {
                return $this->json([
                    'error' => "Aucune ville n’a été fournie et l’utilisateur n’a pas de ville définie."
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            // Étape 2 : appel au service MeteoClient (avec cache memcached)
            $data = $this->meteoClient->fetchWeather($ville);

            if (!$data) {
                return $this->json([
                    'error' => "Aucune donnée météo trouvée pour la ville '{$ville}'."
                ], Response::HTTP_NOT_FOUND);
            }

            // Étape 3 : renvoi du résultat au format JSON
            return $this->json([
                'ville' => $ville,
                'source' => 'openweathermap',
                'meteo' => $data
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des données météo : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

