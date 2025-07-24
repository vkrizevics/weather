<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\StationRepository")]
class Station
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer", unique: true)]
    private int $_id;

    #[ORM\Column(type: "string", length: 16)]
    private string $stationId;

    #[ORM\Column(type: "string", length: 64)]
    private string $name;

    #[ORM\Column(type: "string", length: 10, nullable: true)]
    private ?string $wmoId = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $beginDate = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $latitude = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $longitude = null;

    #[ORM\Column(type: "decimal", precision: 8, scale: 2, nullable: true)]
    private ?string $gauss1 = null;

    #[ORM\Column(type: "decimal", precision: 8, scale: 2, nullable: true)]
    private ?string $gauss2 = null;

    #[ORM\Column(type: "decimal", precision: 8, scale: 6, nullable: true)]
    private ?string $geogr1 = null;

    #[ORM\Column(type: "decimal", precision: 8, scale: 6, nullable: true)]
    private ?string $geogr2 = null;

    #[ORM\Column(type: "decimal", precision: 5, scale: 2, nullable: true)]
    private ?string $elevation = null;

    #[ORM\Column(type: "decimal", precision: 5, scale: 2, nullable: true)]
    private ?string $elevationPressure = null;

    // Getters and setters for all properties

    public function getId(): ?int
    {
        return $this->id;
    }

    public function get_Id(): ?int
    {
        return $this->_id;
    }

    public function set_Id(int $_id): self
    {
        $this->_id = $_id;

        return $this;
    }

    public function getStationId(): ?string
    {
        return $this->stationId;
    }

    public function setStationId(string $stationId): self
    {
        $this->stationId = $stationId;
        
        return $this;
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

    public function getWmoId(): ?string
    {
        return $this->wmoId;
    }

    public function setWmoId(?string $wmoId): self
    {
        $this->wmoId = $wmoId;
        
        return $this;
    }

    public function getBeginDate(): ?\DateTimeInterface
    {
        return $this->beginDate;
    }

    public function setBeginDate(?\DateTimeInterface $beginDate): self
    {
        $this->beginDate = $beginDate;
        
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        
        return $this;
    }

    public function getLatitude(): ?int
    {
        return $this->latitude;
    }

    public function setLatitude(?int $latitude): self
    {
        $this->latitude = $latitude;
        
        return $this;
    }

    public function getLongitude(): ?int
    {
        return $this->longitude;
    }

    public function setLongitude(?int $longitude): self
    {
        $this->longitude = $longitude;
        
        return $this;
    }

    public function getGauss1(): ?string
    {
        return $this->gauss1;
    }

    public function setGauss1(?string $gauss1): self
    {
        $this->gauss1 = $gauss1;
        
        return $this;
    }

    public function getGauss2(): ?string
    {
        return $this->gauss2;
    }

    public function setGauss2(?string $gauss2): self
    {
        $this->gauss2 = $gauss2;
        
        return $this;
    }

    public function getGeogr1(): ?string
    {
        return $this->geogr1;
    }

    public function setGeogr1(?string $geogr1): self
    {
        $this->geogr1 = $geogr1;
        
        return $this;
    }

    public function getGeogr2(): ?string
    {
        return $this->geogr2;
    }

    public function setGeogr2(?string $geogr2): self
    {
        $this->geogr2 = $geogr2;
        
        return $this;
    }

    public function getElevation(): ?string
    {
        return $this->elevation;
    }

    public function setElevation(?string $elevation): self
    {
        $this->elevation = $elevation;
        
        return $this;
    }

    public function getElevationPressure(): ?string
    {
        return $this->elevationPressure;
    }

    public function setElevationPressure(?string $elevationPressure): self
    {
        $this->elevationPressure = $elevationPressure;
        
        return $this;
    }
}
