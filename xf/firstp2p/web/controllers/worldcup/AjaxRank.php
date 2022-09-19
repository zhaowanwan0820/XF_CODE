<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;

/**
 * 2018世界杯活动
 */
class AjaxRank extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'pageNo' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        $this->form->validate();
    }

    public function invoke() {
        $ret = array(
            'error' => 0,
            'msg' => 'success',
            'data' => 1
        );

        $data = $this->form->data;
        $pageNo = isset($data['pageNo']) ? intval($data['pageNo']) : 0;
        $pageSize = 10;
        if ($pageNo > 10) {
            $list = array();
        } else {
            $list = $this->rpc->local('GameService\getRankList', array($pageNo, $pageSize));
            if (empty($list)) {
                $list = array();
            }
        }

        $ret['data'] = $list;
        return ajax_return($ret);
    }
}
