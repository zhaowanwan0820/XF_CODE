<?php

namespace web\controllers\rank;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\rank\RankService;
use core\service\UserService;
use core\service\GameService;

/**
 * 排行榜入口
 *
 * @author sunxuefeng@ucfgroup.com
 * @date 2018.10.22
 */
class Index extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'rankId' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
        );

        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $token = $data['token'];
        if (!empty($token)) {
            $tokenInfo = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($tokenInfo['code'])) {
                $GLOBALS['user_info'] = $tokenInfo['user'];
            }
        }
        if (!$this->check_login()) {
            return false;
        }

        // rankId需要加密
        $rankId = (new GameService())->decode($data['rankId']);
        if (empty($rankId)) {
            return $this->show_error('该活动不存在');
        }

        $loginUser = $GLOBALS['user_info'];
        $result = RankService::getRank($rankId, $loginUser['id']);

        if (!empty($result)) {
            $userService = new UserService();
            foreach($result['list'] as &$item) {
                $userInfo = $userService->getUserArray($item['userId'], 'mobile');
                $item['mobile'] = format_mobile($userInfo['mobile']);
            }
            $result['mine']['mobile'] = format_mobile($loginUser['mobile']);
        }

        $this->tpl->assign('result', $result);
        $this->template = $this->getTemplate();
    }
}
