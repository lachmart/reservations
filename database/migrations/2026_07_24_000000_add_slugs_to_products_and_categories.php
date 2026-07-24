<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'slug')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }
        if (! Schema::hasColumn('categories', 'slug')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        $this->backfill('categories');
        $this->backfill('products');

        Schema::table('products', fn (Blueprint $t) => $t->unique('slug'));
        Schema::table('categories', fn (Blueprint $t) => $t->unique('slug'));
    }

    private function backfill(string $table): void
    {
        $used = [];
        foreach (DB::table($table)->select('id', 'name', 'slug')->get() as $row) {
            if (! empty($row->slug)) {
                $used[$row->slug] = true;
                continue;
            }
            $base = Str::slug($row->name) ?: $table;
            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = $base.'-'.$i++;
            }
            $used[$slug] = true;
            DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
