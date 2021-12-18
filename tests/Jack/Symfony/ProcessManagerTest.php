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
}
