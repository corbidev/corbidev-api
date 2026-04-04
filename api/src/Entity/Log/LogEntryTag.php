<?php

namespace App\Entity\Log;

use App\Repository\Log\LogEntryTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogEntryTagRepository::class)]
#[ORM\Table(name: 'log_entry_tag')]
class LogEntryTag
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'entryTags')]
    #[ORM\JoinColumn(name: 'log_entry_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?LogEntry $logEntry = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'entryTags')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?LogTag $tag = null;

    public function getLogEntry(): ?LogEntry
    {
        return $this->logEntry;
    }

    public function setLogEntry(?LogEntry $logEntry): static
    {
        $this->logEntry = $logEntry;

        return $this;
    }

    public function getTag(): ?LogTag
    {
        return $this->tag;
    }

    public function setTag(?LogTag $tag): static
    {
        $this->tag = $tag;

        return $this;
    }
}
