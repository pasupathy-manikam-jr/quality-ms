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
        Schema::create('gauges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->string('type', 100)->nullable();
            $table->string('measuring_range', 100)->nullable();
            $table->string('resolution', 50)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('interval_days');
            $table->string('status', 20)->default('active')->index();
            $table->date('next_due_on')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gauge_id')->constrained()->restrictOnDelete();
            $table->date('performed_on');
            $table->string('performed_by');
            $table->string('result', 20);
            $table->text('as_found')->nullable();
            $table->text('as_left')->nullable();
            $table->date('next_due_on')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('file_sha256', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['gauge_id', 'performed_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibrations');
        Schema::dropIfExists('gauges');
    }
};
