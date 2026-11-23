<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                ->constrained('loans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('equipment_id')
                ->constrained('equipments')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedInteger('qty');
            $table->enum('condition_before', ['baik', 'rusak_ringan'])->default('baik');
            $table->timestamps();

            $table->unique(['loan_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_details');
    }
};
