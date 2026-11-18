<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique()->comment('Sent to the provider as the idempotency key');
            $table->foreignId('user_id')->constrained();
            $table->text('prompt');
            $table->string('size');
            $table->string('quality');
            $table->json('reference_images');
            $table->string('status');

            // The rates the hold was computed with, in nanodollars per 1M tokens (ADR 0026, ADR 0035).
            $table->bigInteger('rate_input_text_tokens');
            $table->bigInteger('rate_input_image_tokens');
            $table->bigInteger('rate_output_tokens');
            $table->bigInteger('rate_reasoning_tokens');

            // The estimate shown before submitting, in nanodollars; its maximum is the hold.
            $table->bigInteger('estimate_minimum');
            $table->bigInteger('estimate_maximum');

            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
