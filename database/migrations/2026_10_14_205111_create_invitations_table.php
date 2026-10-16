<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('inviter_id')->constrained('users')->cascadeOnDelete();

            $table->string('email', 150);
            $table->string('role', 50)->default('PROFESOR');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('employee_number', 50)->nullable();
            $table->string('phone', 30)->nullable();

            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('PENDING'); // PENDING, ACCEPTED, EXPIRED, REVOKED
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
