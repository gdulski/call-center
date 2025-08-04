<?php

namespace App\Entity;

use App\Repository\QueueTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QueueTypeRepository::class)]
class QueueType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['queue_type:read', 'user:read', 'schedule:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['queue_type:read', 'user:read', 'schedule:read'])]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'queueType', targetEntity: CallQueueVolumePrediction::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $callQueueVolumePredictions;

    #[ORM\OneToMany(mappedBy: 'queueType', targetEntity: Schedule::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $schedules;

    public function __construct()
    {
        $this->callQueueVolumePredictions = new ArrayCollection();
        $this->schedules = new ArrayCollection();
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

    /**
     * @return Collection<int, CallQueueVolumePrediction>
     */
    public function getCallQueueVolumePredictions(): Collection
    {
        return $this->callQueueVolumePredictions;
    }

    public function addCallQueueVolumePrediction(CallQueueVolumePrediction $callQueueVolumePrediction): static
    {
        if (!$this->callQueueVolumePredictions->contains($callQueueVolumePrediction)) {
            $this->callQueueVolumePredictions->add($callQueueVolumePrediction);
            $callQueueVolumePrediction->setQueueType($this);
        }

        return $this;
    }

    public function removeCallQueueVolumePrediction(CallQueueVolumePrediction $callQueueVolumePrediction): static
    {
        $this->callQueueVolumePredictions->removeElement($callQueueVolumePrediction);

        return $this;
    }

    /**
     * @return Collection<int, Schedule>
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(Schedule $schedule): static
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules->add($schedule);
            $schedule->setQueueType($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): static
    {
        $this->schedules->removeElement($schedule);

        return $this;
    }
} 