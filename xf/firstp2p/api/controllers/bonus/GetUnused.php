<?php

/* *
 * 获取未发光未过期的红包组组数  返回字段1-10
 * @author  yutao@ucfgroup.com
 * @date    2015-09-23
 */

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\BonusService;

class GetUnused extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        if (app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            $this->json_data = ['summary' => '可用于投资'];
            return true;
        }

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $bonusService = new BonusService();
        $unusedBonusCount = $bonusService->getUnsendCount($loginUser['id']);
        $result['summary'] = $unusedBonusCount . "组";

        if ($unusedBonusCount < 1) {
            $userInfo = $bonusService->getUsableBonus($loginUser['id']);

            $result['summary'] = $userInfo['money'] . app_conf('NEW_BONUS_UNIT');
        }
        $this->json_data = $result;
        return;
    }

}
