<?php
/**
 * 合同中心-签署全部合同
 *
 * @date 2018年8月20日14:46:30
 */

namespace task\apis\account;

use libs\utils\Logger;
use core\enum\contract\ContractServiceEnum;
use task\lib\ApiAction;
use core\service\deal\DealAgencyService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\contract\ContractNewService;
use core\service\contract\ContractViewerService;
use core\service\contract\ContractUtilsService;

class Contsignajax extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $role = intval($params['role']);
        $deal_id = intval($params['deal_id']);
        $user_id = intval($params['user_id']);

        // 1 校验输入参数
        if($role <= 0 || $deal_id <= 0 || $user_id <= 0){
            $this->json_data = false;
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '参数错误' )));
            return ;
        }

        $deal_info = (new DealService())->getDealInfo($deal_id);
        if (empty($deal_info)) {
            $this->json_data = false;
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '标的信息不存在错误' )));
            return;
        }
        // 2 判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
        // 用户信息
        $deal_user_info = UserService::getUserById($user_id, 'id,user_name');
        if(empty($deal_user_info)){
            $this->json_data = false;
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '用户信息不存在' )));
            return;
        }
        $params = array('id' => $user_id, 'user_name' => $deal_user_info['user_name']);
        $agencyService = new DealAgencyService();
        $user_role = $agencyService->getUserAgencyInfoNew($params);
        $advisory_role = $agencyService->getUserAdvisoryInfo($params);
        $entrust_role = $agencyService->getUserEntrustInfo($params);
        $canal_role = $agencyService->getUserCanalInfo($params);
        $is_agency = intval($user_role['is_agency']);
        $is_advisory = intval($advisory_role['is_advisory']);
        $is_entrust = intval($entrust_role['is_entrust']);
        $is_canal = intval($canal_role['is_canal']);
        $is_borrower = ($user_id == $deal_info['user_id']) ? 1 : 0;

        // 3 生成签署jobs
        if (is_numeric($deal_info['contract_tpl_type'])) {
            if (($role == 1) && ($is_borrower)) {
                $signRole = 1;
            }
            if (($role == 3) && ($is_agency)) {
                $signRole = 2;
            }
            if (($role == 4) && ($is_advisory)) {
                $signRole = 3;
            }
            if (($role == 5) && ($is_entrust)) {
                $signRole = 5;
            }
            if (($role == 6) && ($is_canal)) {
                $signRole = 6;
            }
            try{
                $sign_info = (new ContractNewService())->signAll($deal_id, $signRole, $user_id);
            }catch(\Exception $e){
                Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($e->getMessage()))));
            }

        }
        $this->json_data = $sign_info;
    }

}
