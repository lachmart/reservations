<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('delivery_zone', 20)->nullable()->after('delivery');
            // Estimated delivery price included in `price`; null = to be quoted.
            $table->decimal('delivery_price', 10, 2)->nullable()->after('delivery_zone');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['delivery_zone', 'delivery_price']);
        });
    }
};
