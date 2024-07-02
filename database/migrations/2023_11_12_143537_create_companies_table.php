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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('registration_number');
            $table->string('address');
            $table->string('agreement_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_swift')->nullable();
            $table->string('bank_iban')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('show_accounts')->default(1);
            $table->string('number_prefix')->nullable();
            $table->string('number_suffix')->nullable();
            $table->string('number_style')->default(\App\NumberStyles::NUMBER_STYLE_NONE->name);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
