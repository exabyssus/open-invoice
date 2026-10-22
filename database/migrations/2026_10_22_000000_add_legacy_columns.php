<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ids of the old rekini.agw.lv database (so the import can be re-run) and the PDF the old system issued.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->unique(['company_id', 'legacy_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->string('legacy_pdf')->nullable();
            $table->unique(['company_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'legacy_id']);
            $table->dropColumn(['legacy_id', 'legacy_pdf']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'legacy_id']);
            $table->dropColumn('legacy_id');
        });
    }
};
