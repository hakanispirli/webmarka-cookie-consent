<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;
use Webmarka\CookieConsent\Models\ConsentLog;

#[AsCommand(name: 'cookie-consent:export')]
final class ExportCommand extends Command
{
    protected $signature = 'cookie-consent:export
        {path? : CSV file to write (defaults to standard output)}
        {--from= : Only records created on or after this date}
        {--to= : Only records created on or before this date}
        {--consent-id= : Only records of one consent id (the id stored in the visitor\'s cookie)}';

    protected $description = 'Export consent records as CSV (proof of consent)';

    public function handle(): int
    {
        try {
            $from = $this->dateOption('from')?->startOfDay();
            $to = $this->dateOption('to')?->endOfDay();
        } catch (Throwable) {
            $this->components->error('Invalid date given for --from or --to.');

            return self::FAILURE;
        }

        $path = $this->argument('path');
        $handle = fopen(is_string($path) && $path !== '' ? $path : 'php://output', 'wb');

        if ($handle === false) {
            $this->components->error('Unable to open the output file.');

            return self::FAILURE;
        }

        fputcsv($handle, [
            'id', 'created_at', 'consent_id', 'action', 'policy_version', 'categories',
            'consent_mode', 'locale', 'url', 'ip_address', 'user_agent', 'user_id',
        ], escape: '');

        $consentId = $this->option('consent-id');

        ConsentLog::query()
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->when(is_string($consentId) && $consentId !== '', fn ($query) => $query->where('consent_id', $consentId))
            ->orderBy('id')
            ->lazyById(500)
            ->each(static function (ConsentLog $log) use ($handle): void {
                fputcsv($handle, array_map(self::cell(...), [
                    $log->id,
                    $log->created_at->toIso8601String(),
                    $log->consent_id,
                    $log->action->value,
                    $log->policy_version,
                    (string) json_encode($log->categories),
                    (string) json_encode($log->consent_mode),
                    $log->locale,
                    $log->url,
                    $log->ip_address,
                    $log->user_agent,
                    $log->user_id,
                ]), escape: '');
            });

        fclose($handle);

        if (is_string($path) && $path !== '') {
            $this->components->info("Consent records exported to [{$path}].");
        }

        return self::SUCCESS;
    }

    /**
     * Prevents CSV formula injection (values such as URLs and user agents
     * come from visitors).
     */
    private static function cell(int|string|null $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    private function dateOption(string $name): ?Carbon
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}
