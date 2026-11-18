<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->bigInteger('provider_cost')->nullable()->comment('Nanodollars, from the provider\'s usage and rates');
            $table->text('image_url')->nullable();
            $table->timestamp('image_url_expires_at')->nullable();
            $table->timestamp('started_at')->nullable()->comment('The provider\'s');
            $table->timestamp('completed_at')->nullable()->comment('The provider\'s; the usage day it is billed on');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn(['provider_cost', 'image_url', 'image_url_expires_at', 'started_at', 'completed_at']);
        });
    }
};
