<?php

use App\Support\Linkify;

test('turns a pasted URL into a clickable link', function () {
    $html = Linkify::links('Watch this: https://www.youtube.com/watch?v=CoCmsyFQ_Xc')->toHtml();

    expect($html)->toContain('<a href="https://www.youtube.com/watch?v=CoCmsyFQ_Xc"')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"');
});

test('plain text without URLs is unchanged (just escaped)', function () {
    $html = Linkify::links('Finish pages 4 to 7 of the workbook.')->toHtml();

    expect($html)->toBe('Finish pages 4 to 7 of the workbook.')
        ->not->toContain('<a ');
});

test('escapes HTML so script tags can never execute', function () {
    $html = Linkify::links('<script>alert("xss")</script> visit https://example.com')->toHtml();

    expect($html)->not->toContain('<script>')
        ->toContain('&lt;script&gt;')
        ->toContain('<a href="https://example.com"');
});

test('a malicious URL cannot break out of the href attribute', function () {
    $html = Linkify::links('https://example.com/"><script>alert(1)</script>')->toHtml();

    expect($html)->not->toContain('"><script>');
});

test('URLs with query strings keep their parameters', function () {
    $html = Linkify::links('https://www.youtube.com/watch?v=abc&t=42s')->toHtml();

    // "&" is escaped to "&amp;" — the browser decodes it back inside href.
    expect($html)->toContain('href="https://www.youtube.com/watch?v=abc&amp;t=42s"');
});

test('trailing sentence punctuation stays outside the link', function () {
    $html = Linkify::links('See https://example.com.')->toHtml();

    expect($html)->toContain('<a href="https://example.com"')
        ->toContain('</a>.');
});

test('handles null and empty descriptions', function () {
    expect(Linkify::links(null)->toHtml())->toBe('');
    expect(Linkify::links('')->toHtml())->toBe('');
});

test('links multiple URLs in one description', function () {
    $html = Linkify::links("Video 1: https://youtu.be/aaa\nVideo 2: https://youtu.be/bbb")->toHtml();

    expect($html)->toContain('href="https://youtu.be/aaa"')
        ->toContain('href="https://youtu.be/bbb"');
});
