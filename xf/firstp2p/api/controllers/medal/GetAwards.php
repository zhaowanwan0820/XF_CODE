<?php
/**
 * 领取奖励接口
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
namespace api\controllers\medal;
use api\controllers\AppBaseAction;
use libs\web\Form;
use api\conf\Error;
use NCFGroup\Protos\Medal\RequestGetUserMedalAwards;

class GetAwards extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "medalId" => array("filter" => "required","message" => "medalId is required"),
            "prizeId" => array("filter" => "required","message" => "prizeId is required"),
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
        $data = $this->form->data;
        $request = new RequestGetUserMedalAwards();
        $request->setUserId(intval($user['id']));
        $request->setMedalId(intval($data['medalId']));
        $request->setAwards(explode(',',$data['prizeId']));
        $res = $this->rpc->local("MedalService\getAwards",array($request));
        if ($res) {
            $res = 'success';
        } else {
            $res = 'failed';
        }
        $this->json_data = $res;
    }
}
