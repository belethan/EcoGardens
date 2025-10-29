<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoClient
{
    private HttpClientInterface $http;
    private CacheInterface $cache;
    private string $apiKey;
    private string $countryCode;
    private string $baseUrl;
    private string $unitCode;



    public function __construct(
        HttpClientInterface $http,
        CacheInterface $meteoCache,               // alias vers le pool "meteo.cache"
        string $apiKey,
        string $countryCode = 'fr',
        string $unitCode = 'metric',
        string $baseUrl = 'https://api.openweathermap.org/data/2.5',
        private ?LoggerInterface $logger = null,
    ) {
        $this->http = $http;
        $this->cache = $meteoCache;
        $this->apiKey = $apiKey;
        $this->unitCode = $unitCode;
        $this->countryCode = $countryCode;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->logger = $logger;
    }

    private function normalizeCacheKey(string $key): string
    {
        // On remplace tout caractère interdit par un underscore "_"
        $normalized = preg_replace('/[{}()\/\\\\@:]/', '_', $key);

        // On supprime les espaces et on force en minuscule pour homogénéiser
        return strtolower(trim($normalized));
    }

    /**
     * Récupère la météo d'une ville.
     * Règle métier demandé:
     *  - Clé de cache = city name (normalisée) ; TTL 1h.
     *  - On consulte le cache avant tout appel API, sinon on appelle OWM,
     *    on stocke tel quel le JSON renvoyé, puis on retourne ce JSON.
     */
    public function fetchWeather(string $city): array
    {

        $city = trim($city);
        $cacheKey = $this->normalizeCacheKey('meteo_' . $city);

        try {
            // Log avant lecture du cache
            $this->logger?->info("Vérification du cache météo", [
                'ville' => $city,
                'clé' => $cacheKey,
            ]);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city, $cacheKey) {
                $item->expiresAfter(3600); // 1h

                $this->logger?->info("Cache manquant, appel de l'API OpenWeatherMap", [
                    'ville' => $city,
                    'clé' => $cacheKey,
                ]);

                $data = $this->fetchBasicWeather($city);

                $this->logger?->info("Données stockées en cache", [
                    'ville' => $city,
                    'clé' => $cacheKey,
                ]);

                return $data;
            });
        } catch (\Throwable $e) {
            $this->logger?->error("💥 Erreur lors de la récupération météo", [
                'ville' => $city,
                'clé' => $cacheKey,
                'erreur' => $e->getMessage(),
            ]);

            return [
                'error' => "Erreur lors de la récupération des données météo : " . $e->getMessage(),
            ];
        }
    }

    public function fetchBasicWeather(string $city): array
    {

        $city = trim($city);
        if ($city === '') {
            throw new \InvalidArgumentException('Le nom de la ville est requis.');
        }

        $url = sprintf('%s/weather', $this->baseUrl);
        $query = [
            'q'     => $city,
            'appid' => $this->apiKey,
            'lang'  => $this->countryCode ?? 'fr',
            'units' => $this->unitCode,
        ];

        try {
            $response = $this->http->request('GET', $url, ['query' => $query]);
            $status   = $response->getStatusCode();

            if ($status !== 200) {
                return [
                    'error' => "Erreur OpenWeatherMap (code $status)",
                    'response' => $response->getContent(false),
                ];
            }

            return $response->toArray(false);

        } catch (\Exception $e) {
            $this->logger?->error("💥 Erreur lors de la récupération météo basic URL", [
                'ville' => $city,
                'erreur' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur de communication avec OpenWeatherMap',
                'message' => $e->getMessage(),
            ];
        }
    }
}

