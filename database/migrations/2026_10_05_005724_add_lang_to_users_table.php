<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each user's interface language (a key of config('app.locales')); null means the app default.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('lang', 10)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lang');
        });
    }
};
