<?php
namespace libs\event;

class TestEvent implements AsyncEvent
{
    public $key = null;
    public $successFul = null;

    public function __construct($key, $successFul = true)
    {
        $this->key = $key;
        $this->successFul = $successFul;
    }

    public function execute() 
    {
        if(!$this->successFul) {
            throw new \Exception('jx exception');
        }

        return true;
    }
}
