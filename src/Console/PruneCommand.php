<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Webmarka\CookieConsent\Models\ConsentLog;

#[AsCommand(name: 'cookie-consent:prune')]
final class PruneCommand extends Command
{
    protected $signature = 'cookie-consent:prune {--days= : Delete records older than this many days (default: logging.retention_days)}';

    protected $description = 'Delete consent records older than the configured retention period';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('cookie-consent.logging.retention_days', 1095);

        if (! is_numeric($days) || (int) $days < 1) {
            $this->components->error('The retention period must be a positive number of days.');

            return self::FAILURE;
        }

        $deleted = ConsentLog::query()
            ->where('created_at', '<', now()->subDays((int) $days))
            ->delete();

        $this->components->info("Deleted {$deleted} consent record(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
