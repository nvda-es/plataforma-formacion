<?php
declare(strict_types=1);

namespace App;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Conversión segura de Markdown a HTML.
 *
 * - El HTML incrustado en el Markdown se escapa (evita XSS).
 * - Los enlaces con esquemas peligrosos (javascript:, data:…) se eliminan.
 * - Los encabezados se desplazan para que el nivel más alto del contenido
 *   encaje en la jerarquía de la página (accesibilidad): por defecto empieza en <h2>.
 */
final class Markdown
{
    private static ?GithubFlavoredMarkdownConverter $converter = null;

    public static function toHtml(string $markdown, int $topLevel = 2): string
    {
        if (trim($markdown) === '') {
            return '';
        }
        self::$converter ??= new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);

        $html = (string) self::$converter->convert($markdown);

        if (!preg_match_all('#<h([1-6])(?=[\s>])#', $html, $matches)) {
            return $html;
        }
        $shift = $topLevel - min(array_map('intval', $matches[1]));
        if ($shift === 0) {
            return $html;
        }
        return (string) preg_replace_callback(
            '#<(/?)h([1-6])(?=[\s>])#',
            static fn (array $m): string => '<' . $m[1] . 'h' . max(1, min(6, (int) $m[2] + $shift)),
            $html
        );
    }
}
