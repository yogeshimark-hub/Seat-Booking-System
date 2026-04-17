<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
          $table->id();
          $table->foreignId('event_id')->constrained()->onDelete('cascade');
          $table->string('seat_row');
          $table->integer('seat_column');
          $table->enum('status', ['available', 'locked', 'booked'])->default('available');
          $table->foreignId('locked_by')->nullable()->constrained('users')->onDelete('set null');
          $table->timestamp('lock_expires_at')->nullable();
          $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
