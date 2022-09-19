<?php
/**
 * Contractpre controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-08-01
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;

/**
 * 合同协议
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class Contractpre extends DuotouBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'token is required',
            ),
            'project_id' => array(
                    'filter' => 'int',
                    'message' => 'project_id is required',
            ),
            'money' => array(
                    'filter' => 'float',
                    'message' => 'money is required',
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke()
    {
        if (!$this->dtInvoke())
            return false;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }

        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $data = $this->form->data;
        $projectId = $data['project_id'];
        $money = $data['money'];

        if (!$userId || $projectId <= 0) {
            return $this->assignError('ERR_PARAMS_ERROR');
        }

        $res = $this->rpc->local("ContractPreService\getDtbContractPre", array($projectId, $userId,$money));
        $this->tpl->assign('content', $res);
        $this->tpl->assign('token',$data['token']);
    }
    public function _after_invoke() {
         $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}
