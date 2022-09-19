<?php

/**
 * app4.0 列表页
 * 返回结果根据传的dealListType参数来返回相应的数据结构改变，其他逻辑不变
 * @date 2016-09-13
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\DealsListService;

class DealsList extends AppBaseAction {

  

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string', "option" => array('optional' => true)),
            'refresh' => array('filter' => 'int', "option" => array('optional' => true)),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;      
        $refresh = !empty($data['refresh']) ? $data['refresh'] : 0;
        
        $dealsListService = new DealsListService();
        $result = $dealsListService->getList($userId,'all',$refresh);
        
        $this->json_data = $result;
    } 

}
