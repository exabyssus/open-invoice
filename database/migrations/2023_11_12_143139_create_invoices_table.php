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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->foreignId('client_id');
            $table->foreignId('company_id');
            $table->foreignIdFor(\App\Models\User::class, 'created_by');
            $table->timestamp('date');
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_advance')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->integer('dept_amount')->default(0);
            $table->string('currency');
            $table->string('language')->default('lv');
            $table->text('comment')->nullable();

            $table->string('template');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
