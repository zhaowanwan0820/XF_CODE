<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/27
 * Time: 16:29
 */

namespace web\controllers\medal;

use libs\web\Form;
use NCFGroup\Protos\Medal\RequestGetUserMedalAwards;
use web\controllers\BaseAction;

class Award extends BaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "medal_id" => array("filter" => "required","message" => "medalId is required"),
            "prize_id" => array("filter" => "required","message" => "prizeId is required"),
        );
        //没有登录，跳转到登录页面
        if(!$this->check_login()) {
            return false;
        }
        //参数验证没有通过。
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), "", 1);
            return false;
        }
        return true;
    }

    public function invoke() {
        $data = $this->form->data;
        $userId = intval($GLOBALS['user_info']['id']);
        $requestUserMedalAward = new RequestGetUserMedalAwards();
        $requestUserMedalAward->setUserId($userId);
        $requestUserMedalAward->setMedalId(intval($data['medal_id']));
        $requestUserMedalAward->setAwards(explode(",", $data['prize_id']));
        $medalService = new \core\service\MedalService();
        try{
            $result = $medalService->getAwards($requestUserMedalAward);
            if($result) {
                ajax_return(array("status" => 0, "msg" => "领取成功"));
            } else {
                ajax_return(array("status" => 1, "msg" => "领取失败，请稍后再试"));
            }
            return true;
        } catch(\Exception $e) {
            ajax_return(array("status" => 1, "msg" => $e->getMessage()));
            return false;
        }
    }

}