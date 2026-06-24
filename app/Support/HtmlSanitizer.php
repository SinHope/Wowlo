<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * A tiny allowlist HTML sanitiser for the Banner Notification rich text.
 *
 * The compose toolbar only produces bold / italic / underline / strikethrough /
 * font colour / hyperlink, so we accept a deliberately small set of tags and
 * attributes and DROP everything else (scripts, event handlers, iframes, styles
 * other than `color`, non-http(s)/mailto links, …). The banner is rendered raw
 * to EVERY user, so even though only the trusted super_admin can write it, we
 * still sanitise server-side as defence in depth — never store unfiltered HTML.
 */
class HtmlSanitizer
{
    /** Tags we keep. Anything else is unwrapped (its safe text/children survive). */
    private const ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'u', 's', 'strike', 'span', 'a', 'br', 'p', 'div', 'ul', 'ol', 'li'];

    /**
     * Clean a fragment of HTML down to the banner allowlist.
     */
    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument;

        // Parse as a UTF-8 fragment without DOMDocument injecting <html>/<body>
        // wrappers or a doctype, and swallow the warnings HTML5 tags can raise.
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__wowlo_root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('__wowlo_root');

        if (! $root) {
            return '';
        }

        self::sanitizeChildren($root, $dom);

        // Serialise the root's inner HTML.
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Recursively walk and sanitise every child of $node in place.
     */
    private static function sanitizeChildren(DOMNode $node, DOMDocument $dom): void
    {
        // Snapshot first — we mutate the live child list as we go.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue; // text nodes are safe and kept as-is
            }

            $tag = strtolower($child->nodeName);

            // Clean descendants before deciding what to do with this element.
            self::sanitizeChildren($child, $dom);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Disallowed tag: drop the tag but keep its (already-cleaned)
                // children so wording survives. <script>/<style> have no
                // element children we want, so their text is discarded.
                if (in_array($tag, ['script', 'style'], true)) {
                    $child->parentNode?->removeChild($child);
                } else {
                    self::unwrap($child);
                }

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    /**
     * Strip every attribute except the few we explicitly allow per tag.
     */
    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);

            $keep = match (true) {
                // Links: only a safe href, and we force target/rel ourselves below.
                $tag === 'a' && $name === 'href' => self::isSafeUrl($attr->nodeValue),
                // Colour only — drop any other inline style.
                $name === 'style' => true,
                default => false,
            };

            if (! $keep) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        if ($el->hasAttribute('style')) {
            $color = self::extractColor($el->getAttribute('style'));
            if ($color !== null) {
                $el->setAttribute('style', 'color: '.$color);
            } else {
                $el->removeAttribute('style');
            }
        }

        if ($tag === 'a') {
            if (! $el->hasAttribute('href')) {
                // A link with no safe href is just text.
                self::unwrap($el);

                return;
            }
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /** Replace an element with its children (keeps the text, drops the tag). */
    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        if (! $parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    /** Allow only http(s) and mailto links — blocks javascript:, data:, etc. */
    private static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);

        return (bool) preg_match('~^(https?://|mailto:)~i', $url);
    }

    /** Pull a single safe CSS colour value out of a style string, or null. */
    private static function extractColor(string $style): ?string
    {
        if (! preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $style, $m)) {
            return null;
        }

        $value = trim($m[1]);

        // Accept hex, rgb()/rgba(), and plain colour keywords only.
        if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)
            || preg_match('/^rgba?\(\s*[\d.,%\s]+\)$/i', $value)
            || preg_match('/^[a-z]+$/i', $value)) {
            return $value;
        }

        return null;
    }
}
