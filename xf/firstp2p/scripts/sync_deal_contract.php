<?php
/**
 * 同步所有标的合同
 */
require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");

use core\dao\DealModel;
use core\dao\ContractModel;
use core\dao\DealContractModel;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);

set_time_limit(0);
ini_set('memory_limit', '1024M');

class SyncDealContract {
    public function run_one($deal_id) {
        $deal_model = new DealModel();
        $deal = $deal_model->find($deal_id);
        if (!$deal || !in_array($deal['deal_status'], array(2,4,5))) {
            echo "wrong deal status\n";
            return false;
        }

        $deal_contract_model = new DealContractModel();
        $dc_list = $deal_contract_model->findAll("`deal_id`='{$deal_id}'");
        $rs = false;
        if ($deal['deal_status'] == 2) {
            if (!$dc_list) {
                $rs = $deal_contract_model->create($deal);
            }
                        
            $contract_model = new ContractModel();
            $contract_agency = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `agency_id`>0 LIMIT 1");
            if ($contract_agency['sign_time']) {
                $GLOBALS['db']->query("UPDATE firstp2p_deal_contract SET `status`='1', `sign_time`='{$contract_agency['sign_time']}' WHERE `deal_id`='{$deal_id}' AND `agency_id`>0");
            }

            $contract_borrower = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `user_id`='{$deal['user_id']}' LIMIT 1");
            if ($contract_borrower['sign_time']) {
                $GLOBALS['db']->query("UPDATE firstp2p_deal_contract SET `status`='1', `sign_time`='{$contract_borrower['sign_time']}' WHERE `deal_id`='{$deal_id}' AND `user_id`='{$deal['user_id']}'");
            }
        } else {
            if (!$dc_list) {
                $rs = $this->_create($deal);
            } else {
                foreach ($dc_list as $dc) {
                    if ($dc['agnecy_id']) {
                        $contract_agency = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `agency_id`>0 LIMIT 1");
                        $dc->status = 1;
                        $dc->sign_time = $contract_agency['sign_time'];
                        $rs = $dc->save();
                    } else {
                        $contract_borrower = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `user_id`='{$deal['user_id']}' LIMIT 1");
                        $dc->status = 1;
                        $dc->sign_time = $contract_borrower['sign_time'];
                        $rs = $dc->save();
                    }
                }
            }
        }

        if ($rs === false) {
            echo $deal_id . "fail\n";
        } else {
            echo $deal_id . "succ\n";
        }
    }

    public function run() {
        $deal_model = new DealModel();
        $deal_contract_model = new DealContractModel();
        $succ_cnt = 0;
        $fail_cnt = 0;
        $deal_list = $deal_model->findAll("`deal_status` IN (2,4,5) AND `is_effect`='1' AND `is_delete`='0'", true, 'id,deal_status,user_id,agency_id,contract_tpl_type', array());
        foreach ($deal_list as $deal) {
            $rs = false;
            if ($deal['deal_status'] == 2) {
                $rs = $deal_contract_model->create($deal);
            
                $contract_model = new ContractModel();
                $contract_agency = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `agency_id`>0 LIMIT 1");
                if ($contract_agency['sign_time']) {
                    $GLOBALS['db']->query("UPDATE firstp2p_deal_contract SET `status`='1', `sign_time`='{$contract_agency['sign_time']}' WHERE `deal_id`='{$deal['id']}' AND `agency_id`>0");
                }
 
                $contract_borrower = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `user_id`='{$deal['user_id']}' LIMIT 1");
                if ($contract_borrower['sign_time']) {
                    $GLOBALS['db']->query("UPDATE firstp2p_deal_contract SET `status`='1', `sign_time`='{$contract_borrower['sign_time']}' WHERE `deal_id`='{$deal['id']}' AND `user_id`='{$deal['user_id']}'");
                }
            } elseif ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
                $rs = $this->_create($deal);
            }

            if ($rs == true) {
                $succ_cnt++;
            } else {
                $fail_cnt++;
            }
        }

        echo "成功：{$succ_cnt}\n失败：{$fail_cnt}\n";
    }

    private function _create($deal) {
        if (!$deal || !$deal['id']) {
            return false;
        }

        $contract_model = new ContractModel();
        $obj = new DealContractModel();

        $log_arr = array(
            "deal_id" => $deal['id'],
        );

        $GLOBALS['db']->startTrans();

        try {
            // 借款人记录
            $contract_borrower = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `user_id`='{$deal['user_id']}' LIMIT 1");
            $obj->user_id = $deal['user_id'];
            $obj->deal_id = $deal['id'];
            $obj->create_time = time();
            $obj->contract_tpl_type = $deal['contract_tpl_type'];
            $obj->agency_id = 0;
            $obj->status = 1;
            $obj->sign_time = $contract_borrower['sign_time'];

            if ($obj->insert() === false) {
                throw new \Exception("insert borrow_user {$deal['deal_status']} contract fail");
            }

            // 担保机构记录
            $contract_agency = $contract_model->findBy("`deal_id`='{$deal['id']}' AND `agency_id`>0 LIMIT 1");
            $obj->user_id = 0;
            $obj->deal_id = $deal['id'];
            $obj->create_time = time();
            $obj->contract_tpl_type = $deal['contract_tpl_type'];
            $obj->agency_id = $deal['agency_id'];
            $obj->status = 1;
            $obj->sign_time = $contract_agency['sign_time'];

            if ($obj->insert() === false) {
                throw new \Exception("insert agency_user {$deal['deal_status']} contract fail");
            }

            $GLOBALS['db']->commit();
            Logger::wlog("create_deal_contract:" . json_encode($log_arr), Logger::INFO);
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $log_arr['msg'] = $e->getMessage();
            $content = "create_deal_contract:" . json_encode($log_arr);
            Logger::wlog($content, Logger::ERR);
            \libs\utils\Alarm::push('deal', '生成合同异常', $content);
            return false;
        }
        

    }
}

$deal_id = intval($argv[1]);
$obj = new SyncDealContract();
if ($deal_id) {
    $obj->run_one($deal_id);
} else {
    $obj->run();
}
