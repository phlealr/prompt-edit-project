<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each generation and webhook event records its provider (ADR 0044). Everything so far went to
     * Pixelforge, the only provider until now.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->string('provider')->nullable();
        });
        DB::table('generations')->update(['provider' => 'pixelforge']);
        Schema::table('generations', function (Blueprint $table) {
            $table->string('provider')->nullable(false)->change();
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('provider')->nullable();
        });
        DB::table('webhook_events')->update(['provider' => 'pixelforge']);
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('provider')->nullable(false)->change();
            // Two providers can use the same event id.
            $table->dropUnique(['provider_event_id']);
            $table->unique(['provider', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_event_id']);
            $table->unique('provider_event_id');
            $table->dropColumn('provider');
        });

        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
