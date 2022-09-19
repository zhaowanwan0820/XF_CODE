<?php
/**
 * 用户迁移到经讯时代确认
 * @author wangchuanlu<wangchuanlu@ucfgroup.com>
 */

namespace web\controllers\user;
use web\controllers\BaseAction;
use libs\web\Form;

class TransferConfirm extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->validate();
    }

    public function invoke() {
        $result = array(
            'code'=>0,
            'msg'=>'确认迁移成功',
        );
        $user_id = intval ( $GLOBALS['user_info']['id'] );
        $ret = $this->rpc->local('UserService\updateUserToJXSD', array($user_id));
        if (!$ret) {
            $result['code'] = 1;
            $result['msg'] = '确认迁移失败，请稍后重试';
        }
        echo json_encode($result);
        return ;
    }

}
