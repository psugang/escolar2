<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_justifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->constrained('attendances')->cascadeOnDelete();

            $table->string('document_path', 255)->nullable();
            $table->string('document_name', 255)->nullable();
            $table->text('reason');
            $table->foreignUuid('registered_by_user_id')->constrained('users');

            $table->timestamps();

            $table->unique('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_justifications');
    }
};
