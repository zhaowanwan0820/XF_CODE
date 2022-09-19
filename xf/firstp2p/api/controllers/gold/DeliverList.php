<?php
/**
 * 提金订单列表接口
 * @author xiaoan
 * @date 2017.09.18
 */


namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class DeliverList extends GoldBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
               'token' => array(
                        'filter' => 'required',
                        'message' => 'ERR_PARAMS_VERIFY_FAIL',
                ),
                'type' => array(
                        'filter' => 'string',
                        'option' => array('optional' => true)
                ),
                'pageNum' => array(
                        'filter' => 'int',
                        'message' => 'pageNum must int',
                        'option' => array('optional' => true)
                ),
                'pageSize' => array(
                        'filter' => 'int',
                        'message' => 'pageNum must int',
                        'option' => array('optional' => true)
                ),
        );
        if (!$this->form->validate()) {
            $this->setErr(ERR_PARAMS_VERIFY_FAIL,$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $pageNum = isset($data['pageNum']) ? $data['pageNum'] : '';
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : '';
        $type = isset($data['type']) ? addslashes($data['type']) : '';
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('GoldService\getDeliverList', array($user['id'],$type,$pageNum,$pageSize));

        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['errMsg']);
            return false;
        }
        $result = array();

        $result['list'] = $res['data']['data'];
        $result['totalPage'] = $res['data']['totalPage'];
        $result['totalNum'] = $res['data']['totalNum'];
        $result['deliveredNum'] = $res['data']['deliveredNum'];

        $this->json_data = $result;
    }



}

