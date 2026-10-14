<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('student_id_number', 50)->nullable();
            $table->string('curp', 18)->nullable();
            $table->date('birth_date')->nullable();
            $table->char('gender', 1)->nullable(); // M, F, X

            $table->string('status', 20)->default('ACTIVO'); // ACTIVO, BAJA, EGRESADO

            $table->timestamps();

            $table->unique(['school_id', 'student_id_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
