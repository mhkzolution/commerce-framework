<?php

declare(strict_types=1);

namespace Commerce\Iam\Team;

use Commerce\Iam\Models\Team;

final class TeamContext
{
    private ?Team $team = null;

    public function set(?Team $team): void
    {
        $this->team = $team;
    }

    public function team(): ?Team
    {
        return $this->team;
    }

    public function id(): ?int
    {
        return $this->team?->id;
    }

    public function uuid(): ?string
    {
        return $this->team?->uuid;
    }

    public function isEnabled(): bool
    {
        return (bool) config('iam.teams.enabled', false);
    }

    public function clear(): void
    {
        $this->team = null;
    }
}
