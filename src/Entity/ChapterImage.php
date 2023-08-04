<?php

namespace App\Entity;

use App\Repository\ChapterImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: ChapterImageRepository::class)]
class ChapterImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $imagePath = null;

    #[ORM\ManyToOne(targetEntity: Chapter::class, inversedBy: 'chapterImages')]
    #[ORM\JoinColumn(nullable: false)]
    private $Chapter;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getChapter(): ?Chapter
    {
        return $this->Chapter;
    }

    public function setChapter(?Chapter $Chapter): self
    {
        $this->Chapter = $Chapter;

        return $this;
    }
}
