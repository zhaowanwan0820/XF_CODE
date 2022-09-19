<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;

/**
 * 2018世界杯活动
 */
class GuessLog extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'pageNo' => array('filter' => 'int', 'option' => array('optional' => true)),
            'isApp' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $this->isApp = intval($data['isApp']);
        $token = $data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        if (!$this->_check_login()) {
            return false;
        }

        $uid = intval($GLOBALS['user_info']['id']);
        $pageNo = intval($data['pageNo']) ?: 1;
        $pageSize = 10;
        $event = $this->rpc->local('GameService\getUserLogList', array($uid, $pageNo, $pageSize));
        $list = $event['data'];
        foreach($list as &$item) {
            $item['date'] = date('Y-m-d', $item['createTime']);
            $item['time'] = date('H:i:s', $item['createTime']);
            //TODO 字段待修正
            $item['statusDesc'] = '成功';
            $item['note'] = '竞猜哪一场';

        }
        $this->tpl->assign("token", $token);
        $this->tpl->assign('list', $list);
        $this->template = 'web/views/worldcup2018/guessing_record.html';
    }
}
