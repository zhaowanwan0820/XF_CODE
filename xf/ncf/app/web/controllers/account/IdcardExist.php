<?php

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\user\UserService;

class IdcardExist extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'idType' => array('filter' => 'int'),
            'idno' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
        }
    }

    public function invoke() {
        $id_type = isset($this->form->data['idType']) ? intval(trim($this->form->data['idType'])) : 0;
        $idno = isset($this->form->data['idno']) ? trim($this->form->data['idno']) : '';
        if (!array_key_exists($id_type, $GLOBALS['dict']['ID_TYPE'])) {
            $this->printError(-1, '系统繁忙，请稍后重试');
            return;
        }
        if (empty($idno)) {
            $this->printError(-2, '系统繁忙，请稍后重试');
            return;
        }

        $ret = UserService::isIdCardExist($idno);
        if ($ret === TRUE) {
            $this->printError(-3, '身份验证失败,如需帮助请联系客服');
            return;
        }

        echo json_encode(array('code' => 0, 'msg' => ''));
    }

    public function printError($errorCode, $errorMsg) {
        $ret = array(
            'code' => $errorCode,
            'msg' => $errorMsg
        );
        echo json_encode($ret);
    }
}