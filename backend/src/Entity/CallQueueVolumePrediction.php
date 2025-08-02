<?php

namespace App\Entity;

use App\Repository\CallQueueVolumePredictionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallQueueVolumePredictionRepository::class)]
class CallQueueVolumePrediction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $hour;

    #[ORM\Column]
    private int $expectedCalls;

    #[ORM\ManyToOne(targetEntity: QueueType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private QueueType $queueType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHour(): \DateTimeInterface
    {
        return $this->hour;
    }

    public function setHour(\DateTimeInterface $hour): static
    {
        $this->hour = $hour;

        return $this;
    }

    public function getExpectedCalls(): int
    {
        return $this->expectedCalls;
    }

    public function setExpectedCalls(int $expectedCalls): static
    {
        $this->expectedCalls = $expectedCalls;

        return $this;
    }

    public function getQueueType(): QueueType
    {
        return $this->queueType;
    }

    public function setQueueType(QueueType $queueType): static
    {
        $this->queueType = $queueType;

        return $this;
    }
}