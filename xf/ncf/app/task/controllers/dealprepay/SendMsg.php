<?php
namespace task\controllers\dealprepay;

use core\dao\deal\DealModel;
use core\dao\repay\DealPrepayModel;
use core\service\msgbox\MsgboxService;
use core\service\user\UserService;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\repay\DealPrepayMsgService;

/**
 * 提前还款完成--给投资人发送回款站内信
 * Class SendNotify
 * @package task\controllers\dealcreate
 */

class SendMsg extends BaseAction {

    public function invoke() {
        return true;
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $repayId = $params['repayId'];

            $deal = DealModel::instance()->getDealInfo($dealId,true);
            $prepay = DealPrepayModel::instance()->findViaSlave($repayId);

            // 给借款人发送站内信
            $user = UserService::getUserById($deal['user_id']);
            $content = "尊敬的客户，" . $deal['name'] . " 您的提前还款申请已通过审核，本次借款已全部还清。";

            $msgbox = new MsgboxService();
            $msgbox->create($prepay['user_id'], 8, '回款', $content);

            // 给投资人发送站内信
            DealPrepayMsgService::sendMsgBox($dealId,$repayId);
            Logger::info("Task dealprepay SendMsg dealId:{$dealId},repayId:{$repayId}");
        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
