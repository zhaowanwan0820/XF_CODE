<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/20
 * Time: 15:42
 * 风控事件类
 */

namespace core\event;
use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Risk;

class RiskEvent extends BaseEvent{
    private $data;
    private $type;
    public function __construct($data,$type=Risk::ASYNC) {
        $this->data = $data;
        $this->type=$type;
    }

    public function execute() {
        $auditType = isset($this->data['frms_audit_type'])?$this->data['frms_audit_type']:'';
        $url = Risk::getRequestUrl($auditType);
        Risk::request($url,$this->data,$this->type);
        return true;
    }

    public function alertMails(){
        return array('lvbaosong@ucfgroup.com');
    }
}