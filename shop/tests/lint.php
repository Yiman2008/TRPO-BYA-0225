<?php
declare(strict_types=1);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__), FilesystemIterator::SKIP_DOTS));
$count = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') { continue; }
    $process = proc_open([PHP_BINARY, '-l', $file->getPathname()], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start PHP lint'); }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { fwrite(STDERR, $stdout . $stderr); exit(1); }
    $count++;
}
echo "PHP lint: $count files OK\n";
