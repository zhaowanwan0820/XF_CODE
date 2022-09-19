<?php
require(dirname(__FILE__) . '/../app/init.php');

use core\dao\DealExtModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_ext_model = new DealExtModel();
$j = 0;
for($i=1;$i< 84388;$i++){
    $deal = $deal_ext_model->findBy('deal_id = '.$i,"deal_id,loan_fee_ext,consult_fee_ext,guarantee_fee_ext,pay_fee_ext");
    if($deal){
        if($deal->loan_fee_ext){
            $periods = count(json_decode($deal->loan_fee_ext));
            if($periods > 2){
                if(intval($periods[1]) == 0){
                    $loan_fee_rate_type = 2;
                }else{
                    $loan_fee_rate_type = 3;
                }
            }
            if(($periods == 2)){
                if(intval($periods[0]) == 0){
                    $loan_fee_rate_type = 2;
                }else{
                    $loan_fee_rate_type = 3;
                }
            }
        }else{
            $loan_fee_rate_type = 1;
        }

        if($deal->consult_fee_ext){
            $periods = count(json_decode($deal->consult_fee_ext));
            if($periods > 2){
                if(intval($periods[1]) == 0){
                    $consult_fee_rate_type = 2;
                }else{
                    $consult_fee_rate_type = 3;
                }
            }
            if(($periods == 2)){
                if(intval($periods[0]) == 0){
                    $consult_fee_rate_type = 2;
                }else{
                    $consult_fee_rate_type = 3;
                }
            }
        }else{
            $consult_fee_rate_type = 1;
        }

        if($deal->guarantee_fee_ext){
            $periods = count(json_decode($deal->guarantee_fee_ext));
            if($periods > 2){
                if(intval($periods[1]) == 0){
                    $guarantee_fee_rate_type = 2;
                }else{
                    $guarantee_fee_rate_type = 3;
                }
            }
            if(($periods == 2)){
                if(intval($periods[0]) == 0){
                    $guarantee_fee_rate_type = 2;
                }else{
                    $guarantee_fee_rate_type = 3;
                }
            }
        }else{
            $guarantee_fee_rate_type = 1;
        }

        if($deal->pay_fee_ext){
            $periods = count(json_decode($deal->pay_fee_ext));
            if($periods > 2){
                if(intval($periods[1]) == 0){
                    $pay_fee_rate_type = 2;
                }else{
                    $pay_fee_rate_type = 3;
                }
            }
            if(($periods == 2)){
                if(intval($periods[0]) == 0){
                    $pay_fee_rate_type = 2;
                }else{
                    $pay_fee_rate_type = 3;
                }
            }
        }else{
            $pay_fee_rate_type = 1;
        }

        $update_sql = "UPDATE `firstp2p_deal_ext` SET `loan_fee_rate_type`='".$loan_fee_rate_type."', `consult_fee_rate_type`='".$consult_fee_rate_type."', `pay_fee_rate_type`='".$pay_fee_rate_type."', `guarantee_fee_rate_type`='".$guarantee_fee_rate_type."' WHERE `deal_id`='".$deal->deal_id."';
";
        $rs = $deal_ext_model->execute($update_sql);
        if(!$rs){
            echo $deal->deal_id."更新失败！"."\r\n";
        }
    }
}
echo "success";
exit();
