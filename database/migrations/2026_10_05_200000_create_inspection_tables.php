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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number', 100);
            $table->string('revision', 20)->default('A');
            $table->string('name');
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['part_number', 'revision']);
        });

        Schema::create('inspection_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('stage', 20);
            $table->unsignedSmallInteger('revision')->default(1);
            $table->string('title');
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inspection_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('characteristic');
            $table->string('kind', 20);
            $table->string('method')->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('nominal', 18, 6)->nullable();
            $table->decimal('min', 18, 6)->nullable();
            $table->decimal('max', 18, 6)->nullable();
            $table->unsignedSmallInteger('sample_size')->default(1);
            $table->boolean('is_critical')->default(false);
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('inspection_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->date('inspected_on');
            $table->string('status', 20)->default('in-progress')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inspection_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_plan_item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sample');
            $table->foreignId('gauge_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('value', 18, 6)->nullable();
            $table->boolean('is_ok')->nullable();
            $table->boolean('passed');
            $table->timestamps();

            $table->unique(['inspection_id', 'inspection_plan_item_id', 'sample'], 'readings_unique_sample');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_readings');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('inspection_plan_items');
        Schema::dropIfExists('inspection_plans');
        Schema::dropIfExists('parts');
    }
};
