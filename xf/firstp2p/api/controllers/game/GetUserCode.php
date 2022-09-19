<?php

namespace api\controllers\game;

use libs\web\Form;
use api\controllers\ApiBaseAction;
use NCFGroup\Common\Library\ApiService;

/**
 * 网信2000天活动 获取用户抽奖码
 */
class GetUserCode extends ApiBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'pageNo' => array('filter' => 'int', 'option' => array('optional' => true)),
            'pageSize' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $loginUser['id'];
        $params = array(
            'userId' => $loginUser['id'],
            'pageNo' => isset($data['pageNo']) ? $data['pageNo'] : 1,
            'pageSize' => isset($data['pageSize']) ? $data['pageSize'] : 500,
        );
        $codesArr = ApiService::rpc('o2o', 'game/getUserGameCode', $params);

        // 展示需要序号 从1开始
        $codes = array();
        if ($codesArr) {
            $count = count($codesArr);
            $weight = strlen($count);
            foreach ($codesArr as $k => $code) {
                // 等宽 0补齐
                $key = str_pad(++$k, $weight, "0", STR_PAD_LEFT);
                $codes[$key] = $code;
            }
        }

        $this->tpl->assign('codes', $codes);
        $this->template = $this->getTemplate('getusercode');
    }
}
