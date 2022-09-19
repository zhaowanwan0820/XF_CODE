<?php
namespace core\event;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\UserCompanyModel;
use core\dao\UserModel;
use core\dao\DealAgencyModel;

use core\service\UserService;
use libs\utils\Logger;
use libs\sms\SmsServer;

require_once APP_ROOT_PATH . 'system/libs/msgcenter.php';

/**
 * 收费后放款的标的更改为进行中时，触发此事件
 */
class ProcessingDealEvent extends BaseEvent
{
    private $_deal_id;

    public function __construct($deal_id)
    {
        $this->_deal_id = intval($deal_id);
    }

    public function execute()
    {
        $deal_info = DealModel::instance()->findViaSlave($this->_deal_id);
        $project_info = DealProjectModel::instance()->findViaSlave($deal_info['project_id']);
        $deal_advisory_info = DealAgencyModel::instance()->getDealAgencyById($deal_info->advisory_id);
        $user_info = UserModel::instance()->findViaSlave($deal_advisory_info->agency_user_id, 'id,mobile');
        $fee = DealModel::instance()->getAllFee($this->_deal_id);

        $borrowUserService = new UserService($deal_info['user_id']);
        if($borrowUserService->isEnterprise()){
            $enterprise_info = $borrowUserService->getEnterpriseInfo(true);
            $borrowName = $enterprise_info['company_name'];
        }else{
            $company_info = UserCompanyModel::instance()->findByUserId($deal_info['user_id']);
            $borrowName = $company_info['name'];
        }

        // 向咨询方发送手续费总金额的通知
        $params = array($deal_info->name, format_price(array_sum($fee)), $deal_info->jys_record_number,$project_info['name'],$borrowName);
        //签名 网信
        SmsServer::instance()->send($user_info->mobile, 'TPL_SMS_DEAL_CHANGE_FEE', $params, $user_info->id,1);
        Logger::info(sprintf('send fee-msg-event success,deal_id:%d, params:%s [%s:%s]', $this->_deal_id, json_encode($params), __FILE__, __LINE__));
        return true;
    }

    public function alertMails()
    {
        return array('fanjingwen@ucfgroup.com');
    }
}
