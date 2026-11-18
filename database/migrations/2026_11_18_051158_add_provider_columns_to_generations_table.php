<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->unique();
            $table->text('provider_response')->nullable()->comment('The last provider response body, raw, for audit');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn(['provider_id', 'provider_response']);
        });
    }
};
