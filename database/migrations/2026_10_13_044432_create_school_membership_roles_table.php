<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_membership_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('school_membership_id')
                ->constrained('school_memberships')
                ->cascadeOnDelete();

            $table->string('role', 30);
            $table->timestamps();

            $table->unique(['school_membership_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_membership_roles');
    }
};
