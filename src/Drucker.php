<?php

namespace Drucker;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Drucker {
    private $command;

    public function __construct($command = 'lpr')
    {
        $this->command = $command;
    }

    public function paper($code) {
      $builder = $this->createProcessBuilder();

      $process = $builder->setInput($code)->getProcess();
      return $this->getOutput($process);
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
