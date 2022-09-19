<?php
/**
 *
 */
namespace core\event\Gold;

use core\event\Gold\GoldMsgEvent;

\FP::import("libs.libs.msgcenter");

//流标发送短信
class GoldFailDealMsgEvent extends GoldMsgEvent {

    const TPLNAME = 'TPL_SMS_GOLD_FAIL_DEAL';
    public function __construct() {
        parent::__construct();
    }

    /**
     * dealName 标名称
     * money 返回多少钱
     */
    public function setMsgList($msg){
        $this->msgList[] = array(
                'userId'=>$msg['userId'],
                'content'=>array('deal_name'=>msubstr($msg['dealName'], 0, 9),'money'=>$msg['money']),
                'tplName'=>self::TPLNAME
        );
    }

}
