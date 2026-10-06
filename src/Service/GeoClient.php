<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoClient
{
    private const URL = 'https://data.geopf.fr/geocodage/search';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /** Transforme un nom de ville en coordonnées GPS. Renvoie null si la ville est introuvable. */
    public function trouverVille(string $ville): ?array
    {
        if (mb_strlen($ville) < 3) {
            return null; // l'API exige au moins 3 caractères
        }

        $data = $this->httpClient->request('GET', self::URL, [
            'query' => [
                'q' => $ville,
                'type' => 'municipality', // on cherche une commune, pas une rue
                'limit' => 1,             // on veut seulement le meilleur résultat
            ],
        ])->toArray();

        $resultat = $data['features'][0] ?? null;
        if ($resultat === null) {
            return null;
        }

        // ⚠️ Piège : l'API renvoie [longitude, latitude], dans cet ordre-là !
        [$longitude, $latitude] = $resultat['geometry']['coordinates'];

        return [
            'nom' => $resultat['properties']['city'],
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }
}