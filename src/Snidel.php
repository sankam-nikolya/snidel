<?php
class Snidel
{
    /**
     * @var array
     */
    private $childPids;

    /**
     * @var Snidel_Token
     */
    private $token;

    /**
     * @var bool
     */
    private $joined = false;

    /**
     * @var array
     */
    private $results = array();

    /**
     * @var array
     */
    private $tagsToPids = array();

    public function __construct($maxProcs = 5)
    {
        $this->ownerPid = getmypid();
        $this->childPids = array();
        $this->token = new Snidel_Token(getmypid(), $maxProcs);
    }

    /**
     * fork process
     *
     * @param   callable    $callable
     * @param   array       $args
     * @param   string      $tag
     * @return  void
     * @throws  RuntimeException
     */
    public function fork($callable, $args = array(), $tag = null)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new RuntimeException('Failed to fork');
        } elseif ($pid) {
            // parent
            $this->childPids[] = $pid;
            if ($tag) {
                $this->tagsToPids[$tag][] = $pid;
            }
        } else {
            // child
            if ($this->token->accept()) {
                $childPid = getmypid();
                $ret = call_user_func_array($callable, $args);
                $data = new Snidel_Data($childPid);
                $data->write($ret);
                $this->token->back();
            }
            exit;
        }
    }

    /**
     * waits until all children are completed
     *
     * @return  void
     */
    public function wait()
    {
        if ($this->joined) {
            return;
        }

        $count = count($this->childPids);
        for ($i = 0; $i < $count; $i++) {
            $childPid = pcntl_waitpid(-1, $status);
            if (!pcntl_wifexited($status)) {
                throw new RuntimeException('error in child.');
            }
            $data = new Snidel_Data($childPid);
            $this->results[$childPid] = $data->readAndDelete();
        }
        $this->joined = true;
    }

    /**
     * gets results
     *
     * @param   string  $tag
     * @return  array   $ret
     * @throws  RuntimeException
     */
    public function get($tag = null)
    {
        if (!$this->joined) {
            $this->wait();
        }

        if ($tag === null) {
            return array_values($this->results);
        } else {
            return $this->getWithTag($tag);
        }
    }

    /**
     * gets results with tag
     *
     * @param   string  $tag
     * @return  array   $results
     */
    private function getWithTag($tag)
    {
        $results = array();
        if (!isset($this->tagsToPids[$tag])) {
            return $results;
        }

        foreach ($this->tagsToPids[$tag] as $pid) {
            $results[] = $this->results[$pid];
        }

        return $results;
    }

    public function __destruct()
    {
        if ($this->ownerPid === getmypid() && $this->joined === false) {
            throw new RuntimeException('must be joined');
        }
    }
}
