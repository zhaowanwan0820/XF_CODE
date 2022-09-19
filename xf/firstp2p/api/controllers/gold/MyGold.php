<?php

/**
 * 我的黄金首页显示
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date 2017.05.17
 **/

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use NCFGroup\Protos\Gold\RequestCommon;

class MyGold extends GoldBaseAction {

    public function init() {
       parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array(
                        'filter' => 'required',
                        'message' => 'ERR_PARAMS_VERIFY_FAIL',
                ),
                'type' => array(
                    'filter' => 'int',
                    'message' => 'type must int',
                    'option' => array('optional' => true)
                ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
      $data = $this->form->data;

        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
         if(!empty($data['type']) && $data['type'] != self::GOLD_CURRENT_TYPE && $data['type'] != self::GOLD_TYPE){
             $this->setErr('ERR_PARAMS_ERROR');
             return false;
         }
        $cumulative_des =  ($data['type'] == self::GOLD_TYPE) ? 3  : 2;
        $type = !empty($data['type']) ? intval($data['type']) : 0;
        $request = new RequestCommon();
        $request->setVars(array('userId'=>$user['id'], 'type' => $type));
        $res = $this->rpc->local('GoldService\myGold', array($request));
        $res['data']['gold'] = number_format(floorfix($res['data']['gold'],3,6),3);
        $res['data']['hold_gold'] = number_format(floorfix($res['data']['hold_gold'],3,6),3);
        $res['data']['cumulative_compensate_gold'] = number_format(floorfix($res['data']['cumulative_compensate_gold'],3,6),$cumulative_des);
        $res['data']['hold_gold_market_value'] = number_format(floorfix($res['data']['hold_gold_market_value'],2),2);
        $res['data']['gold_market_value'] = number_format(floorfix($res['data']['gold_market_value'],3,6),3);
        $res['data']['cost_price'] = number_format(floorfix($res['data']['cost_price'],3,6),2);
        $PL = number_format($res['data']['PL'],2);
        $res['data']['PL'] = $PL > 0 ? '+'.$PL : $PL;
        $result = array();
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON','请求失败，请重试');
            return false;
        }

        $result['gold_list'] = $res['data'];

        $this->json_data = $result;
    }

}
