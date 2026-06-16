<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;
use IvanBaric\Velora\Models\TeamMembership;

final class MembershipRoleSynced implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $roleSlugs
     */
    public function __construct(
        public TeamMembership $membership,
        public array $roleSlugs,
    ) {}
}
