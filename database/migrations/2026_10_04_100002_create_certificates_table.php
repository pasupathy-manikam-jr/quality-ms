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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('number', 100);
            $table->string('type', 20);
            $table->date('issued_on');
            $table->string('po_number', 100)->nullable();
            $table->string('third_party_inspector')->nullable();
            $table->string('status', 20)->default('received')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('file_sha256', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'number']);
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->string('lot_number', 100)->index();
            $table->decimal('size', 12, 3)->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->string('quantity_unit', 20)->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamps();

            $table->unique(['certificate_id', 'lot_number']);
        });

        Schema::create('lot_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained()->cascadeOnDelete();
            $table->string('property', 50);
            $table->decimal('value', 18, 6);
            $table->timestamps();

            $table->unique(['lot_id', 'property']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_results');
        Schema::dropIfExists('lots');
        Schema::dropIfExists('certificates');
    }
};
