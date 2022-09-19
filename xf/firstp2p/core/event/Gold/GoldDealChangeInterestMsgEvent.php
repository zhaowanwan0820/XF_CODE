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
class GoldDealChangeInterestMsgEvent extends BaseEvent {
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
                'service' => 'NCFGroup\Gold\Services\DealRepay',
                'method' => 'getUserRepayInfo',
                'args' => $request,
            ));
            if (empty($response['data'])){
                Logger::error(__CLASS__.' deal_id '.$this->_deal_id.' get goldrpc dealload fail');
                return false;
            }
            $deal_load_list = $response['data'];
            foreach($deal_load_list as $val2){
                // 针对单个标发送推送消息
                $content = sprintf("项目：%s(共%s次)\n已购克重：%s克\n收益克重：%s克\n已转入优金宝", $deal_data['name'], $val2['sum'],number_format($val2['money'],3),number_format($val2['repay_money'],3));
                $structured_content = array(
                    'main_content' => $content,
                    'turn_type' => 0, // 不显示下面标签
                );
                $msgbox = new MsgBoxService();
                $msgbox->create($val2['loan_user_id'],MsgBoxEnum::TYPE_GOLD_DEAL_REPAY_TIPS, '黄金到期', $content, $structured_content);
            }

            foreach ($deal_load_list as $val) {
                // 显示保留3位小数如：20 显示20.000
                $buy_amount = floorfix($val['money'],3);
                $repay_money = floorfix($val['repay_money'],3);
                $sum_money = floorfix($val['repay_money']+$val['money'],3);
                $user = UserModel::instance()->find($val['loan_user_id']);

                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                } else {
                    $_mobile = $user['mobile'];
                }
                $sms_content = array(
                    'deal_name' => msubstr($deal_data['name'], 0, 9),
                    'sum_money' =>$sum_money,
                    'sum' => $val['sum'],
                    'buy_amount' => $buy_amount,
                    'repay_money' => $repay_money,
                );
                // 黄金增值克重短信模板
                $tpl = 'TPL_SMS_GOLD_DEAL_REPAY_MERGE';
                SmsServer::instance()->send($_mobile, $tpl, $sms_content, $val['loan_user_id'], $val['site_id']);
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
