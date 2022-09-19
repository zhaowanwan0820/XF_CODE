<?php

use libs\utils\Logger;
use core\dao\UserModel;
use core\service\VipService;
use libs\utils\PaymentApi;

/**
 * vip资金记录通知接口
 */
class VipNotifyAction extends CommonAction {

    public function __construct() {
#        parent::__construct();
    }

    public function notify() {
        $result = array('errno' => 10000, 'errmsg' => 'success');
        $records = json_decode(urldecode($_GET['record']), true);
        PaymentApi::log('notify-info:'.json_encode($records,JSON_UNESCAPED_UNICODE));
        if (count($records)) {
            $vipService = new VipService();
            try{
                foreach ($records as $item) {
                    $vipService->updateVipGrade($item['log_user_id']);
                }
            } catch (\Exception $e) {
                $result['errmsg'] = $e->getMessage();
                $result['errno'] = 10001;//异常处理
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        return;
    }
}

