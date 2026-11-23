<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        $connection = config('cookie-consent.logging.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    public function up(): void
    {
        Schema::create(config('cookie-consent.logging.table', 'consent_logs'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('consent_id')->index();
            $table->string('action', 32);
            $table->json('categories');
            $table->json('consent_mode');
            $table->string('policy_version', 64);
            $table->string('locale', 16)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('user_id', 64)->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('cookie-consent.logging.table', 'consent_logs'));
    }
};
