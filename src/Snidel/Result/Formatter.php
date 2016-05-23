<?php
namespace Ackintosh\Snidel\Result;

use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Fork\Formatter as ForkFormatter;
use Ackintosh\Snidel\Task\Formatter as TaskFormatter;

class Formatter
{
    public static function serialize(Result $result)
    {
        $cloned = clone $result;
        $serializedTask = TaskFormatter::serialize($cloned->getTask());
        $serializedFork = ForkFormatter::serialize($cloned->getFork());
        $cloned->setTask(null);
        $cloned->setFork(null);

        return serialize(array(
            'serializedTask'     => $serializedTask,
            'serializedFork'    => $serializedFork,
            'result'            => $cloned,
        ));
    }

    public static function minifyAndSerialize(Result $result)
    {
        $cloned = clone $result;

        $serializedTask = TaskFormatter::minifyAndSerialize($cloned->getTask());
        $serializedFork = ForkFormatter::serialize($cloned->getFork());
        $cloned->setTask(null);
        $cloned->setFork(null);

        return serialize(array(
            'serializedTask'     => $serializedTask,
            'serializedFork'    => $serializedFork,
            'result'            => $cloned,
        ));
    }

    public static function unserialize($serializedResult)
    {
        $unserialized = unserialize($serializedResult);
        $unserialized['result']->setTask(TaskFormatter::unserialize($unserialized['serializedTask']));
        $unserialized['result']->setFork(ForkFormatter::unserialize($unserialized['serializedFork']));

        return $unserialized['result'];
    }
}