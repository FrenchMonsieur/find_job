<?php

namespace App\Service;

use App\Entity\Offre;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;

class OffreImporter
{
    public function __construct(
        private OffreClient $client,
        private OffreRepository $repository,
        private EntityManagerInterface $em,
    ) {
    }

    /** Va chercher les offres, enregistre les nouvelles et renvoie combien ont été ajoutées */
    public function importer(float $latitude, float $longitude, int $rayon, string $romes): int
    {
        $resultats = $this->client->rechercher($latitude, $longitude, $rayon, $romes);
        $nouvelles = 0;
        $vusDansCetImport = [];

        foreach ($resultats['jobs'] as $job) {
            $lbaId = $job['identifier']['id'] ?? null;

            // On saute l'offre si : pas d'identifiant, déjà vue dans ce lot, ou déjà en base
            if (
                $lbaId === null
                || isset($vusDansCetImport[$lbaId])
                || $this->repository->findOneBy(['lbaId' => $lbaId])
            ) {
                continue;
            }
            $vusDansCetImport[$lbaId] = true;

            $offre = new Offre();
            $offre->setLbaId($lbaId);
            $offre->setTitre(mb_substr($this->nettoyer($job['offer']['title']), 0, 255));
            $offre->setEntreprise($this->nettoyer($job['workplace']['name'] ?? $job['workplace']['legal_name'] ?? null));
            $offre->setAdresse($this->nettoyer($job['workplace']['location']['address'] ?? null));
            $offre->setUrl($job['apply']['url'] ?? null);

            $this->em->persist($offre); // "prépare" l'enregistrement
            $nouvelles++;
        }

        $this->em->flush(); // enregistre tout d'un coup dans la base
        return $nouvelles;
    }
    /** Transforme "&amp;" en "&", "&#039;" en "'", etc. */
    private function nettoyer(?string $texte): ?string
    {
        return $texte === null ? null : html_entity_decode($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}