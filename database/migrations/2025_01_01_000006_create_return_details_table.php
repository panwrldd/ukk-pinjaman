<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')
                ->constrained('returns')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('loan_detail_id')
                ->unique()
                ->constrained('loan_details')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('equipment_id')
                ->constrained('equipments')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedInteger('qty_returned');
            $table->unsignedInteger('qty_damaged')->default(0);
            $table->enum('condition_after', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->text('damage_notes')->nullable();
            $table->timestamps();

            $table->index(['return_id', 'equipment_id']);
            $table->index('condition_after');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_details');
    }
};
