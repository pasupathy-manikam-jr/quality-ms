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
        Schema::create('ncrs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('source', 30)->index();
            $table->nullableMorphs('sourceable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity', 20)->default('minor');
            $table->foreignId('part_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer')->nullable();
            $table->decimal('quantity_affected', 15, 3)->nullable();
            $table->string('disposition', 30)->nullable();
            $table->text('disposition_notes')->nullable();
            $table->foreignId('disposition_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disposition_approved_at')->nullable();
            $table->text('closure_notes')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('capas', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('type', 20)->default('corrective');
            $table->string('title');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('open')->index();
            foreach (['d1_team', 'd2_problem', 'd3_containment', 'd4_root_cause', 'd5_actions', 'd6_implementation', 'd7_prevention', 'd8_closure'] as $discipline) {
                $table->text($discipline)->nullable();
            }
            $table->date('effectiveness_check_on')->nullable();
            $table->text('effectiveness_notes')->nullable();
            $table->foreignId('effectiveness_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effectiveness_verified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('capa_ncr', function (Blueprint $table) {
            $table->foreignId('capa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ncr_id')->constrained()->cascadeOnDelete();
            $table->primary(['capa_id', 'ncr_id']);
        });

        Schema::create('capa_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capa_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capa_actions');
        Schema::dropIfExists('capa_ncr');
        Schema::dropIfExists('capas');
        Schema::dropIfExists('ncrs');
    }
};
