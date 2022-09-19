<?php
namespace task\controllers\dealprogressing;

use core\dao\deal\DealAgencyModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealModel;
use core\dao\project\DealProjectModel;
use core\enum\DealExtEnum;
use core\enum\UserEnum;
use core\service\user\UserService;
use libs\sms\SmsServer;
use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;


class NotifyConsult extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            $dealId = $params['dealId'];
            $deal = DealModel::instance()->getDealInfoViaSlave($dealId);
            $dealExt = DealExtModel::instance()->getDealExtByDealId($dealId);

            // 如果是 收费后放款，就给借款人发短息 --此处待定
            if($dealExt->loan_type == DealExtEnum::LOAN_AFTER_CHARGE){
                $project_info = DealProjectModel::instance()->findViaSlave($deal['project_id']);
                $deal_advisory_info = DealAgencyModel::instance()->getDealAgencyById($deal->advisory_id);

                $user_info = UserService::getUserById($deal_advisory_info->agency_user_id);
                $fee = DealModel::instance()->getAllFee($dealId);

                $borrowUserService = UserService::getUserById($deal['user_id']);
                if($borrowUserService['user_type'] == 1){
                    $enterprise_info = UserService::getEnterpriseInfo($deal['user_id']);
                    $borrowName = $enterprise_info['company_name'];
                }else{
                    $company_info = UserService::getUserCompanyInfo($deal['user_id']);
                    $borrowName = $company_info['name'];
                }

                // 向咨询方发送手续费总金额的通知
                $params = array($deal->name, format_price(array_sum($fee)), '',$project_info['name'],$borrowName);
                //签名 网信
                SmsServer::instance()->send($user_info['mobile'], 'TPL_SMS_DEAL_CHANGE_FEE', $params, $user_info['id'],1);
                Logger::info(sprintf('send fee-msg-event success,deal_id:%d, params:%s [%s:%s]', $dealId, json_encode($params), __FILE__, __LINE__));
                return true;
            }
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}