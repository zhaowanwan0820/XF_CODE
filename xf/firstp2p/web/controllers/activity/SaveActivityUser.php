<?php
/**
 * 在线报名
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2016-12-02
 */
namespace web\controllers\Activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\dao\vip\ActivityUserModel;

class SaveActivityUser extends BaseAction
{
    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'from_login' => array('filter' => 'string'),
            'activity_id' => array('filter' => 'int'),
            'address' => array('filter' => 'string'),
            'relations' => array('filter' => 'string')
        );

        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $relations = stripslashes($data['relations']);
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

        $user['user_id'] = $GLOBALS['user_info']['id'];
        $user['real_name'] = $GLOBALS['user_info']['real_name'];
        $user['activity_id'] = intval($data['activity_id']);
        if (!empty($data['address'])) {
            $addressArr = explode(" ",trim($data['address']));
            $user['province'] = $addressArr[0];
            $user['city'] = $addressArr[1];
        }


        //保存邀请人信息
        $activityUserModel = new ActivityUserModel;
        $result = $activityUserModel->addActivityUser($user);
        if(empty($result)) {
            $ret['code'] = -1;
            $ret['msg'] = "网络出了一点小差，请重新提交！";
            return ajax_return($ret);
        }
        Logger::info(implode(' | ', array_merge($user, array('参加活动报名成功'))));

        $relations = json_decode($relations,true);
        if (empty($relations)) {
            $ret['code'] = -1;
            $ret['msg'] = "请提交邀请用户信息";
            return ajax_return($ret);
        }
        //保存被邀请人信息
        foreach($relations as $relation_item) {
            $flag = false;
            foreach ($relation_item as $key => $value) {
                if ($key != 'id' && !empty($value)) {
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                continue;
            }

            $user['relation_type'] = intval($relation_item['relation_type']);
            $user['relation_name'] = empty($relation_item['relation_name']) ? '': trim($relation_item['relation_name']);
            $user['relation_sex'] = $relation_item['relation_sex']==='' ? -1 : intval($relation_item['relation_sex']);
            $user['relation_age'] = intval($relation_item['relation_age']);
            $user['relation_phone'] = empty($relation_item['relation_phone']) ? '' : trim($relation_item['relation_phone']);

            $activityUserModel = new ActivityUserModel;
            $result = $activityUserModel->addActivityUser($user);
            if(empty($result)) {
                $ret['code'] = -1;
                $ret['msg'] = "网络出了一点小差，请重新提交！";
                return ajax_return($ret);
            }
            Logger::info(implode(' | ', array_merge($user, array('邀请活动用户成功'))));
        }

        $ret['code'] = 0;
        $ret['msg'] = "恭喜，您已报名此活动！<br/>最终入选结果，请以官方通知为准";
        return ajax_return($ret);
    }

    private function _check_login() {
        if (empty($GLOBALS['user_info'])) {
            $url = 'http://';
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
                || $_SERVER['SERVER_PORT'] == '443') {
                $url = 'https://';
            }
            if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
                $url .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
            } else {
                $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }

            // 增加登录来源判断
            if (strpos($url, 'from_login') === false) {
                if (strpos($url, '?') !== false) {
                    $url .= '&from_login=1';
                } else {
                    $url .= '?from_login=1';
                }
            }

            $current_url = urlencode($url);
            $location_url = !empty($current_url) ? "user/login?tpl=onlineregistration&backurl=" . $current_url : "user/login?tpl=onlineregistration";
            set_gopreview();
            return app_redirect(url($location_url));
        }
        return true;
    }
}
