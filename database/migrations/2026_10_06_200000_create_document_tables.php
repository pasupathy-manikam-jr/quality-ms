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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('title');
            $table->string('type', 30);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('review_interval_months')->default(12);
            $table->date('next_review_on')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('document_iso_clause', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iso_clause_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'iso_clause_id']);
        });

        Schema::create('document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('revision', 10);
            $table->text('change_summary')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('capa_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('file_sha256', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'revision']);
        });

        Schema::create('document_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['document_revision_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_acknowledgements');
        Schema::dropIfExists('document_revisions');
        Schema::dropIfExists('document_iso_clause');
        Schema::dropIfExists('documents');
    }
};
