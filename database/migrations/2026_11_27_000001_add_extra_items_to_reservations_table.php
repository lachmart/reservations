<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Things added to a reservation in the warehouse (cables, extension leads,
 * tape…) that are not catalog products: [{name, amount}]. They appear on the
 * rental sheet only and change neither the price nor stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reservations', 'extra_items')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->json('extra_items')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'extra_items')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('extra_items');
            });
        }
    }
};
