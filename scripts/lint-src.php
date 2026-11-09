<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dirs = [$root . '/src', $root . '/tests', $root . '/examples'];
$failed = false;

foreach ($dirs as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php') {
            continue;
        }
        $path = $file->getPathname();
        $cmd = 'php -l ' . escapeshellarg($path) . ' 2>&1';
        $output = [];
        $status = 0;
        exec($cmd, $output, $status);
        $line = implode("\n", $output);
        if ($status !== 0 || !preg_match('/^No syntax errors detected in .+$/', $line)) {
            fwrite(STDERR, $line . "\n");
            $failed = true;
            continue;
        }
        fwrite(STDOUT, $line . "\n");
    }
}

exit($failed ? 1 : 0);
