<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('specification')->nullable();
            $table->string('size_label', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('material_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('property', 50);
            $table->string('unit', 20)->nullable();
            $table->decimal('min', 18, 6)->nullable();
            $table->decimal('max', 18, 6)->nullable();
            $table->decimal('size_from', 12, 3)->nullable();
            $table->decimal('size_to', 12, 3)->nullable();
            $table->timestamps();

            $table->index(['material_id', 'property']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_limits');
        Schema::dropIfExists('materials');
    }
};
