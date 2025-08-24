<?php

namespace App\Support;

class Czech
{
    /**
     * Return a Czech vocative (5th case) form for a given full name.
     * Heuristics: use a short exceptions map for common names and simple rules for others.
     * Only the first token (given name) is inflected; if uncertain, returns the original first name.
     */
    public static function vocative(?string $fullName): string
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $fullName);
        $first = $parts[0] ?? $fullName;

        $lower = mb_strtolower($first, 'UTF-8');

        // Common exceptions and frequent names (expand as needed)
        $map = [
            // Male
            'jan' => 'Jane',
            'petr' => 'Petře',
            'jiří' => 'Jiří',
            'jiri' => 'Jiří',
            'josef' => 'Josefe',
            'tomáš' => 'Tomáši',
            'tomas' => 'Tomáši',
            'lukáš' => 'Lukáši',
            'lukas' => 'Lukáši',
            'martin' => 'Martine',
            'ondřej' => 'Ondřeji',
            'ondrej' => 'Ondřeji',
            'michal' => 'Michale',
            'radek' => 'Radku',
            'adam' => 'Adame',
            'filip' => 'Filipe',
            'david' => 'Davide',
            'vít' => 'Víte',
            'vit' => 'Víte',
            'vladimír' => 'Vladimíre',
            'vladimir' => 'Vladimíre',
            'marek' => 'Marku',
            'matěj' => 'Matěji',
            'matej' => 'Matěji',
            'václav' => 'Václave',
            'vaclav' => 'Václave',

            // Female
            'eva' => 'Evo',
            'anna' => 'Anno',
            'petra' => 'Petro',
            'lucie' => 'Lucie',
            'marie' => 'Marie',
            'veronika' => 'Veroniko',
            'kateřina' => 'Kateřino',
            'katerina' => 'Kateřino',
            'jana' => 'Jano',
            'alena' => 'Aleno',
            'adela' => 'Adélo',
            'adéla' => 'Adélo',
        ];

        if (isset($map[$lower])) {
            return $map[$lower];
        }

        // Heuristics
        // If ends with -a (common feminine), change to -o (Jana -> Jano, Petra -> Petro)
        if (self::endsWith($lower, 'a')) {
            return mb_substr($first, 0, mb_strlen($first, 'UTF-8') - 1, 'UTF-8') . 'o';
        }

        // -ek -> -ku (Radek -> Radku, Marek handled above but keep general rule)
        if (self::endsWith($lower, 'ek')) {
            return mb_substr($first, 0, mb_strlen($first, 'UTF-8') - 2, 'UTF-8') . 'ku';
        }

        // -el -> +i (Daniel -> Danieli)
        if (self::endsWith($lower, 'el')) {
            return $first . 'i';
        }

        // Names ending with a hard consonant -> +e (Martin -> Martine, Pavel -> Pavle)
        $last = mb_substr($lower, -1, 1, 'UTF-8');
        if (preg_match('/[bcčdďfghjklmnňprřsštťvzžqxwy]/u', $last)) {
            return $first . 'e';
        }

        // Fallback: return original first name
        return $first;
    }

    private static function endsWith(string $str, string $suffix): bool
    {
        return mb_substr($str, -mb_strlen($suffix, 'UTF-8'), null, 'UTF-8') === $suffix;

    }
}
