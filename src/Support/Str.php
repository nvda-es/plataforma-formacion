<?php
declare(strict_types=1);

namespace App\Support;

final class Str
{
    /** Convierte un texto en un identificador para URL: "Introducción a PHP" → "introduccion-a-php". */
    public static function slug(string $text): string
    {
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
            if ($transliterator !== null) {
                $text = (string) $transliterator->transliterate($text);
            }
        }
        $text = strtr(mb_strtolower($text), [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c',
        ]);
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim(substr(trim($text, '-'), 0, 120), '-');
        return $text === '' ? 'curso' : $text;
    }
}
