<?php
namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyAccountService;

class Log extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $count = 10;
        $page = intval($data['page']);
        $offset = $page > 0 ? ($page - 1) * $count : 0;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $accountService = new CandyAccountService();
        $logList = $accountService->getAccountLog($loginUser['id'], $offset, $count);
        $total = $offset == 0 ? count($logList) : $count;
        if (!empty($logList)) {
            foreach ($logList as $key => $log) {
                $logList[$key]['create_time'] = date("Y-m-d", $log['create_time']);
                $logList[$key]['amount'] = number_format($log['amount'], 3);
            }
        }

        $this->json_data = ['list' => $logList, 'total' => $total];
    }
}
