<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\db\MysqlDb;
use core\event\BaseEvent;
use core\service\RiskCheckService;

/**
 * RiskCheckEvent
 * 同盾风险决策
 * @uses AsyncEvent
 * @package default
 */
class RiskCheckEvent extends BaseEvent
{
    private $_data = array();

    public function __construct($data) {
        $this->_data = $data;
    }

    public function execute() {
        $params_list = $this->_data;
        $riskObj = new RiskCheckService();
        $riskObj->insertRiskLog($params_list);
        unset($riskObj);
        return true;
    }

    public function alertMails(){
        return array('liuzhenpeng@ucfgroup.com');
    }
}

