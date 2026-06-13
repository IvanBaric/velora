<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Console;

use Illuminate\Console\Command;
use IvanBaric\Velora\Support\SystemAccessSynchronizer;

final class SyncVeloraCommand extends Command
{
    protected $signature = 'velora:sync
        {--force : Overwrite existing permissions and roles from configuration, except superadmin unless explicitly enabled.}';

    protected $description = 'Sync Velora permissions and system roles from configuration.';

    public function handle(SystemAccessSynchronizer $synchronizer): int
    {
        $result = $synchronizer->sync(
            overwriteExisting: (bool) $this->option('force') || (bool) config('velora.sync.overwrite_existing', false),
        );

        if (! $result->ok) {
            $this->components->error($result->message);

            return self::FAILURE;
        }

        $this->components->info($result->message);

        $this->line("created: {$result->data['created']}");
        $this->line("updated: {$result->data['updated']}");
        $this->line("skipped: {$result->data['skipped']}");

        return self::SUCCESS;
    }
}
