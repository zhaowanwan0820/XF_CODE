<?php
/**
 * 将混合用途的存管用户刷为借款户或投资户
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;
use core\dao\DealRepayModel;

//获取所有还款中的P2P消费贷的标ID
while(true){
    $repays = Db::getInstance('firstp2p')->getAll("SELECT dr.id FROM firstp2p_deal_repay AS dr LEFT JOIN firstp2p_deal AS d ON dr.deal_id = d.id WHERE dr.deal_type = 0 AND dr.status = 0 AND dr.repay_type = 1 AND d.type_id = 29 AND d.deal_status = 4 limit 500;");
    if(count($repays) == 0){
        break;
    }else{
        foreach($repays as $repay){
            $dealRepay = DealRepayModel::instance()->find($repay['id']);
            $res = $dealRepay->updateOne(array('repay_type'=>3));
            if(!$res){
                Logger::info(sprintf('update xfd repay type :repay id %s faild!', $repay['id']));
                echo sprintf('update xfd repay type :repay id %s faild!', $repay['id']);
            }else{
                Logger::info(sprintf('update xfd repay type :repay id %s success!', $repay['id']));
                echo sprintf('update xfd repay type :repay id %s success!', $repay['id']);
            }
            echo $repay['id']."\n";
        }
    }
}