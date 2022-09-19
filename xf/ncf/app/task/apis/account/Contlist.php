<?php
/**
 * 合同中心-合同列表
 *
 * @date 2018年8月20日14:46:30
 */

namespace task\apis\account;


use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\deal\DealAgencyService;
use core\service\contract\ContractNewService;
use core\service\contract\ContractSignService;

class Contlist extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $id = $deal_id = intval($params['dealId']);
        $user_id = intval($params['userId']);
        $role = intval($params['role']);
        $page_num = intval($params['pageNum']) <= 0 ? 1 : intval($params['pageNum']); // 第p页

        $deal_service = new DealService();
        $deal = $deal_service->getDeal($deal_id, true, true, true);
        if (empty($deal)) {
            $this->json_data = array();
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '标的信息不存在' )));
            return;
        }
        $deal['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        $deal['borrow_amount'] = format_price($deal['borrow_amount'] / 10000, false);
        // 用户信息
        $deal_user_info = UserService::getUserById($deal['user_id'], 'id,user_name');
        if (empty($deal_user_info)) {
            $this->json_data = array();
            Logger::error(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params), '用户信息不存在' )));
            return;
        }
        $deal['user_real_name'] = UserService::getFormatUserName($deal['user_id']);

        //用户角色（借款人出借人、担保公司）
        $user_info = array('id' => $user_id, 'user_name' => $deal_user_info['user_name']);
        $deal_agency_service = new DealAgencyService();
        $user_agency_info = $deal_agency_service->getUserAgencyInfoNew($user_info);
        $agency_info = $user_agency_info['agency_info'];
        $is_agency = intval($user_agency_info['is_agency']);

        //用户角色（资产管理方）
        if (((substr($deal['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'], 0, 5)) === 'NQYZR') OR (is_numeric($deal['contract_tpl_type']))) {
            $user_advisory_info = $deal_agency_service->getUserAdvisoryInfo($user_info);
            $advisory_info = $user_advisory_info['advisory_info'];
            $is_advisory = intval($user_advisory_info['is_advisory']);
        }
        $user_entrust_info = $deal_agency_service->getUserEntrustInfo($user_info);
        $entrust_info = $user_entrust_info['entrust_info'];
        $is_entrust = intval($user_entrust_info['is_entrust']);
        $user_canal_info = $deal_agency_service->getUserCanalInfo($user_info);
        $canal_info = $user_canal_info['canal_info'];
        $is_canal = intval($user_canal_info['is_canal']);

        $is_borrower = ($user_id == $deal['user_id']) ? 1 : 0;

        //合同列表
        if (($role == 1) && ($is_borrower)) {
            $params = array($user_id, $id, 1, $page_num);
        } else if (($role == 3) && ($is_agency)) {
            $params = array(0, $id, 2, $page_num, 10, $agency_info['id']);
        } else if (($role == 4) && ($is_advisory)) {
            $params = array(0, $id, 3, $page_num, 10, $advisory_info['id']);
        } else if (($role == 5) && ($is_entrust)) {
            $params = array(0, $id, 4, $page_num, 10, $entrust_info['id']);
        } else if (($role == 6) && ($is_canal)) {
            $params = array(0, $id, 5, $page_num, 10, $canal_info['id']);
        } else {
            $params = array($user_id, $id, 0, $page_num);
        }
        $isDt = $deal_service->isDealDT($deal['id']);
        $contract_new_service = new ContractNewService();
        if (is_numeric($deal['contract_tpl_type'])) {
            $result = $contract_new_service->getDealContList($params[0], $params[1], $params[2], $params[3], $params[4], $params[5]);
        } else {
            return app_redirect(url("index"));
        }
        //列表为空时，跳转到上级页面
        if ($result['count'] == 0) {
            $this->json_data = array();
            return;
        }

        foreach ($result['list'] as $k => $v) {
            if ($v['isDt'] <> 1) {
                $contract_sign_service = new ContractSignService();
                $tsaRet = $contract_sign_service->getSignedContractListByNum($v['number']);
                if (!empty($tsaRet) && !empty($tsaRet[0])) {
                    $result['list'][$k]['hasTsa'] = 1;
                    $result['list'][$k]['tsaInfo']['createTimeStr'] = date('Y-m-d H:i:s', $tsaRet[0]['create_time']);
                } else {
                    $result['list'][$k]['hasTsa'] = 0;
                }
            }
        }

        $is_have_sign = 1;//是否已经签署通过
        $sign_num = $result['count'];//已经签署条数
        if (($is_agency || $is_advisory || $is_borrower || $is_entrust || $is_canal) && ($role <> 2)) {
            if ($role == 3) {
                $params = array($deal_id, $user_id, $is_advisory, $result['count'], $advisory_info['agency_id']);
            } elseif ($role == 5) {
                $params = array($deal_id, $user_id, $is_entrust, $result['count'], $entrust_info['agency_id']);
            } elseif ($role == 6) {
                $params = array($deal_id, $user_id, $is_canal, $result['count'], $canal_info['agency_id']);
            } else {
                $params = array($deal_id, $user_id, $is_agency, $result['count'], $agency_info['agency_id']);
            }
            if (is_numeric($deal['contract_tpl_type'])) {
                if ($role == 1) {
                    $params = array($id, 1, $user_id);
                }
                if ($role == 3) {
                    $params = array($id, 2, $deal['agency_id']);
                }
                if ($role == 4) {
                    $params = array($id, 3, $deal['advisory_id']);
                }
                if ($role == 5) {
                    $params = array($id, 5, $deal['entrust_agency_id']);
                }
                if ($role == 6) {
                    $params = array($id, 6, $deal['canal_agency_id']);
                }
                $sign_info = $contract_new_service->getContSignNum($params[0],$params[1],$params[2]);
            } else {
                $this->json_data = array();
                return;
            }
            $is_have_sign = $sign_info['is_sign_all'];
            $sign_num = $sign_info['sign_num'];

            if (($sign_num > 0) && $isDt) {
                $sign_num = $result['count'];
                $is_have_sign = 1;
            }

        } else {
            $is_loan = true;
        }

        $result = array(
            "p" => $page_num,
            "deal" => $deal,
            "role" => $role,
            "sign_num" => $sign_num,
            "is_agency" => $is_agency,
            "is_advisory" => $is_advisory,
            "is_borrower" => $is_borrower,
            "is_loan" => $is_loan,
            "is_have_sign" => $is_have_sign,
            "contract" => $result['list'],
        );


        $this->json_data = $result;
    }

}
