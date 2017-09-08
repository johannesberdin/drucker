<?php

namespace Drucker;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Drucker
{
  /**
   * Holds the filesystem.
   * @var Illuminate\Filesystem\Filesystem $filesystem
   */
    private $filesystem;

    /**
     * Holds the path mails should be stored to.
     * @var string $mailPath
     */
    private $mailPath;

    /**
     * Holds the printing command.
     * @var string $command
     */
    private $command;

    /**
     * Initializes the printing engine, whoop whoop.
     *
     * @param string $mailPath
     * @param string $command
     * @param Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct($mailPath = 'mails', $command = 'lp', $filesystem = null)
    {
        $this->command = $command;
        $this->filesystem = $filesystem ?: new Filesystem();

        $this->prepareDirectory($mailPath);
        $this->mailPath = $mailPath;
    }

    /**
     * Prepares directory to be used by the script.
     *
     * @param string $directory
     * @return void
     */
    private function prepareDirectory($directory)
    {
        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Deletes a directory.
     *
     * @param string $directory
     * @return void
     */
    private function cleanupDirectory($directory)
    {
        $this->filesystem->delete($directory);
    }

    /**
     * Queues content for printing.
     *
     * @param string $contents
     * @return void
     */
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

    /**
     * Prepares the print process.
     *
     * @return Symfony\Component\Process\ProcessBuilder
     */
    protected function createProcessBuilder()
    {
        return ProcessBuilder::create()->setPrefix($this->command);
    }

    /**
     * Returns the output of the print command on success.
     *
     * @param Symfony\Component\Process\Process $process
     * @throws Symfony\Component\Process\Exception\ProcessFailedException
     * @return string
     */
    protected function getOutput(Process $process)
    {
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process->getErrorOutput());
        }
        return $process->getOutput();
    }
}
