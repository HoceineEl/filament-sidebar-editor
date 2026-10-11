<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

const TRANSLATION_LOCALES = ['ar', 'en', 'fr'];

/**
 * @return array<string, string>
 */
function translationLines(string $locale): array
{
    return Arr::dot(require dirname(__DIR__, 2)."/resources/lang/{$locale}/sidebar-editor.php");
}

/**
 * @return list<string>
 */
function translationPlaceholders(string $line): array
{
    preg_match_all('/:([a-z_]+)/', $line, $matches);

    return collect($matches[1])->unique()->sort()->values()->all();
}

it('ships exactly the supported locales', function (): void {
    $directories = collect(glob(dirname(__DIR__, 2).'/resources/lang/*', GLOB_ONLYDIR))->map(fn (string $path): string => basename($path))->sort()->values()->all();

    expect($directories)->toBe(TRANSLATION_LOCALES);
});

it('has the same keys and placeholders as English', function (string $locale): void {
    $english = translationLines('en');

    expect(array_keys(translationLines($locale)))->toEqualCanonicalizing(array_keys($english));

    foreach (translationLines($locale) as $key => $line) {
        expect(translationPlaceholders($line))->toBe(translationPlaceholders($english[$key]), "{$locale}: {$key}");
    }
})->with(TRANSLATION_LOCALES);

it('renders the shown count in every locale', function (string $locale, int $total, string $expected): void {
    expect(trans_choice('sidebar-editor::sidebar-editor.shown_count', $total, ['shown' => 1, 'total' => $total], $locale))->toBe($expected);
})->with([
    ['en', 0, 'No pages'],
    ['en', 3, '1 of 3 shown'],
    ['fr', 3, '1 sur 3 affichées'],
    ['ar', 0, 'لا توجد صفحات'],
    ['ar', 3, 'تظهر 1 من 3'],
]);
