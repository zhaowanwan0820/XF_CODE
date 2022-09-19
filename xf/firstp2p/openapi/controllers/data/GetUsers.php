<?php
namespace openapi\controllers\data;

use libs\web\Form;
use openapi\controllers\DataBaseAction;
use core\service\UserService;

/**
 * @abstract 获取用户列表
 * @author longbo
 */
class GetUsers extends DataBaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
            "update_time" => array("filter" => "int", "message" => "updateTime is error", "option" => array('optional' => true)),
            "sortType" => array("filter" => "int", "message" => "sort is error", "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $offset = empty($data['offset']) ? 0 : intval($data['offset']);
        $count = empty($data['count']) ? 1000 : intval($data['count']);
        $count = ($count > 5000) ? 5000 : $count;
        $updateTime = intval($data['update_time'] - 8 * 3600);
        $updateTime = $updateTime > 0 ? $updateTime : 0;
        $sortType = $data['sortType'];

        try {
            $UserService = new UserService(); 
            $res = $UserService->getUserBySiteId($this->siteId, $offset, $count, $updateTime, $sortType);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $users = array();
        if ($res) {
            foreach ($res as $value) {
                $user = array();
                $user['id'] = $value['id'];
                $user['userXid'] = $this->encodeId($value['id']);
                $user['user_name'] = $value['user_name'];
                $user['real_name'] = $value['real_name'];
                $user['sex'] = $value['sex'];
                $user['money'] = $value['money'];
                $user['lock_money'] = $value['lock_money'];
                $user['mobile'] = $value['mobile'];
                $user['email'] = $value['email'];
                $user['create_time'] = $value['create_time'] + 8*3600;
                $user['update_time'] = $value['update_time'] > 0 ? $value['update_time'] + 8*3600 : $user['create_time'];
                $user['group_id'] = $value['group_id'];
                $user['refer_user_id'] = $value['refer_user_id'];
                $user['invite_code'] = $value['invite_code'];
                $users[] = $user;
            }
        }

        $this->json_data = $users;
    }

}
