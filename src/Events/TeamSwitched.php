<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;

class TeamSwitched implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $team,
    ) {}
}
