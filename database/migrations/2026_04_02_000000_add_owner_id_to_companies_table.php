<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
        });

        // Backfill: assign first attached user as owner
        DB::table('companies')
            ->whereNull('owner_id')
            ->get()
            ->each(function ($company) {
                $firstUser = DB::table('user_companies')
                    ->where('company_id', $company->id)
                    ->orderBy('id')
                    ->value('user_id');

                if ($firstUser) {
                    DB::table('companies')
                        ->where('id', $company->id)
                        ->update(['owner_id' => $firstUser]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
