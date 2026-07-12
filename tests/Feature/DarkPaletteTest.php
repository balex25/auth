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
