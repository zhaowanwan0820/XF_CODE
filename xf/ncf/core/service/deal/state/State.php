<?php
namespace core\service\deal\state;

use core\service\deal\state\StateManager;

abstract class State{
    public $errMsg = '';
    public abstract function work(StateManager $sm);

    public function setErrMsg($errMsg){
        $this->errMsg = $errMsg;
    }

    public function getErrMsg(){
        return $this->errMsg;
    }
}
