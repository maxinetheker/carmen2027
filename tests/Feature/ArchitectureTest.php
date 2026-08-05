<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ArchitectureTest extends TestCase
{
    public function test_authored_code_files_do_not_exceed_150_lines(): void
    {
        $roots = ['app', 'database', 'resources', 'routes', 'tests'];
        $extensions = ['php', 'js', 'css', 'blade.php'];
        $tooLong = [];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(base_path($root))
            );
            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) continue;
                if (! $this->matchesExtension($file->getFilename(), $extensions)) continue;
                $lines = count(file($file->getPathname()));
                if ($lines > 150) {
                    $tooLong[] = str_replace(base_path().'\\', '', $file->getPathname()).": {$lines}";
                }
            }
        }

        $this->assertSame([], $tooLong, implode(PHP_EOL, $tooLong));
    }

    private function matchesExtension(string $name, array $extensions): bool
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($name, '.'.$extension)) return true;
        }
        return false;
    }
}
