<?php

/**
 * 企业用户信息完事验证
 * @author 文岭<liwenling@ucfgroup.com>
 */

namespace web\controllers\enterprise;

use web\controllers\BaseAction;
use libs\web\Form;

class Bank extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "c" => array("filter"=>"string"),
            "p" => array("filter"=>"string"),
            "b" => array("filter"=>"string"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;

        $city       = trim($data['c']);
        $province   = trim($data['p']);
        $branch     = trim($data['b']);

        $list = $this->rpc->local('BanklistService\getBanklist',array($city ,$province ,$branch));
        ajax_return($list);
    }

}
