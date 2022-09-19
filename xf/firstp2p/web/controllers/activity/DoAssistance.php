<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\WeiXinService;
use NCFGroup\Common\Library\ApiService;
use libs\utils\Aes;
use core\service\marketing\AssistanceService;

class DoAssistance extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'app_version' => array('filter' => 'string'),
            'eventId' => array('filter' => 'string'),
            'sn' => array('filter' => 'string')
        );
        $this->form->validate();
    }

    public function invoke()
    {
        if (empty($_POST)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 1003, 'msg' => '请求方法错误', 'data' => ""]);
            exit;
        }

        $token = $this->form->data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }
        if (empty($GLOBALS['user_info'])) {
            $eventUrl = urlencode(get_domain().str_replace('DoAssistance', 'Assistance', $_SERVER['REQUEST_URI']));
            $loginUrl = !empty($eventUrl) ? "/user/login?tpl=assistance&backurl=" . $eventUrl : "/user/login?tpl={$tpl}";
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => -1, 'msg' => '请重新登录', 'data' => "$loginUrl"]);
            exit;
        }

        $isNewer = false;
        if (($GLOBALS['user_info']['create_time'] + 86400) >= strtotime('2019-01-01')) {
            $hasLoan = (new \core\service\UserService())->hasLoan($GLOBALS['user_info']['id']);
            if ($hasLoan == false) {
                $isNewer = true;
            }
        }
        $eventId = $this->form->data['eventId']; // 活动ID
        $sn = $this->form->data['sn'];
        if ($sn == '') {
            $ownerUid = $GLOBALS['user_info']['id'];
        } else {
            $ownerUid = Aes::decryptHex($sn, AssistanceService::SN_KEY);
        }
        $eventId = $this->form->data['eventId']; // 活动ID
        $assistanceService = new AssistanceService($eventId);
        if ($ownerUid <= 0 || $eventId == '' || empty($assistanceService->config)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 1002, '链接错误']);
            exit;
        }
        $result = $assistanceService->doLike($ownerUid, $GLOBALS['user_info']['id'], $GLOBALS['user_info']['mobile'], $isNewer);
        if ($result['code'] == 0) {
            $result['data']['role'] = $ownerUid == $GLOBALS['user_info']['id'] ? 0 : 1;
            $result['data']['mobile'] =substr_replace($GLOBALS['user_info']['mobile'], '****', 3, 4);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

}



