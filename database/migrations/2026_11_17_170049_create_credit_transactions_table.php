<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The ledger: every credit movement is a row, only ever inserted (ADR 0006).
     */
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('generation_id')->nullable();
            $table->string('type');
            $table->bigInteger('amount')->comment('Nanodollars; negative for holds and charges');
            $table->timestamp('created_at')->useCurrent();

            // One hold, one release and one charge per generation, at most.
            $table->unique(['generation_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
