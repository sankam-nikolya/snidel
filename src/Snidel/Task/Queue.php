<?php
namespace Ackintosh\Snidel\Task;

use Ackintosh\Snidel\AbstractQueue;
use Ackintosh\Snidel\Task\Formatter;

class Queue extends AbstractQueue
{
    /**
     * @param   \Ackintosh\Snidel\Task  $task
     * @return  void
     * @throws  RuntimeException
     */
    public function enqueue($task)
    {
        $this->queuedCount++;

        $serialized = Formatter::serialize($task);
        if ($this->isExceedsLimit($serialized)) {
            throw new \RuntimeException('the task exceeds the message queue limit.');
        }

        if (!$this->sendMessage($serialized)) {
            throw new \RuntimeException('failed to enqueue task.');
        }
    }

    /**
     * @return  \Ackintosh\Snidel\Task
     * @throws  \RuntimeException
     */
    public function dequeue()
    {
        $this->dequeuedCount++;
        try {
            $serializedTask = $this->receiveMessage();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('failed to dequeue task');
        }

        return Formatter::unserialize($serializedTask);
    }
}