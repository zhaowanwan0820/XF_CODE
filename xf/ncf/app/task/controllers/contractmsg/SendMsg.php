<?php
namespace task\controllers\contractmsg;


use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\enum\MsgBoxEnum;
use core\enum\DealLoadEnum;
use core\enum\UserEnum;
use core\service\msgbox\MsgboxService;
use libs\utils\Logger;
use core\enum\contract\ContractServiceEnum;
use task\controllers\BaseAction;
use core\service\repay\DealRepayMsgService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\contract\ContractService;
use core\service\email\SendEmailService;

/**
 * 合同签署完成后（还未打戳），发送邮件和站内信给各方
 * 发送邮件和站内信(投资人, 借款人)
 * 发送邮件(咨询机构, 担保机构)
 * 如果是智多鑫或者随鑫约，则不会发送
 * 给合同库中对应的合同记录标记为已发送状态
 * Class SendMsg
 * @package task\controllers\contractmsg
 */
class SendMsg extends BaseAction
{

    public function invoke()
    {
        return true;
        $params = json_decode($this->getParams(), true);
        try {
            Logger::info("Task contractmsg SendMsg receive params " . json_encode($params));
            $dealId = intval($params['dealId']);
            if ($dealId <= 0) {
                throw new \Exception('参数错误');
            }
            $dealService = new DealService();
            $deal = $deal = $dealService->getDeal($dealId);
            if (empty($deal)) {
                throw new \Exception('标的不存在');
            }
            //智多鑫不发送消息
            if ($dealService->isDealDT($dealId)) {
                Logger::info("Task contractmsg SendMsg dealId:{$dealId} 为智多鑫，不用发送站内信和邮件 ");
                return true;
            }

            $site_url = get_deal_domain($dealId);
            $contract_url = $site_url . "/account/contract";

            // 获取合同列表(网贷)
            $response = ContractService::getContractByDealId($dealId, null, '', ContractServiceEnum::SOURCE_TYPE_PH);

            if (empty($response['list'])) {
                $response = ContractService::getContractByDealId($dealId, null, '', ContractServiceEnum::SOURCE_TYPE_PH);
            }
            if (empty($response['list'])) {
                Logger::info("Task contractmsg SendMsg dealId:{$dealId} 没有合同 不发送站内信和邮件 ");
                return true;
            }
            $list = $response['list'];
            $contract['deal_name'] = get_deal_title($deal['name'], '', $dealId);
            $contract['title'] = '"' . $contract['deal_name'] . '"的合同已经下发';

            $users = array();
            $isSendMsgForUser = array();//记录是否下发合同消息
            foreach ($list as $one) {
                if ($one['user_id'] <> 0) {
                    if ($one['is_send'] == 0) {
                        $users[$one['user_id']] = $one['user_id'];

                        //预约投资不单独给用户下发合同消息，若用户同时存在普通投资和预约投资，默认下发合同消息
                        $dealLoad = DealLoadModel::instance()->find($one['deal_load_id']);
                        if (!isset($isSendMsgForUser[$one['user_id']]) || !$isSendMsgForUser[$one['user_id']]) {
                            $isSendMsgForUser[$one['user_id']] = $dealLoad['source_type'] == DealLoadEnum::$SOURCE_TYPE['reservation'] ? false : true;
                        }
                    }
                }
            }

            $users[$deal['user_id']] = $deal['user_id'];
            $msgbox = new MsgboxService();
            //投资人,借款人
            $user_id_collection = array();
            $emailDatas = array();
            foreach ($users as $user) {
                $user_info = UserService::getUserById($user, "id,user_type,user_name,email");
                if (isset($user_info['user_type']) && (int)$user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
                    $userName = $user_info['user_name'];
                } else {
                    $userName = UserService::getFormatUserName($user_info['id']);
                }
                // 邮件
                if (!empty($user_info['email'])) {
                    $notice_email = array(
                        'user_name' => $userName,
                        'deal_url' => $site_url . '/deal/' . $dealId,
                        'deal_name' => $contract['deal_name'],
                        'help_url' => $site_url . url("index", "helpcenter"),
                        'site_url' => $site_url,
                        'site_name' => app_conf("SHOP_TITLE"),
                        'msg_cof_setting_url' => $site_url . url("index", "uc_msg#setting"),
                        'contract_url' => $contract_url,
                    );
                    $emailDatas[] = array(
                        'userEmail' => $user_info['email'],//用户邮箱,必填
                        'userId' => $user,//用户ID,必填
                        'contentData' => $notice_email,//邮件内容,必填
                        'tplName' => 'TPL_SEND_CONTRACT_EMAIL',//邮件模板名
                        'title' => $contract['title'],//必填
                        'site' => get_deal_domain_title($dealId),
                        'data' => array(),
                    );
                }

                // 站内信 (预约投资不单独给用户下发合同消息)
                if ((!isset($isSendMsgForUser[$user]) || $isSendMsgForUser[$user]) && !in_array($user, $user_id_collection)) {
                    $content = sprintf('您投资的“%s”合同已下发。', $contract['deal_name']);
                    $msgbox->create($deal['user_id'], MsgBoxEnum::TYPE_CONTRACT_SEND, '', $content);
                }
            }

            // 合同回调发送状态
            $result = ContractService::sendContractStatus(intval($dealId), ContractServiceEnum::SOURCE_TYPE_PH);
            if ($result) {
                $emailResult = SendEmailService::batchSendEmail($emailDatas);
            } else {
                return false;
            }
            if (!$emailResult) {
                throw new \Exception("SendEmailService::batchSendEmail 调用失败");
            }
            Logger::info("Task dealprepay SendMsg dealId:{$dealId} success");
        } catch (\Exception $ex) {
            Logger::error(implode(',', array(__CLASS__, __FUNCTION__, __LINE__, " dealId:{$dealId}", $ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}