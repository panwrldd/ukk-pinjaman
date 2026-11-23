<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('loan_id')
                ->unique()
                ->constrained('loans')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('returned_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->date('return_date');
            $table->dateTime('verified_at')->nullable();
            $table->unsignedInteger('late_days')->default(0);
            $table->decimal('fine_per_day_snapshot', 12, 2)->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->enum('status', ['pending_verification', 'verified'])->default('pending_verification');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('return_date');
            $table->index('fine_paid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
