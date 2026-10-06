<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Repository\OffreRepository;
use App\Service\GeoClient;
use App\Service\OffreImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class OffreController extends AbstractController
{
    // Les métiers proposés dans le formulaire (code ROME => nom)
    private const METIERS = [
        'M1805' => 'Développement informatique',
        'M1801' => 'Administration de systèmes',
        'M1810' => 'Production et exploitation informatique',
    ];

    #[Route('/', name: 'app_offre')]
    public function index(Request $request, OffreRepository $repository): Response
    {
        // Quel onglet afficher ? Par défaut : les nouvelles
        $filtre = $request->query->get('statut', 'nouvelle');
        if (!array_key_exists($filtre, Offre::STATUTS)) {
            $filtre = 'nouvelle';
        }

        return $this->render('offre/index.html.twig', [
            'offres' => $repository->findBy(['statut' => $filtre], ['dateAjout' => 'DESC']),
            'metiers' => self::METIERS,
            'statuts' => Offre::STATUTS,
            'filtre' => $filtre,
            'compteurs' => $repository->compterParStatut(),
            'nbARelancer' => $repository->compterARelancer(),
        ]);
    }

    #[Route('/offres/importer', name: 'app_offre_importer', methods: ['POST'])]
    #[IsCsrfTokenValid('importer')]
    public function importer(Request $request, GeoClient $geo, OffreImporter $importer): Response
    {
        // 1. On récupère ce que l'utilisateur a rempli
        $ville = trim((string) $request->request->get('ville', ''));
        $rayon = $request->request->getInt('rayon', 30);
        $metier = (string) $request->request->get('metier', '');
        $uniquementLba = $request->request->has('uniquement_lba'); // case cochée ou non

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
        $nb = $importer->importer($lieu['latitude'], $lieu['longitude'], $rayon, [$metier], $uniquementLba);
        $this->addFlash('success', "$nb nouvelle(s) offre(s) autour de {$lieu['nom']}");

        return $this->redirectToRoute('app_offre');
    }

    #[Route('/offres/{id}/statut', name: 'app_offre_statut', methods: ['POST'])]
    #[IsCsrfTokenValid('statut')]
    public function changerStatut(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        $statut = (string) $request->request->get('statut');

        if (array_key_exists($statut, Offre::STATUTS)) {
            $offre->setStatut($statut);
            $em->flush(); // pas besoin de persist : l'offre est déjà en base
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->retourPagePrecedente($request);
    }

    #[Route('/offres/{id}/relance', name: 'app_offre_relance', methods: ['POST'])]
    #[IsCsrfTokenValid('relance')]
    public function relancer(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        $offre->marquerRelancee();
        $em->flush();

        return $this->retourPagePrecedente($request);
    }

    /** Revient sur la page d'où on vient (même onglet), ou sur l'accueil */
    private function retourPagePrecedente(Request $request): Response
    {
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_offre'));
    }
}