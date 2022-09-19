<?php
namespace core\service\deal\state;

use core\dao\deal\DealModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\dao\jobs\JobsModel;
use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\enum\UserEnum;
use core\service\deal\state\State;
use core\service\dealqueue\DealQueueService;
use core\service\msgbus\MsgbusService;
use core\service\project\ProjectService;
use core\dao\deal\DealLoanTypeModel;
use core\dao\dealqueue\DealQueueModel;
use libs\utils\Logger;
use core\service\contract\ContractService;
use core\service\coupon\CouponService;
use libs\sms\SmsServer;
use NCFGroup\Common\Library\Idworker;

/**
 *
 * Class FailState
 * @package core\service\deal\state
 */
class FailState extends State{

    public function work(StateManager $sm) {
        $deal = $sm->getDeal();

        $startTrans = false;
        try {
            $GLOBALS['db']->startTrans();
            $startTrans = true;
            $function = 'core\service\deal\P2pDealCancelService::dealCancelRequest';
            $param = array('order_id'=>Idworker::instance()->getId(),'deal_id' => $deal->id);
            $jm = new JobsModel();
            $jm->priority = JobsEnum::JOBS_PRIORITY_DEAL_FAIL;
            $res = JobsModel::instance()->addJob($function, $param);
            if(!$res){
                throw new \Exception("流标jobs添加失败 dealId:".$deal->id);
            }
            $deal->deal_status = DealEnum::DEAL_STATUS_FAIL;
            $deal->is_doing = DealEnum::DEAL_IS_DOING_YES;
            $res = $deal->save();
            if(!$res){
                throw new \Exception("流标状态更改失败");
            }
            $message = array('dealId'=>$deal->id);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_FAIL,$message);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . "," .__FUNCTION__ . ",line:" . __LINE__ ."," . $ex->getMessage());
            $startTrans && $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }

    public static  function sendSmsToLoader($load_list, $arr_user, $deal) {
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
                            if ($arr_user[$user_id]['user_type'] ==UserEnum::USER_TYPE_ENTERPRISE)
                            {
                                $_mobile = 'enterprise';
                                $accountTitle = get_company_shortname($user_id); // by fanjingwen
                            } else {
                                $_mobile = $arr_user[$user_id]['mobile'];
                                $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
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

        // 更新项目  已上标金额等
        if($deal['project_id'] > 0) {
            $deal_pro_service = new ProjectService();
            $deal_pro_service->updateProBorrowed($deal['project_id']);
            $deal_pro_service->updateProLoaned($deal['project_id']);
        }
    }
}
