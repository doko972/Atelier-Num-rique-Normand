<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres administrables du site et horaires d'appel (codex §35).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            // string | text | boolean | integer | json
            $table->string('type', 20)->default('string');
            $table->string('group', 60)->default('general');
            $table->string('label', 200);
            $table->string('help', 400)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['group', 'position']);
        });

        Schema::create('opening_hours', function (Blueprint $table): void {
            $table->id();
            // 1 = lundi … 7 = dimanche (ISO-8601)
            $table->unsignedTinyInteger('weekday');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('note', 200)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['weekday', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
        Schema::dropIfExists('site_settings');
    }
};
