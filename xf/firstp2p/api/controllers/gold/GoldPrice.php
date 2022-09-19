<?php

/**
 * 实时金价接口
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2017.05.17
 * */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use NCFGroup\Protos\Gold\RequestCommon;

class GoldPrice extends GoldBaseAction {


    public function init() {

    }

    public function invoke() {

        $res = $this->rpc->local('GoldService\getGoldPrice', array());
        $result = array();
        $result['status'] = '0';
        if (floorfix($res['data']['gold_price'],2) == 0) {
            $result['gold_price'] = '--';
            $result['status'] = '1';
            $result['msg'] = '实时金价正在更新，请稍后再试';
        } else {
            $result['gold_price'] = floorfix($res['data']['gold_price'],2);
        }
        $this->json_data = $result;
    }

}
