<?php
/**
 * 修复流标未成功的数据
 * @date 2015-03-13
 */
require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\DealModel;
use core\dao\DealContractModel;
use core\dao\ContractModel;

error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '1024M');


$time = to_timespan("2015-11-17");
$deal_model = new DealModel();
$arr_deal = $deal_model->findAll("`success_time` > '{$time}'", true, "id");

$dc_model = new DealContractModel();
$cont_model = new ContractModel();

foreach ($arr_deal as $deal) {
    $id = $deal['id'];
    echo $id . "\t";

    $arr_dc = $dc_model->findAll("`deal_id`='{$id}'");
    if (!$arr_dc) {
        echo "dc empty!!!\n";
    }

    foreach ($arr_dc as $dc) {
        if ($dc['status'] == 0) {
            echo "dc_id: ". $dc['id'] . " not sign\t";
        } elseif ($dc['status'] == 1) {
            $st = $dc['sign_time'];
            $user_id = $dc['user_id'];
            $agency_id = $dc['agency_id'];
            $arr_cont = $cont_model->findAll("`deal_id`='{$id}' AND `agency_id`='{$agency_id}' AND `user_id`='{$user_id}' AND `status`='0'");
            if (!$arr_cont) {
                echo "dc_id: " . $dc['id'] . "\thas signed\n";
            } else {
                foreach ($arr_cont as $cont) {
                    $cont->sign_time = $st;
                    $cont->status = 1;
                    $r = $cont->save();
                    if ($r === false) {
                        echo "dc_id: " . $dc['id'] . "\tc_id: " . $cont['id'] . "\tupdate failed\n";
                    } else {
                        echo "dc_id: " . $dc['id'] . "\tc_id: " . $cont['id'] . "\tturn 0 to 1\n";
                    }
                }
            }
        } elseif ($dc['status'] == 2) {
            $st = $dc['sign_time'];
            $user_id = $dc['user_id'];
            $agency_id = $dc['agency_id'];
            $arr_cont = $cont_model->findAll("`deal_id`='{$id}' AND `agency_id`='{$agency_id}' AND `user_id`='{$user_id}' AND `status`='0'");
            if (!$arr_cont) {
                echo "dc_id: " . $dc['id'] . "\thas signed\n";
            } else {
                $GLOBALS['db']->startTrans();
                try {
                    foreach ($arr_cont as $$cont) {
                        $cont->sign_time = $st;
                        $cont->status = 1;
                        $r = $cont->save();
                        if ($r === false) {
                            echo "dc_id: " . $dc['id'] . "\tc_id: " . $cont['id'] . "\tupdate failed\n";
                            throw new \Exception("failed");
                        } else {
                            echo "dc_id: " . $dc['id'] . "\tc_id: " . $cont['id'] . "\tturn 0 to 1\n";
                        }
                    }

                    $dc->status = 1;
                    $res = $dc->save();
                    if ($r === false) {
                        echo "dc_id: " . $dc['id'] . "\tupdate failed\n";
                        throw new \Exception("failed");
                    } else {
                        echo "dc_id: " . $dc['id'] . "\tturn 2 to 1\n";
                    }

                    $GLOBALS['db']->commit();
                } catch (\Exception $e) {
                    $GLOBALS['db']->rollback();
                    echo "dc_id: " . $dc['id'] . "\tupdate failed\n";
                }
            }
        }
    }

    echo "id: " . $id . " complete\n";

}





