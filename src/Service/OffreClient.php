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
    ) {}

    public function rechercher(float $latitude, float $longitude, int $rayon, string $romes): array
    {
        $response = $this->httpClient->request('GET', self::URL, [
            'auth_bearer' => $this->token,
            'query' => [
                '48.8566' => $latitude,
                '2.3522' => $longitude,
                '30' => $rayon,
                'M1805' => $romes,
            ],
        ]);

        return $response->toArray();
    }
}