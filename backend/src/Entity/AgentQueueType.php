<?php

namespace App\Entity;

use App\Repository\AgentQueueTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AgentQueueTypeRepository::class)]
class AgentQueueType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'agentQueueTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: QueueType::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private QueueType $queueType;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: false, options: ['default' => 0.00])]
    #[Groups(['user:read'])]
    private float $efficiencyScore = 0.00;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

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

    public function getEfficiencyScore(): float
    {
        return $this->efficiencyScore;
    }

    public function setEfficiencyScore(float $efficiencyScore): static
    {
        $this->efficiencyScore = $efficiencyScore;

        return $this;
    }
} 