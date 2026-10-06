<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    public const STATUTS = [
        'nouvelle' => 'Nouvelle',
        'a_postuler' => 'À postuler',
        'envoyee' => 'Envoyée',
        'refusee' => 'Refusée',
        'ignoree' => 'Pas intéressé',
    ];
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $lbaId = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAjout = null;
    public function __construct()
    {
        $this->statut = 'nouvelle';
        $this->dateAjout = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLbaId(): ?string
    {
        return $this->lbaId;
    }

    public function setLbaId(string $lbaId): static
    {
        $this->lbaId = $lbaId;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }

    public function setEntreprise(?string $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (!array_key_exists($statut, self::STATUTS)) {
            throw new \InvalidArgumentException("Statut inconnu : $statut");
        }

        // Première fois qu'on la marque "envoyée" : on note la date automatiquement
        if ($statut === 'envoyee' && $this->dateCandidature === null) {
            $this->dateCandidature = new \DateTimeImmutable();
        }

        $this->statut = $statut;

        return $this;
    }

    public function getDateAjout(): ?\DateTimeImmutable
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeImmutable $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    /** Date du dernier contact : la relance si elle existe, sinon la candidature */
    public function getDernierContact(): ?\DateTimeImmutable
    {
        return $this->dateRelance ?? $this->dateCandidature;
    }

    /** Vrai si envoyée, sans réponse, et dernier contact il y a plus de 7 jours */
    public function estARelancer(): bool
    {
        $dernierContact = $this->getDernierContact();

        return $this->statut === 'envoyee'
            && $dernierContact !== null
            && $dernierContact < new \DateTimeImmutable('-7 days');
    }

    public function marquerRelancee(): static
    {
        $this->dateRelance = new \DateTimeImmutable();

        return $this;
    }
}
