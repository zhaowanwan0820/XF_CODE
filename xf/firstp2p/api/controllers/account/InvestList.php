<?php

/**
 * InvestList.php
 *
 * @date 2017-06-19
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;

/**
 * 获取最新的30条投资记录
 *
 * Class InvestList
 * @package api\controllers\account
 */
class InvestList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "count" => array("filter" => "int", "option" => array('optional' => true))
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;

        $count = !empty($params['count']) ? intval($params['count']) : 30;
        $loadList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealLoadService\getLastLoadList', array($count)), 60);
        if(empty($loadList)){
            $this->setErr('ERR_SYSTEM','已投资列表不能为空');
            return false;
        }
        $list = array();
        foreach ($loadList as $key => $value){
            $list[$key]['mobile'] = substr_replace($value['mobile'], str_repeat("*", 6), -8, -2);
            $list[$key]['money'] = number_format($value['loan_money'], 2,'.',',');
        }

        $this->json_data = $list;
    }

}
