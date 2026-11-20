<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Customer wants installation, wiring or an operator on site (extra, quoted later). */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('service')->default(false)->after('delivery_price');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
