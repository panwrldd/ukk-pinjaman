<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->enum('condition', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->unsignedInteger('qty_total')->default(1);
            $table->unsignedInteger('qty_available')->default(1);
            $table->unsignedInteger('qty_damaged')->default(0);
            $table->string('location', 100)->nullable();
            $table->enum('status', ['tersedia', 'tidak_tersedia', 'maintenance'])->default('tersedia');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index(['category_id', 'status']);
            $table->index('condition');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
