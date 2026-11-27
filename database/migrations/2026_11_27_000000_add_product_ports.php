<?php

use App\Models\Port;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Connectors on the gear, from the manufacturers' spec sheets (and, for cables and
 * distro, from the product names). Gender follows the maker's naming: a device input
 * is usually a female XLR, an output a male one; Speakon and powerCON sockets on a
 * device are "male" in Neutrik's naming, the cable plugs "female". Our own builds
 * (stagebox, XR18 rack, dimmer) and the power connectors the specs don't name are
 * as the owner described them; the amp racks are entered by hand in the admin.
 * Matches products by exact name, skips missing ones; safe to re-run.
 */
return new class extends Migration
{
    private const PORTS = [
        'xlr3-m' => ['XLR 3pin', 'male'],
        'xlr3-f' => ['XLR 3pin', 'female'],
        'xlr5-m' => ['XLR 5pin', 'male'],
        'xlr5-f' => ['XLR 5pin', 'female'],
        'combo-f' => ['XLR/jack combo', 'female'],
        'jack63-f' => ['Jack 6,3 mm', 'female'],
        'jack35-f' => ['Jack 3,5 mm', 'female'],
        'speakon-m' => ['Speakon', 'male'],
        'speakon-f' => ['Speakon', 'female'],
        'rj45-m' => ['RJ45 / etherCON', 'male'],
        'rj45-f' => ['RJ45 / etherCON', 'female'],
        'usba-f' => ['USB-A', 'female'],
        'usbb-f' => ['USB-B', 'female'],
        'usbc-f' => ['USB-C', 'female'],
        'hdmi-f' => ['HDMI', 'female'],
        'thunderbolt-f' => ['Thunderbolt (mini DisplayPort)', 'female'],
        'iec-c14-m' => ['IEC C14 (napájení)', 'male'],
        'iec-c6-m' => ['IEC C6 (napájení, trojlístek)', 'male'],
        'powercon-in-m' => ['powerCON vstup (modrý)', 'male'],
        'powercon-out-m' => ['powerCON výstup (šedý)', 'male'],
        'true1-m' => ['powerCON TRUE1', 'male'],
        '230v-m' => ['230 V (E/F)', 'male'],
        '230v-f' => ['230 V (E/F)', 'female'],
        'cee16-4-m' => ['CEE 16 A 4pin', 'male'],
        'cee16-5-m' => ['CEE 16 A 5pin', 'male'],
        'cee32-4-m' => ['CEE 32 A 4pin', 'male'],
        'cee32-5-m' => ['CEE 32 A 5pin', 'male'],
        'cee32-5-f' => ['CEE 32 A 5pin', 'female'],
        'midi-f' => ['MIDI (DIN 5pin)', 'female'],
    ];

    private const MIC = ['xlr3-m' => 1];

    private const XLR_CABLE = ['xlr3-m' => 1, 'xlr3-f' => 1];

    private const SPEAKON_CABLE = ['speakon-f' => 2];

    private const EXTENSION_4 = ['230v-m' => 1, '230v-f' => 4];

    private const EXTENSION_1 = ['230v-m' => 1, '230v-f' => 1];

    private const PRODUCT_PORTS = [
        // Mikrofony
        'AKG C1000' => self::MIC,
        'Audix D6' => self::MIC,
        'Audix f2' => self::MIC,
        'Audix i5' => self::MIC,
        'Audix OM 3' => self::MIC,
        'sE Electronics V7' => self::MIC,
        'sE Electronics sE8 Stereo (pár)' => ['xlr3-m' => 2],
        'Sennheiser e609' => self::MIC,
        'Shure Beta58' => self::MIC,
        'Shure SM57' => self::MIC,
        'Shure SM58' => self::MIC,
        'AKG WMS40 Mini Dual' => ['jack63-f' => 2],
        'Sennheiser XSW 2-835 B' => ['xlr3-m' => 1, 'jack63-f' => 1],

        // Mixpulty: 16 mic + talkback in, 12 out + AES; ST1/ST2 L/R, out A/B, phones, footswitch
        'Allen&Heath SQ5 + case' => [
            'xlr3-f' => 17, 'xlr3-m' => 13, 'jack63-f' => 8, 'jack35-f' => 1,
            'rj45-f' => 2, 'usba-f' => 1, 'usbb-f' => 1, 'iec-c14-m' => 1,
        ],
        // 8 XLR + 8 combo in, 2 stereo line in (4 jacks), 2 alt out, phones, footswitch, 8 XLR out
        'Allen&Heath CQ-20B (rack)' => [
            'xlr3-f' => 8, 'combo-f' => 8, 'jack63-f' => 8, 'xlr3-m' => 8,
            'rj45-f' => 1, 'usba-f' => 1, 'usbb-f' => 1, 'iec-c14-m' => 1,
        ],
        // 2 mic XLR, 2 line/Hi-Z jacks, 2 stereo in (4 jacks), phones, main L/R XLR
        'Allen&Heath ZEDi-8' => [
            'xlr3-f' => 2, 'jack63-f' => 7, 'xlr3-m' => 2, 'usbb-f' => 1, 'iec-c6-m' => 1,
        ],
        // XR18 front: 16 combo, 17/18 jacks, 6 aux + 2 main XLR out, phones, USB, Ultranet,
        // MIDI in/out; the rack's connector strip adds powerCON and etherCON
        'XR18' => [
            'combo-f' => 16, 'jack63-f' => 3, 'xlr3-m' => 8, 'usbb-f' => 1,
            'rj45-f' => 2, 'midi-f' => 2, 'powercon-in-m' => 1,
        ],
        // AR2412 behind multipins to XLR fan-outs: red 12 in, the other 8 in + 4 out; dSnake
        'Allen&Heath Stagebox' => ['xlr3-f' => 20, 'xlr3-m' => 4, 'rj45-f' => 1],

        // DI-boxy: jack in + parallel thru, XLR out per channel
        'ART dPDB (2ch pasivní)' => ['jack63-f' => 4, 'xlr3-m' => 2],
        'ART PDB (1ch pasivní)' => ['jack63-f' => 2, 'xlr3-m' => 1],

        // Reproboxy
        'dB Technologies Opera 12' => ['combo-f' => 2, 'xlr3-m' => 1, 'iec-c14-m' => 1],
        'dB Technologies Opera 15' => ['combo-f' => 2, 'xlr3-m' => 1, 'iec-c14-m' => 1],
        'dB Technologies Opera Reevo 212' => ['combo-f' => 1, 'xlr3-m' => 1, 'iec-c14-m' => 1],
        // 2 combo, Hi-Z in + pass-thru, aux in + phones, 2 USB charging
        'JBL Eon Compact One' => ['combo-f' => 2, 'jack63-f' => 2, 'jack35-f' => 2, 'usba-f' => 2, 'iec-c14-m' => 1],
        'Nexo PS10R2' => ['speakon-m' => 2],
        'Nexo PS15R2 (2000W, 1x15"+1x3")' => ['speakon-m' => 2],
        'Subwoofer MT18' => ['speakon-m' => 2],

        // Kabely
        'CAT 5 - 50m' => ['rj45-m' => 2],
        'XLR - XLR (Sommer cable + Neutrik) - 1m' => self::XLR_CABLE,
        'XLR - XLR (Sommer cable + Neutrik) - 2m' => self::XLR_CABLE,
        'XLR - XLR (Sommer cable + Neutrik) - 3m' => self::XLR_CABLE,
        'XLR - XLR (Sommer cable + Neutrik) - 5m' => self::XLR_CABLE,
        'XLR - XLR (Sommer cable + Neutrik) - 10m' => self::XLR_CABLE,
        'XLR - XLR (Sommer cable + Neutrik) - 15m' => self::XLR_CABLE,
        'XLR - XLR (Sommer cable + Neutrik) - 20m' => self::XLR_CABLE,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 1,5m' => self::SPEAKON_CABLE,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 5m' => self::SPEAKON_CABLE,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 10m' => self::SPEAKON_CABLE,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 15m' => self::SPEAKON_CABLE,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 20m' => self::SPEAKON_CABLE,
        'SpeakON (Titanex 2x4mm + Neutrik) - 5m' => self::SPEAKON_CABLE,
        'SpeakON (Titanex 2x4mm + Neutrik) - 10m' => self::SPEAKON_CABLE,
        'SpeakON (Titanex 2x4mm + Neutrik) - 15m' => self::SPEAKON_CABLE,
        'SpeakON (Titanex 2x4mm + Neutrik) - 20m' => self::SPEAKON_CABLE,
        'DMX kabel Klotz, délka 1m' => self::XLR_CABLE,
        'DMX kabel Klotz, délka 2m' => self::XLR_CABLE,
        'DMX kabel Klotz, délka 3m' => self::XLR_CABLE,
        'DMX kabel Klotz, délka 5m' => self::XLR_CABLE,
        'DMX kabel Klotz, délka 7m' => self::XLR_CABLE,
        'DMX kabel Klotz, délka 10m' => self::XLR_CABLE,
        'DMX buben 30m' => self::XLR_CABLE,
        'DMX buben 50m' => self::XLR_CABLE,

        // Distro (from the names: vidlice = plug, zásuvka / PES / powerbox = sockets)
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás délka 1,5m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 2m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 3m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás délka 5m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 7m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 10m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 15m' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, 4.zás., délka 30m, na bubnu' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, 4.zás, délka 50m, na bubnu' => self::EXTENSION_4,
        'Titanex 3G2,5 H07RN-F, vidlice Legrand, 4.zás, délka 30m, na bubnu' => self::EXTENSION_4,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, zásuvka Legrand, délka 5m' => self::EXTENSION_1,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, zásvuka Legrand, délka 7m' => self::EXTENSION_1,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, zásuvka Legrand, délka 10m' => self::EXTENSION_1,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, zásuvka Legrand, délka 15m' => self::EXTENSION_1,
        'Titanex 5G2,5 H07RN-F, vidlice SEZ 32A 5P, powerbox 6x230V, 10m' => ['cee32-5-m' => 1, '230v-f' => 6],
        'Titanex 5G4 H07RN-F, vidlice SEZ 16A 4P, zásuvka SEZ 32A 5P, 1m' => ['cee16-4-m' => 1, 'cee32-5-f' => 1],
        'Titanex 5G4 H07RN-F, vidlice SEZ 16A 5P, zásuvka SEZ 32A 5P, 1m' => ['cee16-5-m' => 1, 'cee32-5-f' => 1],
        'Titanex 5G4 H07RN-F, vidlice SEZ 32A 4P, zásuvka SEZ 32A 5P, 1m' => ['cee32-4-m' => 1, 'cee32-5-f' => 1],
        'Titanex 5G4 H07RN-F, vidlice SEZ 32A 5P, zásuvka SEZ 32A 5P, 20m' => ['cee32-5-m' => 1, 'cee32-5-f' => 1],

        // Světla: DMX in = male, DMX out = female
        'Cameo Thunderwash 600 RGBW' => [
            'xlr3-m' => 1, 'xlr3-f' => 1, 'powercon-in-m' => 1, 'powercon-out-m' => 1,
        ],
        'Chauvet Proffesional Amhaze Whisper 2000W' => ['xlr3-m' => 1, 'xlr3-f' => 1, 'true1-m' => 1],
        'Fractal Lights 9x10W' => ['xlr3-m' => 1, 'xlr3-f' => 1, 'iec-c14-m' => 1],
        'Varytec LED Studio 150 2900K' => [
            'xlr3-m' => 1, 'xlr3-f' => 1, 'powercon-in-m' => 1, 'powercon-out-m' => 1,
        ],
        'Varytec Theatre Spot 650/1000 Fresnel' => ['230v-m' => 1],
        // 1ch dimmer: plug in, socket out
        'Botex UP-2-1' => ['xlr3-m' => 1, 'xlr3-f' => 1, '230v-m' => 1, '230v-f' => 1],
        'Rozvaděč + DMX splitter + 1ch dimmer' => ['xlr3-m' => 1, 'xlr3-f' => 1, '230v-m' => 1, '230v-f' => 1],
        'Avolites T1 + dotykový monitor' => ['xlr5-f' => 1, 'usbb-f' => 1],

        // Počítače
        'Acer Aspire S5 (Windows 10)' => ['usba-f' => 2, 'hdmi-f' => 1, 'thunderbolt-f' => 1, 'jack35-f' => 1],
        'Lenovo ThinkPad T470 (Ubuntu)' => ['usba-f' => 3, 'usbc-f' => 1, 'hdmi-f' => 1, 'rj45-f' => 1, 'jack35-f' => 1],
        'Lenovo ThinkPad T480 (Windows 11)' => ['usba-f' => 2, 'usbc-f' => 2, 'hdmi-f' => 1, 'rj45-f' => 1, 'jack35-f' => 1],
        // 2015: 2× Thunderbolt 2
        'MacBook Pro (macOS)' => ['usba-f' => 2, 'thunderbolt-f' => 2, 'hdmi-f' => 1, 'jack35-f' => 1],

        // Video
        // 2 transmitters (mic in + USB-C), receiver (out, monitoring, USB-C), charging case
        'DJI Mic 2' => ['jack35-f' => 4, 'usbc-f' => 4],
        // receiver + charging case
        'DJI Mic Mini' => ['jack35-f' => 1, 'usbc-f' => 2],
        'DJI OM 4' => ['usbc-f' => 1, 'usba-f' => 1],
        'DJI Osmo Action 3' => ['usbc-f' => 1],
        'DJI Osmo Mobile 7' => ['usbc-f' => 1],
        'DJI RS 2 Pro' => ['usbc-f' => 3],
    ];

    public function up(): void
    {
        $products = Product::whereIn('name', array_keys(self::PRODUCT_PORTS))->get()->keyBy('name');

        if ($products->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($products) {
            $ports = collect(self::PORTS)->map(
                fn (array $port) => Port::firstOrCreate(['name' => $port[0], 'gender' => $port[1]])->id
            );

            foreach (self::PRODUCT_PORTS as $name => $connectors) {
                $products->get($name)?->ports()->syncWithoutDetaching(
                    collect($connectors)->mapWithKeys(fn ($amount, $key) => [$ports[$key] => ['amount' => $amount]])->all()
                );
            }
        });
    }

    public function down(): void
    {
        $ports = collect(self::PORTS)
            ->map(fn (array $port) => Port::where('name', $port[0])->where('gender', $port[1])->first())
            ->filter();

        foreach (self::PRODUCT_PORTS as $name => $connectors) {
            Product::where('name', $name)->first()?->ports()->detach(
                collect($connectors)->keys()->map(fn ($key) => $ports->get($key)?->id)->filter()->all()
            );
        }

        $ports->filter(fn (Port $port) => ! $port->products()->exists())->each->delete();
    }
};
