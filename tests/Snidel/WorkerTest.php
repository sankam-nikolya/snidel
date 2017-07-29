<?php
use Ackintosh\Snidel\TestCase;
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Fork\Process;
use Ackintosh\Snidel\Task\Task;

class WorkerTest extends TestCase
{
    /** @var \Ackintosh\Snidel\Result\Queue */
    private $resultQueue;

    /** @var \Ackintosh\Snidel\Task\Queue */
    private $taskQueue;

    public function setUp()
    {
        parent::setUp();
        $this->worker = new Worker(new Process(getmypid()));
    }

    /**
     * @test
     */
    public function getPid()
    {
        $this->assertSame(getmypid(), $this->worker->getPid());
    }

    /**
     * @test
     */
    public function runTask()
    {
        $this->worker->setResultQueue($this->resultQueue);
        $this->worker->setTaskQueue($this->taskQueue);
        $this->taskQueue->enqueue($this->makeTask());

        $this->worker->run();

        $result = $this->resultQueue->dequeue();
        $this->assertSame('foo', $result->getReturn());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenExceptionOccurredInTask()
    {
        $this->worker = new Worker(new Process(getmypid()));
        $this->worker->setResultQueue($this->resultQueue);
        $this->worker->setTaskQueue($this->taskQueue);
        $task = new Task(
            function ($arg) {
                throw new RuntimeException('test');
            },
            'bar',
            null
        );
        $this->taskQueue->enqueue($task);

        $this->worker->run();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenExceptionOccurredInQueue()
    {
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($this->resultQueue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($this->resultQueue, $stat);

        $this->worker->setResultQueue($this->resultQueue);
        $this->worker->setTaskQueue($this->taskQueue);
        $this->taskQueue->enqueue($this->makeTask());
        $this->worker->run();
    }

    /**
     * @test
     */
    public function error()
    {
        $worker = ClassProxy::on(new Worker(new Process(getmypid())));
        $worker->setResultQueue($this->resultQueue);
        $worker->task = new Task(
            function ($arg) {
                return $arg;
            },
            'test',
            null
        );

        $worker->error();

        $result = $this->resultQueue->dequeue();
        $this->assertTrue($result->isFailure());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function errorThrowsException()
    {
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($this->resultQueue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($this->resultQueue, $stat);

        $worker = ClassProxy::on(new Worker(new Process(getmypid())));
        $worker->task = new Task(
            function ($arg) {
                return $arg;
            },
            'test',
            null
        );
        $worker->setResultQueue($this->resultQueue);
        $worker->error();
    }

    /**
     * @test
     */
    public function terminate()
    {
        $container = $this->makeForkContainer();
        $container->master = new Process(getmypid());
        $worker = $container->forkWorker();
        $worker->terminate(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($worker->getPid(), $status, WUNTRACED));
    }
}
