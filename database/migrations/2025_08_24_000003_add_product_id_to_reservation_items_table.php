<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('reservation_items', 'product_id')) {
                $table->foreignId('product_id')->after('id')->constrained('products')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            if (Schema::hasColumn('reservation_items', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
        });
    }
};
