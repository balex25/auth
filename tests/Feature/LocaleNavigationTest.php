<?php

use Illuminate\Support\Facades\File;

it('routes internal auth navigation through locale-aware helpers', function () {
    $paths = [
        dirname(__DIR__, 2).'/resources/views/pages/auth',
        dirname(__DIR__, 2).'/src/Http',
    ];

    $violations = collect($paths)
        ->flatMap(fn (string $path) => File::allFiles($path))
        ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
        ->flatMap(function (SplFileInfo $file): array {
            $patterns = [
                '/redirect\(\)->route\(/',
                '/redirect\([\'\"]\/[\'\"]\)/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $file->getContents()) === 1) {
                    return [$file->getRelativePathname()];
                }
            }

            return [];
        });

    expect($violations)->toBeEmpty();
});
