<?php
/**
 * ajax方式签署全部合同
 * 调用普惠接口
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\ncfph\AccountService;

/**
 * 合同批量签署
 * @userLock
 */
class Contsignajaxph extends BaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'p' => array('filter' => 'int'),
            'id' => array('filter' => 'int'),//借款id
            'role' => array('filter' => 'int'),
        );
        $this->form->validate();
    }

    public function invoke()
    {

        $data = $this->form->data;
        $user_id = intval($GLOBALS ['user_info']['id']);
        $return_res['status'] = 0;

        $deal_id = intval($data ['id']);
        if ($user_id == 0 || $deal_id <= 0) {
            return self::return_json($return_res);
        }
        $role = intval($data ['role']);
        $accountServcie = new AccountService();
        $sign_info = $accountServcie->contSignAjax($user_id, $deal_id, $role);
        $return_res['status'] = $sign_info ? 1 : 0;
        return self::return_json($return_res);
    }

    public static function return_json($data)
    {
        echo json_encode($data);
        return false;
    }
}

