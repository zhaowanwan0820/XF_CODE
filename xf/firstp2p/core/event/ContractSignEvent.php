<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\service\ContractSignService;


class ContractSignEvent extends BaseEvent
{
    private $_contractId;
    private $_dealId;
    private $_type;
    private $_projectId;
    //private $_needTsa;


    public function __construct($contractId,$dealId=null,$type=0,$projectId=0 /*,$needTsa=false*/) {
        $this->_contractId = $contractId;
        if($dealId){
            $this->_dealId = $dealId;
        }
        $this->_type = $type;
        $this->_projectId = $projectId;
        //$this->_needTsa = $needTsa;
    }

    public function execute() {
        $contractSign = new ContractSignService();

        try {
            if($this->_type == 1){
                $contractSign->signOneContract($this->_contractId,true,$this->_dealId,$this->_type,$this->_projectId);
            }else{
                $contractSign->signOneContract($this->_contractId,true,$this->_dealId);
            }
        } catch (\Exception $e) {
            $this->contractSignLog(sprintf("合同签署失败，errorMsg:%s contractId:%d", $e->getMessage(), $this->_contractId));
            \libs\utils\Alarm::push('tsacheck', 'tsacheck 时间戳合同生成失败', sprintf("签署失败，合同ID:%s,标的id：%s 错误:%s",$this->_contractId,$this->_dealId,$e->getMessage()));
            return false;
        }

        return true;
    }

    public function contractSignLog($str) {
        Logger::wLog("event: " . $str . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH. "/logger/" ."contractSign_" . date('Y_m_d') .'.log');
    }

    public function alertMails() {
        return array('wangfei5@ucfgroup.com');
    }
}
