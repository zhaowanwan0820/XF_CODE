<?php

/**
 * DealProjectShow.php
 *
 * @date 2016-08-25
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use core\dao\DealProjectModel;

/**
 * 已投列表查看项目详情
 *
 *
 * Class DealProjectShow
 * @package api\controllers\duotou
 */
class DealProjectShow extends DuotouBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'token is required',
            ),
            'project_id' => array(
                    'filter' => 'required',
                    "message" => "project_id is required",
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {

        if (!$this->dtInvoke()) 
            return false;

        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }
        $data = $this->form->data;
        $projectId = intval($data['project_id']);

        $projectInfo = DealProjectModel::instance()->find($projectId);
        if (!$projectInfo) {
            return $this->assignError('ERR_SYSTEM','系统繁忙，如有疑问，请拨打客服电话：95782');
        }
        $this->tpl->assign('project_info', $projectInfo['intro']);
    }
    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}
