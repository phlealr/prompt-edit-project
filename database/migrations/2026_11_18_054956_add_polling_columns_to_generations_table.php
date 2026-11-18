<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->timestamp('polled_at')->nullable()->comment('When we last asked the provider (ADR 0027)');
            $table->timestamp('submission_gave_up_at')->nullable()
                ->comment('The submission job ran out of attempts on a generation that may exist (ADR 0042)');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn(['polled_at', 'submission_gave_up_at']);
        });
    }
};
