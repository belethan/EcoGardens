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
    private string $unite;


    public function __construct(
        HttpClientInterface $http,
        CacheInterface $meteoCache,               // alias vers le pool "meteo.cache"
        string $apiKey,
        string $countryCode = 'fr',
        string $unite = 'imperial',
        string $baseUrl = 'https://api.openweathermap.org/data/2.5',
        private ?LoggerInterface $logger = null,
    ) {
        $this->http = $http;
        $this->cache = $meteoCache;
        $this->apiKey = $apiKey;
        $this->unite = $unite;
        $this->countryCode = $countryCode;
        $this->baseUrl = rtrim($baseUrl, '/');
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
        if ($city === '') {
            throw new \InvalidArgumentException('Le nom de la ville est requis.');
        }

        // normalisation simple pour la clé cache ex : metep:paris
        $cacheKey = 'meteo:' . mb_strtolower($city, 'UTF-8');
        // Appel au cache recherche et si non trouvé $cachekey alors execute la callback
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city) {
            // use ($city) est super important pour garder la bonne valeur de $city dans la closure
            // TTL 1h (sécurisé même si default_lifetime déjà à 1h)
            $item->expiresAfter(3600);

            $url = sprintf(
                '%s/weather',
                $this->baseUrl
            );

            $query = [
                'q'     => sprintf('%s,%s', $city, $this->countryCode),
                'appid' => $this->apiKey,
                'lang'   => $this->lang ?? 'fr',
                'units' => $this->unite,
            ];
            //On lance une exception dans le callback, rien n’est mis en cache et l’exception remonte.
            // D’où l’intérêt de gérer proprement les erreurs dans le callback
            try {
                $response = $this->http->request('GET', $url, ['query' => $query]);
                $status   = $response->getStatusCode();
                $data     = $response->toArray(false); // false => ne jette pas en cas de 4xx/5xx

                if ($status !== 200) {
                    // On ne met PAS en cache les erreurs; on les propage.
                    $message = $data['message'] ?? 'Erreur OpenWeatherMap';
                    throw new \RuntimeException("OWM HTTP $status: $message");
                }

                // Ici, on stocke tel quel le JSON (converti en array) comme demandé
                return $data;
            } catch (\Throwable $e) {
                // Logging utile au debug, et on relance l’erreur
                $this->logger?->error('[MeteoClient] Échec appel OWM', [
                    'city' => $city,
                    'error' => $e->getMessage(),
                ]);
                // IMPORTANT : en cas d'exception, ne pas polluer le cache (laisser tomber)
                throw $e;
            }
        });
    }
}

