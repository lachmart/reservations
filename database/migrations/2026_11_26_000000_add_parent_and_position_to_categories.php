<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two-level category tree: a section (Zvuk, Světla, …) and its subcategories
 * (Mikrofony, Mixpulty, …), plus a menu order. No foreign key on purpose: the
 * live schema has drifted from the migrations, and Category clears its
 * children's parent_id itself when it is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->index()->after('slug');
            }
            if (! Schema::hasColumn('categories', 'position')) {
                $table->unsignedSmallInteger('position')->default(0)->after('parent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'position']);
        });
    }
};
