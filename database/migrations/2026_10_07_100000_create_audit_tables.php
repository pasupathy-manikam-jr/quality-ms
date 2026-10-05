<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Internal audits (ISO 9001 §9.2). Named quality_audits so they never collide with audit_logs.
     */
    public function up(): void
    {
        Schema::create('quality_audits', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('title');
            $table->text('scope')->nullable();
            $table->foreignId('lead_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('planned_on');
            $table->string('status', 20)->default('planned')->index();
            $table->text('summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('iso_clause_quality_audit', function (Blueprint $table) {
            $table->foreignId('quality_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iso_clause_id')->constrained()->cascadeOnDelete();
            $table->primary(['quality_audit_id', 'iso_clause_id']);
        });

        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_audit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->foreignId('iso_clause_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
        Schema::dropIfExists('iso_clause_quality_audit');
        Schema::dropIfExists('quality_audits');
    }
};
