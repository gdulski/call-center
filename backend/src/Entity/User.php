<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Enum\UserRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AgentQueueType::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $agentQueueTypes;

    #[ORM\OneToMany(mappedBy: 'agent', targetEntity: AgentAvailability::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $agentAvailabilities;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ScheduleShiftAssignment::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $scheduleShiftAssignments;

    public function __construct()
    {
        $this->agentQueueTypes = new ArrayCollection();
        $this->agentAvailabilities = new ArrayCollection();
        $this->scheduleShiftAssignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection<int, AgentQueueType>
     */
    public function getAgentQueueTypes(): Collection
    {
        return $this->agentQueueTypes;
    }

    public function addAgentQueueType(AgentQueueType $agentQueueType): static
    {
        if (!$this->agentQueueTypes->contains($agentQueueType)) {
            $this->agentQueueTypes->add($agentQueueType);
            $agentQueueType->setUser($this);
        }

        return $this;
    }

    public function removeAgentQueueType(AgentQueueType $agentQueueType): static
    {
        $this->agentQueueTypes->removeElement($agentQueueType);

        return $this;
    }

    /**
     * @return Collection<int, AgentAvailability>
     */
    public function getAgentAvailabilities(): Collection
    {
        return $this->agentAvailabilities;
    }

    public function addAgentAvailability(AgentAvailability $agentAvailability): static
    {
        if (!$this->agentAvailabilities->contains($agentAvailability)) {
            $this->agentAvailabilities->add($agentAvailability);
            $agentAvailability->setAgent($this);
        }

        return $this;
    }

    public function removeAgentAvailability(AgentAvailability $agentAvailability): static
    {
        $this->agentAvailabilities->removeElement($agentAvailability);

        return $this;
    }

    /**
     * @return Collection<int, ScheduleShiftAssignment>
     */
    public function getScheduleShiftAssignments(): Collection
    {
        return $this->scheduleShiftAssignments;
    }

    public function addScheduleShiftAssignment(ScheduleShiftAssignment $scheduleShiftAssignment): static
    {
        if (!$this->scheduleShiftAssignments->contains($scheduleShiftAssignment)) {
            $this->scheduleShiftAssignments->add($scheduleShiftAssignment);
            $scheduleShiftAssignment->setUser($this);
        }

        return $this;
    }

    public function removeScheduleShiftAssignment(ScheduleShiftAssignment $scheduleShiftAssignment): static
    {
        $this->scheduleShiftAssignments->removeElement($scheduleShiftAssignment);

        return $this;
    }
} 