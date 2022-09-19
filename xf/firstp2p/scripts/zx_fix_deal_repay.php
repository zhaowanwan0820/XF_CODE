<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 2017/6/26
 * Time: 下午9:46
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';
require_once dirname(__FILE__) . '/../libs/common/functions.php';

use core\dao\DealRepayModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');



$dealRepay = new DealRepayModel();

$result = $dealRepay->updateBy(array('status' => 0,'true_repay_time' => 0),"deal_id in (1213819,1213817,1213815,1213813,1213812,1211479)");

if($result){
    echo 'success';
}else{
    echo 'fail';
}