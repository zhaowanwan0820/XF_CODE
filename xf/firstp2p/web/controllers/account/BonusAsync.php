<?php
/**
 * 我的网信红包 获取异步数据
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\bonus\BonusUser;

class BonusAsync extends BaseAction {

    public function init() {
        $this->check_login();

        $this->form = new Form('get');
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'type' => array('filter' => 'string'),
//                'status' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            ajax_return(array());
        }
    }

    public function invoke() {

        $res_arr = array();
        $data = $this->form->data;

        $type = $data['type'];
//        $status = $data['status'];
        $page = $data['p'] <= 0 ? 1 : $data['p'];

        if(!in_array($type, array('send', 'get', 'log'))){
            ajax_return($res_arr);
            return;
        }

        //我领到的红包
        if($type == 'get'){
            $result = $this->rpc->local('BonusService\get_list', array($GLOBALS['user_info']['id'], 0, true, $page, 10, true));
            $now = time();
            foreach ($result['list'] as $key =>$item) {
                if(($item['status'] == 1) && ($now >= $item['expired_at'])) {//有效的并且过期时间超过当前时间
                    $result['list'][$key]['status'] = 3;//已经失效
                }
            }
            $res_arr['pagecount'] = ceil($result['count'] / app_conf("PAGE_SIZE"));
        //我发出的红包
        }else if ($type == 'send'){
            $result = $this->rpc->local('BonusService\get_group_list', array($GLOBALS['user_info']['id'], true, $page));
            $res_arr['pagecount'] = ceil($result['count'] / app_conf("PAGE_SIZE"));
        } else {
        // 红包Log
            $result = $this->rpc->local('BonusService\getBonusLogList', [$GLOBALS['user_info']['id'], $page, 10]);
            $res_arr['pagecount'] = $result['page']['totalPage'];
        }

        $res_arr['list'] = $result['list'];
        $res_arr['bonus_user'] = $this->rpc->local('BonusService\getUserBonusInfo', [$GLOBALS['user_info']['id']]);
        $res_arr['share_count'] = $this->rpc->local('BonusService\getUnsendCount', [$GLOBALS['user_info']['id']]);
        ajax_return($res_arr);
    }
}

