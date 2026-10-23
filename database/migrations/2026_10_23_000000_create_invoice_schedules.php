<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('client_id');
            $table->string('frequency');                       // MONTHLY | YEARLY
            $table->unsignedTinyInteger('month')->nullable();  // yearly only
            $table->unsignedTinyInteger('day');                // 1-31, the last day of a shorter month is used
            $table->unsignedSmallInteger('due_days')->nullable();
            $table->boolean('auto_send')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('comment')->nullable();
            $table->json('lines');                             // title, quantity, price (cents), vat, unit
            $table->date('next_run_on');
            $table->dateTime('last_run_at')->nullable();
            $table->foreignId('created_by');
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('invoice_schedule_id')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unique(['invoice_schedule_id', 'date']);   // a schedule makes one invoice per day
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $table) => $table->dropColumn('email'));

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_schedule_id', 'date']);
            $table->dropColumn(['invoice_schedule_id', 'sent_at']);
        });

        Schema::dropIfExists('invoice_schedules');
    }
};
