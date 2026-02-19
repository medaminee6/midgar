<?php

namespace App\Entity;

use App\Repository\StockPredictionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockPredictionRepository::class)]
#[ORM\Table(name: 'stock_prediction')]
class StockPrediction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Produit $product;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $predicted_stockout_date = null;

    #[ORM\Column(type: 'integer')]
    private int $days_until_stockout;

    #[ORM\Column(type: 'float')]
    private float $daily_consumption;

    #[ORM\Column(type: 'integer')]
    private int $current_stock;

    #[ORM\Column(type: 'float')]
    private float $confidence;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Produit
    {
        return $this->product;
    }

    public function setProduct(Produit $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getPredictedStockoutDate(): ?\DateTimeImmutable
    {
        return $this->predicted_stockout_date;
    }

    public function setPredictedStockoutDate(?\DateTimeImmutable $date): self
    {
        $this->predicted_stockout_date = $date;
        return $this;
    }

    public function getDaysUntilStockout(): int
    {
        return $this->days_until_stockout;
    }

    public function setDaysUntilStockout(int $days): self
    {
        $this->days_until_stockout = $days;
        return $this;
    }

    public function getDailyConsumption(): float
    {
        return $this->daily_consumption;
    }

    public function setDailyConsumption(float $consumption): self
    {
        $this->daily_consumption = $consumption;
        return $this;
    }

    public function getCurrentStock(): int
    {
        return $this->current_stock;
    }

    public function setCurrentStock(int $stock): self
    {
        $this->current_stock = $stock;
        return $this;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function setConfidence(float $confidence): self
    {
        $this->confidence = $confidence;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $date): self
    {
        $this->created_at = $date;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}
