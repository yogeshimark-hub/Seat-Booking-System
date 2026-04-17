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
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->enum('booker_type', ['user', 'admin'])->default('user')->after('user_id');
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->enum('locked_by_type', ['user', 'admin'])->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booker_type');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->dropColumn('locked_by_type');
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
