<?php
namespace Jack\Symfony;

use Symfony\Component\Process\Process;

/**
 * This ProcessManager is a simple wrapper to enable parallel processing using Symfony Process component.
 */
class ProcessManager
{
    /**
     * @param Process[]      $processes
     * @param int            $maxParallel Max parallel processes to run
     * @param int            $poll Poll time in microseconds
     * @param callable|null  $callback Callable which takes 3 arguments :
     * - type of output (out or err)
     * - some bytes from the output in real-time
     * - the process itself being run
     * @param callable|null $checkCallback Callable which takes no arguments and is invoked whenever we poll the process running
     * @param callable|null $finishCallback Callable which is invoked when a Process finishes. Takes the Process as argument.
     */
    public function runParallel(array $processes, int $maxParallel, int $poll = 1000, callable $callback = null, callable $checkCallback = null, callable $finishCallback = null): void
    {
        $this->validateProcesses($processes);

        // do not modify the object pointers in the argument, copy to local working variable
        $processesQueue = $processes;

        // fix maxParallel to be max the number of processes or positive
        $maxParallel = min(abs($maxParallel), count($processesQueue));

        // get the first stack of processes to start at the same time
        /** @var Process[] $currentProcesses */
        $currentProcesses = array_splice($processesQueue, 0, $maxParallel);

        // start the initial stack of processes
        foreach ($currentProcesses as $process) {
            $process->start(function ($type, $buffer) use ($callback, $process) {
                if (null !== $callback && is_callable($callback)) {
                    $callback($type, $buffer, $process);
                }
            });
        }

        do {
            // wait for the given time
            usleep($poll);

            // remove all finished processes from the stack
            foreach ($currentProcesses as $index => $process) {
                if (!$process->isRunning()) {
                    if (null !== $finishCallback && is_callable($finishCallback)) {
                        $finishCallback($process);
                    }
                    unset($currentProcesses[$index]);

                    // directly add and start new process after the previous finished
                    if (count($processesQueue) > 0) {
                        $nextProcess = array_shift($processesQueue);
                        $nextProcess->start(function ($type, $buffer) use ($callback, $nextProcess) {
                            if (null !== $callback && is_callable($callback)) {
                                $callback($type, $buffer, $nextProcess);
                            }
                        });
                        $currentProcesses[] = $nextProcess;
                    }
                }
            }
            if (null !== $checkCallback && is_callable($checkCallback)) {
                $checkCallback();
            }
            // continue loop while there are processes being executed or waiting for execution
        } while (count($processesQueue) > 0 || count($currentProcesses) > 0);
    }

    /**
     * @param Process[] $processes
     */
    protected function validateProcesses(array $processes): void
    {
        if (empty($processes)) {
            throw new \InvalidArgumentException('Cannot run in parallel 0 commands');
        }

        foreach ($processes as $process) {
            if (!($process instanceof Process)) {
                throw new \InvalidArgumentException(sprintf(
                    'Process in array need to be instance of Symfony Process, %s given',
                    get_class($process)
                ));
            }
        }
    }
}
