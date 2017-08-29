<?php

namespace Drucker;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Drucker
{
    private $filesystem;
    private $mailPath;
    private $command;

    public function __construct($mailPath = 'mails', $command = 'lp', $filesystem = null)
    {
        $this->command = $command;
        $this->filesystem = $filesystem ?: new Filesystem();

        $this->prepareDirectory($mailPath);
        $this->mailPath = $mailPath;
    }

    private function prepareDirectory($directory)
    {
        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }

    private function cleanupDirectory($directory)
    {
        $this->filesystem->delete($directory);
    }

    public function queue($contents)
    {
        $filePath = $this->mailPath . '/' . Str::random(32);

        $this->filesystem->put($filePath, $contents);

        $builder = $this->createProcessBuilder();
        $process = $builder->add($filePath)->getProcess();

        $output = $this->getOutput($process);

        // This is pretty much a fire-and-forget approach.
        if (preg_match("/request\s+id\s+is\s+(.*)\s+\(\d+ file\(s\)\)/", $output, $match)) {
            $this->filesystem->delete($filePath);
        }
    }

    protected function createProcessBuilder()
    {
        return ProcessBuilder::create()->setPrefix($this->command);
    }

    protected function getOutput(Process $process)
    {
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process->getErrorOutput());
        }
        return $process->getOutput();
    }
}
