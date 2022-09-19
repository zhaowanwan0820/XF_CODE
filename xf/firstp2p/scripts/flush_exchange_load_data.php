<?php

/**
 * firstp2p_exchange_load表的数据加密
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\ExchangeModel;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '2048M');

$page = 1;
$pageSize = 1000;
$exchangeModel = new ExchangeModel();
$fields = "id,certificate_no,mobile,bank_no";
$loadList = array();

do {
    $loadList = $exchangeModel->getLoadList($page, $pageSize, $fields);
    $page = $page + 1;
    $total = count($loadList);

    foreach($loadList as $data){
        $condition = "id = {$data['id']}";
        $res = $exchangeModel->updateLoadData($data, $condition);

        if(!$res){
            Logger::error('exchange load data encrypt fail: '. $data['id']);
        }
    }

}while($total == $pageSize);

exit;

