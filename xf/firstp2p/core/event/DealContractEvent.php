<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\service\ContractService;

class DealContractEvent extends BaseEvent {
    private $_deal_id;
    private $_user_id;
    private $_is_agency;

    public function __construct($deal_id, $user_id, $is_agency=0) {
        $this->_deal_id = $deal_id;
        $this->_user_id = $user_id;
        $this->_is_agency = $is_agency;
    }

    public function execute() {
        $contract_service = new ContractService();
        $res = $contract_service->signDealContNew($this->_deal_id, $this->_user_id, $this->_is_agency);
        $this->contractLog($res);
        if($res == false){
            throw new \Exception("更新合同签署状态是败：{$this->_deal_id} | {$this->_user_id} | {$this->_is_agency}");
        }
        return true;
    }

    public function contractLog($res) {
        Logger::wLog("DealContractEvent:{$this->_deal_id} | {$this->_user_id} | {$this->_is_agency} | {$res}" , Logger::INFO);
    }

    public function alertMails() {
        return array('wangyiming@ucfgroup.com');
    }
}
