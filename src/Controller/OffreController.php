<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\OffreRepository;
use App\Service\OffreImporter;

final class OffreController extends AbstractController
{
    #[Route('/', name: 'app_offre')]
    public function index(OffreRepository $repository): Response
    {
        return $this->render('offre/index.html.twig', [
            'offres' => $repository->findBy([], ['dateAjout' => 'DESC']), // les plus récentes en premier
        ]);
    }

    #[Route('/offres/importer', name: 'app_offre_importer', methods: ['POST'])]
    public function importer(OffreImporter $importer): Response
    {
        $nb = $importer->importer(48.8566, 2.3522, 30, 'M1805');
        $this->addFlash('success', "$nb nouvelle(s) offre(s) ajoutée(s)");

        return $this->redirectToRoute('app_offre');
    }
}
