<?php
/**
 * 理财首页显示标的
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2017.05.17
 */


namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class P2pIndex extends GoldBaseAction {


    public function init() {
        parent::init();
    }

    public function invoke() {
        $res = $this->rpc->local('GoldService\getP2pDealList', array(1,2));
        $this->json_data = $res;
    }

}

