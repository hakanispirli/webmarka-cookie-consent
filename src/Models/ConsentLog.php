<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Webmarka\CookieConsent\Enums\ConsentAction;

/**
 * @property int $id
 * @property string $consent_id
 * @property ConsentAction $action
 * @property array<string, bool> $categories
 * @property array<string, string> $consent_mode
 * @property string $policy_version
 * @property string|null $locale
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $user_id
 * @property Carbon $created_at
 */
class ConsentLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function getConnectionName(): ?string
    {
        $connection = config('cookie-consent.logging.connection');

        return is_string($connection) && $connection !== '' ? $connection : parent::getConnectionName();
    }

    public function getTable(): string
    {
        $table = config('cookie-consent.logging.table');

        return is_string($table) && $table !== '' ? $table : 'consent_logs';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ConsentAction::class,
            'categories' => 'array',
            'consent_mode' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
