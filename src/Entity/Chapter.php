<?php

namespace App\Entity;

use App\Repository\ChapterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: ChapterRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Chapter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    private ?int $viewCount = 0;

    #[ORM\PrePersist]
    public function prePersistView()
    {
        // Set giá trị mặc định cho viewCount khi tạo mới chapter
        $this->viewCount = 0;
    }
    #[ORM\OneToMany(targetEntity: ChapterImage::class, mappedBy: 'Chapter')]
    private $chapterImages;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'Chapter')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;


    #[ORM\PrePersist]
    public function prePersist()
    {
        if (!$this->getName()) {
            $product = $this->getProduct();
            if ($product) {
                $latestChapter = $product->getChapters()->last();
                $chapterNumber = 1;
                if ($latestChapter) {
                    $latestChapterName = $latestChapter->getName();
                    preg_match('/Chapter (\d+)/', $latestChapterName, $matches);
                    if (isset($matches[1])) {
                        $chapterNumber = (int)$matches[1] + 1;
                    }
                }
                $this->setName('Chapter ' . $chapterNumber);
            } else {
                $this->setName('Chapter 1');
            }
        }
    }

    public function __construct()
    {
        $this->chapterImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getViewCount(): ?int
    {
        return $this->viewCount;
    }

    public function incrementViewCount(): self
    {
        $this->viewCount++;

        return $this;
    }

    /**
     * @return Collection|ChapterImage[]
     */
    public function getChapterImages(): Collection
    {
        return $this->chapterImages;
    }

    public function addChapterImage(ChapterImage $chapterImage): self
    {
        if (!$this->chapterImages->contains($chapterImage)) {
            $this->chapterImages[] = $chapterImage;
            $chapterImage->setChapter($this);
        }

        return $this;
    }

    public function removeChapterImage(ChapterImage $chapterImage): self
    {
        if ($this->chapterImages->removeElement($chapterImage)) {
            // set the owning side to null (unless already changed)
            if ($chapterImage->getChapter() === $this) {
                $chapterImage->setChapter(null);
            }
        }

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }


}
