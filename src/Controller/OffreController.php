<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\OffreRepository;
use App\Service\OffreImporter;
use App\Service\GeoClient;
use Symfony\Component\HttpFoundation\Request;

final class OffreController extends AbstractController
{
    // Les métiers proposés dans le formulaire (code ROME => nom)
    private const METIERS = [
        'M1805' => 'Développement informatique',
        'M1801' => 'Administration de systèmes',
        'M1810' => 'Production et exploitation informatique',
    ];

    #[Route('/', name: 'app_offre')]
    public function index(OffreRepository $repository): Response
    {
        return $this->render('offre/index.html.twig', [
            'offres' => $repository->findBy([], ['dateAjout' => 'DESC']),
            'metiers' => self::METIERS,
        ]);
    }

    #[Route('/offres/importer', name: 'app_offre_importer', methods: ['POST'])]
    public function importer(Request $request, GeoClient $geo, OffreImporter $importer): Response
    {
        // 1. On récupère ce que l'utilisateur a rempli
        $ville = trim((string) $request->request->get('ville', ''));
        $rayon = $request->request->getInt('rayon', 30);
        $metier = (string) $request->request->get('metier', '');

        // 2. On vérifie que les valeurs sont autorisées
        if (!array_key_exists($metier, self::METIERS) || $rayon < 1 || $rayon > 200) {
            $this->addFlash('error', 'Recherche invalide.');
            return $this->redirectToRoute('app_offre');
        }

        // 3. On traduit la ville en coordonnées
        $lieu = $geo->trouverVille($ville);
        if ($lieu === null) {
            $this->addFlash('error', "Ville « $ville » introuvable.");
            return $this->redirectToRoute('app_offre');
        }

        // 4. On lance l'import
        $nb = $importer->importer($lieu['latitude'], $lieu['longitude'], $rayon, $metier);
        $this->addFlash('success', "$nb nouvelle(s) offre(s) autour de {$lieu['nom']}");

        return $this->redirectToRoute('app_offre');
    }
}