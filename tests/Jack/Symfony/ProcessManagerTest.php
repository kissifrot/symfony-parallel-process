<?php
namespace Jack\Symfony;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class ProcessManagerTest extends TestCase
{
    protected ProcessManager $processManager;

    public function setUp(): void
    {
        $this->processManager = new ProcessManager();
    }

    public function testRunParallelWithZeroProcesses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processManager->runParallel([], 0);
    }

    public function testRunParallelWithNonSymfonyProcess(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->processManager->runParallel(['ls -la'], 0);
    }

    public function testRunParallel(): void
    {
        $processes = array(
            new Process(['echo', 'foo']),
            new Process(['echo', 'bar']),
            new PhpProcess('<?php echo \'Hello World\'; ?>'),
        );
        $this->processManager->runParallel($processes, 2, 1000);

        $this->assertEquals('foo' . PHP_EOL, $processes[0]->getOutput());
        $this->assertEquals('bar' . PHP_EOL, $processes[1]->getOutput());
        $this->assertEquals('Hello World', $processes[2]->getOutput());
    }

    public function testRunParallelWithCallback(): void
    {
        $processes = array(
            new Process(['echo', 'foo']),
            new Process(['echo', 'bar']),
        );
        $calls = array();
        $this->processManager->runParallel($processes, 1, 1000, function ($type, $buffer, $process) use (&$calls) {
            $calls[] = array($type, $buffer, $process);
        });

        $this->assertSame(array(
            array(Process::OUT, 'foo' . PHP_EOL, $processes[0]),
            array(Process::OUT, 'bar' . PHP_EOL, $processes[1]),
        ), $calls);
    }
}
