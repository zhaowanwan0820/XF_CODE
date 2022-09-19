<?php
/**
 * 线上汇赢1号3期 补发《委托担保合同》
 * 
 * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php hy12_repair.php
 * @author wenyanlei  2014-03-19
 */

require_once dirname(__FILE__).'/../app/init.php';

use app\models\dao\Deal;
use app\models\service\Earning;

set_time_limit(0);

//查询出所有汇赢1号3期的借款
$deal_all = Deal::instance()->findAll("contract_tpl_type = 'HY12' AND is_delete = 0");

$msg = '';

if($deal_all){
    
    FP::import("app.deal");
    FP::import("app.common");
    FP::import("libs.libs.update_contract");
    
    foreach ($deal_all as $deal_one){
        $contract_all = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."contract WHERE deal_id = ".$deal_one['id']." AND type = 2");
        
        if($contract_all){
            
            foreach ($contract_all as $cont_one){
                $deal = get_deal($deal_one['id']);
                
                if($deal){
                    
                    $contractModule = new updateContract();  //引入合同操作类
                    $borrow_user_info = get_deal_borrow_info($deal); //借款人 或公司信息
                    $agency_info = get_agency_info($deal_one['agency_id']);//担保公司信息
                    
                    //获取保证人列表
                    $guarantor_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal_one['id']);
                     
                    $earning = new Earning();
                    $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
                    $borrow_user_info['repay_money'] = $all_repay_money;
                    $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
                    $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
                    $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
                    $borrow_user_info['leasing_money'] = $deal['leasing_money'];
                    $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
                    
                    $load_info = getLoadidByConid($cont_one['id'], 1, $borrow_user_info['user_id'], $agency_info, $guarantor_list);
                    
                    if(isset($load_info['id'])){
                        //投资记录
                        $loan_sql = "SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time
						 FROM ".DB_PREFIX."deal_load as d,".DB_PREFIX."user as u
						 WHERE d.deal_id = ".$deal['id']." AND d.user_id = u.id AND d.id = ".$load_info['id'];
                         
                        $loan_user_info = $GLOBALS['db']->getRow($loan_sql);
                        
                        $res = $contractModule->push_entrust_warrant_contract($cont_one, $deal, $guarantor_list, $loan_user_info, $borrow_user_info, $agency_info);
                        if($res){
                            $msg .= "合同Id: ".$cont_one['id']." 成功！\r\n";
                        }else{
                            $msg .= "合同Id: ".$cont_one['id']." 失败..\r\n";
                        }
                    }else{
                        $msg .= "合同Id: ".$cont_one['id']." 未找到对应的投资记录！\r\n";
                    }
                }else{
                    $msg .= "合同Id: ".$cont_one['id']." 对应的借款id".$deal_one['id']."状态错误！\r\n";
                }
            }
        }else{
            $msg .= "借款Id: ".$deal_one['id']." 没有《委托担保合同》！\r\n";
        }
    }
}else{
    $msg .= "借款记录为空！\r\n";
}

echo $msg;