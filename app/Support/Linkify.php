<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Linkify
{
    /**
     * Convert plain text into safe HTML where any http(s) URLs become
     * clickable links. Everything is escaped FIRST, so pasted HTML or
     * <script> tags can never execute — only the anchor tags we build
     * here are real HTML.
     */
    public static function links(?string $text): HtmlString
    {
        $escaped = e($text ?? '');

        $linked = preg_replace_callback(
            // Match on the escaped text: no "<" can appear, and "&" inside
            // URLs is already "&amp;" — which an href attribute decodes back.
            '~https?://[^\s<]+~i',
            function (array $match): string {
                $url = $match[0];

                // Trailing sentence punctuation belongs to the text, not the URL.
                $trail = '';
                while ($url !== '' && preg_match('/[.,;:!?)\]]$/', $url)) {
                    $trail = substr($url, -1).$trail;
                    $url = substr($url, 0, -1);
                }

                return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer" '
                    .'class="font-semibold text-primary-dark underline break-all">'
                    .$url.'</a>'.$trail;
            },
            $escaped
        );

        return new HtmlString($linked);
    }
}
