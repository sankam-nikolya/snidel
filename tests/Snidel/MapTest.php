<?php
class Snidel_MapTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->map = new Snidel_Map('echo', 5);
    }

    /**
     * @test
     */
    public function getCallable()
    {
        // $map = new Snidel_Map('echo', 5);
        $this->map = new Snidel_Map('echo', 5);
        $this->assertSame('echo', $this->map->getCallable());
    }

    /**
     * @test
     */
    public function getToken()
    {
        // $map = new Snidel_Map('echo', 5);
        $this->assertInstanceOf('Snidel_Token', $this->map->getToken());
    }

    /**
     * @test
     */
    public function childPid()
    {
        $this->map->addChildPid(getmypid());
        $this->assertSame(array(getmypid()), $this->map->getChildPids());
    }

    /**
     * @test
     */
    public function hasChild()
    {
        $this->map->addChildPid(getmypid());
        $this->assertTrue($this->map->hasChild(getmypid()));
        $this->assertFalse($this->map->hasChild(getmypid() + 1));
    }

    /**
     * @test
     */
    public function isProcessing()
    {
        $this->assertTrue($this->map->isProcessing());

        $this->map->countTheForked();
        $this->assertTrue($this->map->isProcessing());

        $this->map->countTheCompleted();
        $this->assertFalse($this->map->isProcessing());
    }
}