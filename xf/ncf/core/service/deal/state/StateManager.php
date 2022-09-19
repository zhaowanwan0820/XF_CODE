<?php
namespace core\service\deal\state;

use core\service\deal\state\State;

class StateManager {

    protected $deal;

    protected $state;

    protected $errMsg;

    public $stateKey = array(
        0 => 'waiting',     //等待材料
        1 => 'progressing', //进行中
        2 => 'full' ,       //满标
        3 => 'fail',      //流标
        4 => 'repaying',    //还款中
        5 => 'repaid',      //已还清
        6 => 'reserving',   //预约投标中
    );

    public function __construct($deal){
        $this->deal = $deal;
        $this->state = $this->deal->deal_status;
    }

    public function getDeal(){
        return $this->deal;
    }

    public function setDeal($deal){
        $this->deal = $deal;
    }

    public function getData($key){
        return $this->{$key};
    }

    public function setData($key,$val){
        $this->{$key} = $val;
    }

    public function getErrMsg(){
        return $this->errMsg;
    }

    public function work(){
        $stateClass = '\core\service\deal\state\\' . ucfirst($this->stateKey[$this->deal->deal_status]) . 'State';
        if(class_exists($stateClass)){
            $stateObj = new $stateClass();
            $stateRes =  $stateObj->work($this);
            if(!$stateRes){
                $this->errMsg = $stateObj->getErrMsg();
            }
            return $stateRes;
        }else{
            throw new \Exception("Class {$stateClass} no exists");
        }
    }
}