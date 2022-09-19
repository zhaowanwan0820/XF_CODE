<?php
/**
 *
 */
namespace core\event\Gold;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\TaskService;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

use core\dao\UserModel;
use libs\utils\Site;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\sms\SmsServer;

// 放款时发送投资合并短信
class GoldDealLoansMsgEvent extends BaseEvent {
    private $_deal_id;

    public function __construct($deal_id) {
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        if (app_conf('SMS_ON') == 1) {
            //标的信息
            $request = new RequestCommon();
            $request->setVars(array("deal_id"=>$this->_deal_id));
            if(!isset($GLOBALS['goldRpc'])) {
                \libs\utils\PhalconRPCInject::init();
            }
            $response = $GLOBALS['goldRpc']->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\Deal',
                'method' => 'getDealById',
                'args' => $request,
            ));
            unset($request);
            if (empty($response['data'])){
                Logger::error(__CLASS__.' deal_id '.$this->_deal_id.' get goldrpc getDealById fail');
                return false;
            }


            // 标信息
            $deal_data = $response['data'];
            // 获取投资记录信息
            $request = new RequestCommon();
            $request->setVars(array("deal_id"=>$this->_deal_id));

            $response = $GLOBALS['goldRpc']->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\DealLoad',
                'method' => 'getConsolidatedInvestorByDealId',
                'args' => $request,
            ));
            if (empty($response['data'])){
                Logger::error(__CLASS__.' deal_id '.$this->_deal_id.' get goldrpc dealload fail');
                return false;
            }

            $deal_load_list = $response['data'];
            foreach($deal_load_list as $val2){
                // 针对单个标发送推送消息
                $content = sprintf('您购买的“%s”将于%s计算收益克重。', $deal_data['name'], date("m-d",$deal_data['repayStartTime']));
                $structured_content = array(
                    'main_content' => $content,
                    'turn_type' => 0, // 不显示下面标签
                );
                $msgbox = new MsgBoxService();
                $msgbox->create($val2['user_id'], MsgBoxEnum::TYPE_GOLD_DEAL_LOAN_TIPS, '黄金收益起始', $content, $structured_content);
            }

            foreach ($deal_load_list as $val) {
                // 显示保留3位小数如：20 显示20.000
                $buy_amount = floorfix($val['m'],3);

                $user = UserModel::instance()->find($val['user_id']);

                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                   // $accountTitle = get_company_shortname($user['id']); // by fanjingwen
                } else {
                    $_mobile = $user['mobile'];
                   // $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                }

                $sms_content = array(
                    'deal_name' => msubstr($deal_data['name'], 0, 9),
                    'buy_amount' => $buy_amount,
                    'cnt' => $val['c'],
                    'repay_start_time' => date("m-d",$deal_data['repayStartTime']), // 起息日只有天 没有时分秒
                );
                // 黄金增值克重短信模板
                $tpl = 'TPL_SMS_GOLD_DEAL_BID_MERGE';
                SmsServer::instance()->send($_mobile, $tpl, $sms_content, $val['user_id'], $val['site_id']);
            }
            return true;
        } else {
            return true;
        }
    }

    public function alertMails() {
        return array('zhaoxiaoan@ucfgroup.com','liangqiang@ucfgroup.com','wangzhen3@ucfgroup.com','gengkuan@ucfgroup.com');
    }
}
