<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Velora\Models\Team;

class TeamSwitched
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Team $team,
    ) {
    }
}
