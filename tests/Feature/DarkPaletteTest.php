<?php

use Illuminate\Support\Facades\File;

it('keeps gray utilities for light mode and neutral utilities for dark mode', function () {
    $resourcePaths = [
        dirname(__DIR__, 2).'/resources/views',
        dirname(__DIR__, 2).'/resources/css',
    ];

    $darkGrayUsages = collect($resourcePaths)
        ->flatMap(fn (string $path) => File::allFiles($path))
        ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), ['php', 'css'], true))
        ->flatMap(function (SplFileInfo $file): array {
            preg_match_all('/dark:[^\s"\']*gray-\d+(?:\/\d+)?/', $file->getContents(), $matches);

            return array_map(
                fn (string $class): string => $file->getRelativePathname().': '.$class,
                $matches[0],
            );
        });

    expect($darkGrayUsages)->toBeEmpty();
});

it('provides dark interaction overrides for light gray interaction utilities', function () {
    $files = File::allFiles(dirname(__DIR__, 2).'/resources/views');
    $missingOverrides = [];

    foreach ($files as $file) {
        foreach (preg_split('/\R/', $file->getContents()) as $lineNumber => $line) {
            preg_match_all('/(?<!dark:)(hover|focus|active):([a-z-]+)gray-\d+(?:\/\d+)?/', $line, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $darkProperty = 'dark:'.$match[1].':'.$match[2];

                if (! str_contains($line, $darkProperty)) {
                    $missingOverrides[] = $file->getRelativePathname().':'.($lineNumber + 1).' '.$match[0];
                }
            }
        }
    }

    expect($missingOverrides)->toBeEmpty();
});
