<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each generation keeps the markup its hold used (ADR 0045). Every generation so far used 25%.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->unsignedSmallInteger('markup_percent')->nullable();
        });
        DB::table('generations')->update(['markup_percent' => 25]);
        Schema::table('generations', function (Blueprint $table) {
            $table->unsignedSmallInteger('markup_percent')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn('markup_percent');
        });
    }
};
