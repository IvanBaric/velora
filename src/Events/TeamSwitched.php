<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamSwitched
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $team,
    ) {}
}
