<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_class_id')->constrained('school_classes')->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week'); // 1 = Lunes, 5 = Viernes
            $table->time('start_time');
            $table->time('end_time');

            $table->date('effective_from');
            $table->date('effective_until')->nullable();

            $table->timestamps();

            $table->index(['school_class_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
