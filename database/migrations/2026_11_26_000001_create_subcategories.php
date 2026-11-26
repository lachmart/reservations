<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Subcategories for the menu (Zvuk → Sety, Reproboxy, Mixpulty, Mikrofony, …)
 * and the order of the sections. Products of the live catalog are moved into
 * them by slug (a trailing % is a prefix match), only from within the parent
 * section. Anything not listed stays in the section itself and is still shown
 * there. Does nothing for sections that don't exist (local, tests); safe to
 * re-run.
 */
return new class extends Migration
{
    private const SECTION_ORDER = ['zvuk', 'svetla', 'stage', 'video', 'pocitace', 'distro'];

    private const SUBCATEGORIES = [
        'zvuk' => [
            'sety' => ['Sety', ['set-svatba-s-kapelou', 'set-dj-party', 'set-proslovy-a-hudba', 'set-svatebni-obrad']],
            'reproboxy-pasivni' => ['Reproboxy pasivní', ['nexo-ps10r2', 'nexo-ps15r2-2000w-1x151x3']],
            'reproboxy-aktivni' => ['Reproboxy aktivní', ['db-technologies-opera-12', 'db-technologies-opera-15', 'db-technologies-opera-reevo-212', 'jbl-eon-compact-one']],
            'subwoofery' => ['Subwoofery', ['subwoofer-mt18-18']],
            'zesilovace' => ['Zesilovače', ['amprack-fp']],
            'mixpulty' => ['Mixpulty', ['allenheath-cq-20b-rack', 'allenheath-sq5-case', 'allenheath-zed6', 'allenheath-stagebox', 'xr18']],
            'mikrofony' => ['Mikrofony', ['akg-%', 'audix-%', 'se-electronics-%', 'sennheiser-%', 'shure-%']],
            'stativy' => ['Stativy', ['adam-hall-%', 'konigmeyer-%', 'gravity-sp-%']],
            'di-boxy' => ['DI-boxy', ['art-dpdb-2ch-pasivni', 'art-pdb-1ch-pasivni']],
            'kabely' => ['Kabely', ['xlr-xlr-%', 'speakon-%', 'cat-5-%']],
        ],
        'svetla' => [
            'svetla-a-reflektory' => ['Světla a reflektory', ['cameo-%', 'fractal-%', 'varytec-%']],
            'vyrobniky-mlhy' => ['Výrobníky mlhy', ['chauvet-proffesional-amhaze-whisper-2000w']],
            'ovladani-svetel' => ['Ovládání světel', ['avolites-t1-dotykovy-monitor', 'botex-up-2-1', 'rozvadec-dmx-splitter-1ch-dimmer']],
            'stativy-na-svetla' => ['Stativy na světla', ['gravity-ls-%']],
            'dmx-kabely' => ['DMX kabely', ['dmx-%']],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::SECTION_ORDER as $i => $slug) {
                DB::table('categories')->where('slug', $slug)->update(['position' => $i + 1]);
            }

            foreach (self::SUBCATEGORIES as $parentSlug => $children) {
                $parentId = DB::table('categories')->where('slug', $parentSlug)->value('id');
                if (! $parentId) {
                    continue;
                }

                $position = 0;
                foreach ($children as $slug => [$name, $productSlugs]) {
                    $position++;
                    $childId = $this->upsertCategory($slug, $name, $parentId, $position);

                    DB::table('products')
                        ->where('category_id', $parentId)
                        ->where(function ($q) use ($productSlugs) {
                            foreach ($productSlugs as $pattern) {
                                str_ends_with($pattern, '%')
                                    ? $q->orWhere('slug', 'like', $pattern)
                                    : $q->orWhere('slug', $pattern);
                            }
                        })
                        ->update(['category_id' => $childId, 'updated_at' => now()]);
                }
            }
        });
    }

    private function upsertCategory(string $slug, string $name, int $parentId, int $position): int
    {
        $existing = DB::table('categories')->where('slug', $slug)->value('id');
        $values = ['name' => $name, 'parent_id' => $parentId, 'position' => $position, 'updated_at' => now()];

        if ($existing) {
            DB::table('categories')->where('id', $existing)->update($values);

            return (int) $existing;
        }

        return (int) DB::table('categories')->insertGetId($values + ['slug' => $slug, 'created_at' => now()]);
    }

    public function down(): void
    {
        // Move products back to their section and drop the subcategories.
        $children = DB::table('categories')->whereNotNull('parent_id')->get(['id', 'parent_id']);
        foreach ($children as $child) {
            DB::table('products')->where('category_id', $child->id)->update(['category_id' => $child->parent_id]);
        }
        DB::table('categories')->whereIn('id', $children->pluck('id'))->delete();
    }
};
