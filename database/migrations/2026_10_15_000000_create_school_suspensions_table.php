<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_suspensions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('school_year_id')->constrained('school_years')->cascadeOnDelete();

            $table->date('date');
            $table->string('reason', 255);

            $table->timestamps();

            $table->unique(['school_year_id', 'date']);
            $table->index(['school_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_suspensions');
    }
};
