
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('school_year_id')
                ->constrained('school_years');

            $table->foreignUuid('shift_id')
                ->constrained('shifts');

            $table->unsignedTinyInteger('grade');
            $table->char('section', 1);

            $table->timestamps();

            $table->unique([
                'school_year_id',
                'shift_id',
                'grade',
                'section',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_groups');
    }
};
