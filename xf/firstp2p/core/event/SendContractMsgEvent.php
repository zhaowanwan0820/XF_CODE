<?php
namespace core\event;
use core\event\BaseEvent;

/**
 * 合同签署完成之后的发送通知邮件
 */
class SendContractMsgEvent extends BaseEvent
{

    private $deal_id;
    //$type(0:老版本,1:合同服务)
    private $tpye;

    public function __construct ($deal_id,$type=0)
    {
        $this->deal_id = $deal_id;
        $this->type = $type;
    }

    public function execute ()
    {
        if($this->type === 0){
            $send_res = send_contract_sign_email($this->deal_id);
        }else{
            $send_res = send_new_contract_sign_email($this->deal_id);
        }

        if ($send_res) {
            return true;
        }
        return false;
    }

    public function alertMails ()
    {
        return array();
    }
}

