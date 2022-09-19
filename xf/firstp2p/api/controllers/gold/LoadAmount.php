<?php
namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class LoadAmount extends GoldBaseAction
{
    public function init()
    {
        parent::init();

    }

    public function invoke()
    {
        $res = $this->rpc->local('GoldService\sellAmount',array());
        $result = array();
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON', '网络异常，请稍后重试');
            return false;
        }
        $result['loadAmount'] = $res['data']['loadAmount'];
        $result['loadAmount'] = "--";
        $this->json_data = $result;
    }
}
