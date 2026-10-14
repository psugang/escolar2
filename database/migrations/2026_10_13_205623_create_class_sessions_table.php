<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_class_id')->constrained('school_classes')->cascadeOnDelete();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            $table->foreignUuid('original_teacher_id')->constrained('teachers');
            $table->foreignUuid('substitute_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();

            $table->string('status', 25)->default('PROGRAMADA'); // PROGRAMADA, COMPLETADA, CANCELADA
            $table->string('cancellation_reason', 255)->nullable();

            $table->timestamps();

            $table->unique(['school_class_id', 'date', 'start_time']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
