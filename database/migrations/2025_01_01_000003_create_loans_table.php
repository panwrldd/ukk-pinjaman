<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->date('loan_date');
            $table->date('due_date');
            $table->text('purpose');
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'borrowed',
                'overdue',
                'returned',
                'cancelled',
            ])->default('pending');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('handed_over_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->dateTime('handed_over_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index('loan_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
