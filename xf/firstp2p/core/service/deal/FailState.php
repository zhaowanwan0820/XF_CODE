<?php
namespace core\service\deal;

use core\dao\JobsModel;
use core\service\CouponLogService;
use core\service\DealService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\SendContractEvent;
use core\event\DealFailCheckerEvent;
use Thrift\StringFunc\core;
use core\service\ContractService;
use core\service\CouponService;
use core\service\ChannelFeeService;
use core\service\DealProjectService;
use libs\sms\SmsServer;

/**
 * FailState
 * 流标状态进行的操作
 *
 * @package
 * @version $id$
 */
class FailState extends State{

    function work($sm) {
        $this->deal = $deal = $sm->getDeal();
        $deal_model = $sm->getDealModel();
        if ($this->checkIsFail()) {
            try {
                $deal_id = $deal['id'];
                $result = $deal_model->failDeal($deal);
                if ($result === false) {
                    $this->sendWarnEmail($deal);
                    return false;
                }
                return true;
            } catch (\Exception $e) {
                \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "{$deal_id} ".$e->getMessage()."\n")));
                throw new \Exception($e->getMessage());
            }
        } else{
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "{$deal_id} 标的状态不一致 不能进行流标操作！\n")));
            throw new \Exception('check任务注册失败');
        }

        return false;
    }

    private function sendWarnEmail($deal) {
        // 如果处理过程失败，则发报警邮件
        $msgcenter = new \msgcenter();
        \FP::import("libs.common.dict");
        $email_arr = \dict::get("MSG_WARN_EMAIL");// @todo 后台最好配上。
        if($email_arr) {
            $content = "流标处理失败，请检查投资人账户信息。借款id：{$deal['id']}，借款标题：{$deal['name']}。时间：" . date("Y-m-d H:i:s", get_gmtime());
            foreach ($email_arr as $email) {
                $msgcenter->setMsg($email, 0, $content, false, "流标处理失败");
            }
            $msgcenter->save();
        }
        \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "{$deal_id} deal_model处理流标失败\n")));
    }

    /**
     * checkIsFail
     * 检查是否符合流标状态
     *
     * @access public
     * @return void
     */
    public function checkIsFail() {
        if ($this->deal['is_doing'] == 1 && $this->deal['deal_status'] == 3) {
            return true;
        }
        return false;
    }

    static public function sendSmsToLoader($load_list, $arr_user, $deal) {
        // 投资人短信迁移到此处
        try{
            if(app_conf("SMS_ON") == 1){
                if (count($load_list) > 0) {
                   // require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
                   // $msgcenter = new \Msgcenter();

                    foreach ($load_list as $v) {
                        $user_id = $v['user_id'];
                        if (isset($arr_user[$user_id])) {
                            /*author:liuzhenpeng, modify:系统触发短信签名, date:2015-10-28*/
                            $site_domain = array_search($v['site_id'], $GLOBALS['sys_config']['TEMPLATE_LIST']);
                            $site_domain = ($site_domain == false) ? 'firstp2p' : $site_domain;
                            $site_name   = $GLOBALS['sys_config']['SITE_LIST_TITLE'][$site_domain];
                            // SMSSend 投资项目流标给出借人短信
                            if ($arr_user[$user_id]['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                            {
                                $_mobile = 'enterprise';
                                $accountTitle = get_company_shortname($user_id); // by fanjingwen
                            } else {
                                $_mobile = $arr_user[$user_id]['mobile'];
                                $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                            }
                            $params = array(
                                    'account_title' => $accountTitle,
                                    'title' => msubstr($deal['name'], 0, 9),
                                    'money' => format_price($v['money']),
                                );
                            SmsServer::instance()->send($_mobile, 'TPL_SMS_DEAL_FAILD_NEW', $params,$user_id,$v['site_id']);
                            unset($site_domain, $site_name);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $e->getMessage()."\n")));
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * afterMoney
     * 处理完投资者的钱之后的操作  如  删除合同  邀请返利之类的
     *
     * @param mixed $deal_id
     * @access public
     * @return void
     */
    public function afterMoney($deal) {
        $deal_id = $deal['id'];
        //删除相关合同
        $cont_service = new ContractService();
        $cont_service->delContByDeal($deal_id);

        //自动结算优惠券及邀请返利
        $coupon = new CouponService();
        $coupon->updateLogStatusByDealId($deal_id, 2);
        $channelFeeService = new ChannelFeeService();
        $channelFeeService->update_deal_channel_log_status($deal_id, 2);

        // 更新项目  已上标金额等
        if($deal['project_id'] > 0) {
            $deal_pro_service = new DealProjectService();
            $deal_pro_service->updateProBorrowed($deal['project_id']);
            $deal_pro_service->updateProLoaned($deal['project_id']);
        }
    }

}
?>
