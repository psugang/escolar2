<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('absence_notified_at')->nullable()->after('status');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->index(['school_id', 'school_year_id']);
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->index(['school_year_id', 'school_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex(['school_year_id', 'school_group_id', 'status']);
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'school_year_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('absence_notified_at');
        });
    }
};
