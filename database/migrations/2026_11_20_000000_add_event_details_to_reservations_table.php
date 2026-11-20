<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the event takes place, what kind of event it is and whether the customer
     * wants the gear delivered and set up. Needed to price delivery, and captured the
     * same way from the cart and from the public MCP server.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('event_place')->nullable()->after('note');
            $table->string('event_type', 100)->nullable()->after('event_place');
            $table->boolean('delivery')->default(false)->after('event_type');
            $table->string('source', 20)->default('web')->after('delivery');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['event_place', 'event_type', 'delivery', 'source']);
        });
    }
};
