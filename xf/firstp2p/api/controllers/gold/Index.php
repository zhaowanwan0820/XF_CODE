<?php

/**
 * 黄金首页列表
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class Index extends GoldBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
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
                'type' => array(
                        'filter' => 'int',
                        'message' => 'type must int',
                        'option' => array('optional' => true)
                ),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR",$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        //$data['type'] == 1 获取所有的在售标的,如果没有在售标的，则获取前三个标的
        $typeRes = array();
        if (isset($data['type']) && $data['type'] == 1) {
            $typeRes = $this->rpc->local('GoldService\getP2pDealList', array(1,100));
        }

        if (!empty($typeRes['list'])) {
            $this->handleRes($typeRes);
            $this->json_data = $typeRes;
            return;
        } elseif (empty($typeRes['list']) && $data['type'] == 1) {
            $typeRes = $this->rpc->local('GoldService\getDealList', array(3,1));
            $this->handleRes($typeRes);
            $this->json_data = $typeRes;
            return;
        }


        $pageNum = !empty($data['pageNum']) ? intval($data['pageNum']) : '';
        $pageSize = !empty($data['pageSize']) ? intval($data['pageSize']) : '';

        $res = $this->rpc->local('GoldService\getDealList', array($pageSize,$pageNum));


        foreach($res['list'] as &$item){
            $item['annual_comp_rate'] = number_format($item['annual_comp_rate'], 3);
            $item['usable_quality'] = (string)$item['usable_quality'];
            $item['tagNames'] = empty($item['tag'])? array() : explode(',', $item['tag']);
        }
        $result = array();
        $result = $res;

        $this->json_data = $result;
    }

    public function handleRes(&$res) {
        foreach($res['list'] as &$item){
            $item['annual_comp_rate'] = number_format($item['annual_comp_rate'], 3);
            $item['usable_quality'] = (string)$item['usable_quality'];
            $item['tagNames'] = empty($item['tag'])? array() : explode(',', $item['tag']);
        }
    }

}
