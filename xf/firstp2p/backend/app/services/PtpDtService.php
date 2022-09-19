<?php
namespace NCFGroup\Ptp\services;

use libs\sms\SmsServer;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\dao\UserModel;
use core\service\DealService;
use libs\utils\Site;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

/**
 * 多投通用服务
 * Class PtpDtService
 * @package NCFGroup\Ptp\services
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
class PtpDtService extends ServiceBase {

    public function publishNotify(RequestCommon $request) {
        $vars = $request->getVars();
        $userId = intval($vars['userId']);//用户Id

        //发送短信通知
        if (app_conf("SMS_ON")==1){
            $user_model = new UserModel();
            $user = $user_model->find($userId);
            $res = SmsServer::instance()->send($user['mobile'], 'TPL_SMS_DTB_PUBLISH', array(), $user['id']);
            if(empty($res)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 通知借款人投资人变动
     * @param RequestCommon $request
     * @return bool
     */
    public function publishNotifyBorrower(RequestCommon $request) {
        $vars = $request->getVars();
        $p2pDealId = intval($vars['p2pDealId']);//p2p标的Id

        $dealService = new DealService();
        $p2pDealInfo = $dealService->getDeal($p2pDealId);
        if(empty($p2pDealInfo)) { //参数不对
            return false;
        }
        $mailTitle = "债权转让通知";
        $mailContent = '您借款的“'.$p2pDealInfo['name'].'”项目发生债权转让，您的到期还款届时将直接还款至最新债权持有人，请您到电脑端“我的账户-标的还款计划”处查看该项目的最新债权持有人及相关信息。';
        $msgbox = new MsgBoxService();
        $msgbox->create($p2pDealInfo['user_id'], MsgBoxEnum::TYPE_DUOTOU_LOAN_USER_CHANGED, $mailTitle, $mailContent);
        return true;
    }
}
