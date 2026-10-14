<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->constrained('attendances')->cascadeOnDelete();

            $table->string('old_status', 20);
            $table->string('new_status', 20);
            $table->foreignUuid('changed_by_user_id')->constrained('users');
            $table->string('reason', 255)->nullable();

            $table->timestamps();

            $table->index('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
