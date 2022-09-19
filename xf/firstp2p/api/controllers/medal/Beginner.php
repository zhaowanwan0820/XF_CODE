<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/5/31
 * Time: 12:49
 */

namespace api\controllers\medal;

use api\controllers\AppBaseAction;
use core\service\MedalService;
use libs\web\Form;

class Beginner extends AppBaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {

        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $result = 0;
        if($redis) {
            $key = sprintf(MedalService::MEDAL_BEGINNER_HINT_FORMAT, $user['id']);
            $result = $redis->rPop($key);
        }
        $this->json_data = intval($result);
        return true;
    }
}
