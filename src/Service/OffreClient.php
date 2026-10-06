<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OffreClient
{
    private const URL = 'https://api.apprentissage.beta.gouv.fr/api/job/v1/search';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'LBA_API_TOKEN')] // va chercher la clé dans .env.local
        private string $token,
    ) {
    }

    public function rechercher(float $latitude, float $longitude, int $rayon, ?array $romes, array $sourcesExclues = []): array
    {
        $parametres = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $rayon,
        ];

        // Pas de codes métier = pas de filtre = tous les secteurs
        if ($romes) {
            $parametres['romes'] = implode(',', $romes);
        }

        $query = http_build_query($parametres);

        // L'API veut le paramètre répété : &partners_to_exclude=A&partners_to_exclude=B
        foreach ($sourcesExclues as $source) {
            $query .= '&partners_to_exclude=' . rawurlencode($source);
        }

        $response = $this->httpClient->request('GET', self::URL . '?' . $query, [
            'auth_bearer' => $this->token,
        ]);

        return $response->toArray();
    }
}