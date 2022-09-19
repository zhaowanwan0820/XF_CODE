<?php
/**
 * 合同中心-获取该用户在此标的中是什么角色
 *
 * @date 2018年8月20日14:46:30
 */

namespace task\apis\account;


use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\deal\DealAgencyService;
use core\service\dealload\DealLoadService;

class ContractRole extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $deal_id = intval($params['dealId']);
        $user_id = intval($params['userId']);

        $deal_service = new DealService();
        $deal = $deal_service->getDeal($deal_id, true, false, true);
        if (empty($deal)) {
            $this->json_data = array();
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '标的信息不存在' )));
            return;
        }
        // 用户信息
        $deal_user_info = UserService::getUserById($deal['user_id'], 'id,user_name');
        if (empty($deal_user_info)) {
            $this->json_data = array();
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '用户信息不存在' )));
            return;
        }

        //用户角色（借款人、出借人、担保方，咨询方，委托方，渠道方）
        // 担保方
        $user_info = array('id' => $user_id, 'user_name' => $deal_user_info['user_name']);
        $deal_agency_service = new DealAgencyService();
        $user_agency_info = $deal_agency_service->getUserAgencyInfoNew($user_info);
        $agency_info = $user_agency_info['agency_info'];
        $is_agency = (($agency_info['id'] == $deal['agency_id']) && $user_agency_info['is_agency']) ? 1 : 0;

        // 用户角色（资产管理方） 咨询方
        $user_advisory_info = $deal_agency_service->getUserAdvisoryInfo($user_info);
        $advisory_info = $user_advisory_info['advisory_info'];
        $is_advisory = (($advisory_info['id'] == $deal['advisory_id']) && $user_advisory_info['is_advisory']) ? 1 : 0;

        // 委托方
        $user_entrust_info = $deal_agency_service->getUserEntrustInfo($user_info);
        $entrust_info = $user_entrust_info['entrust_info'];
        $is_entrust = (($entrust_info['id'] == $deal['entrust_agency_id']) && $user_entrust_info['is_entrust']) ? 1 : 0;

        // 渠道方
        $user_canal_info = $deal_agency_service->getUserCanalInfo($user_info);
        $canal_info = $user_canal_info['canal_info'];
        $is_canal = (($canal_info['id'] == $deal['canal_agency_id']) && $user_canal_info['is_canal']) ? 1 : 0;

        // 借款人
        $is_borrower = ($user_id == $deal['user_id']) ? 1 : 0;

        //投资者
        $dealLoads = (new DealLoadService())->getUserDealLoad($user_id, $deal_id);
        $is_loan = !empty($dealLoads) ? 1 : 0;
        $result = array(
            "is_agency" => $is_agency,
            "is_advisory" => $is_advisory,
            "is_borrower" => $is_borrower,
            "is_entrust" => $is_entrust,
            "is_canal" => $is_canal,
            "is_loan" => $is_loan,
        );

        $this->json_data = $result;
    }

}
