<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignUuid('school_group_id')->constrained('school_groups')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->constrained('shifts');

            $table->unsignedTinyInteger('grade');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();

            $table->string('status', 25)->default('ACTIVO'); // ACTIVO, CAMBIO_GRUPO, BAJA, CONCLUIDO

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['school_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
