<?php

/**
 * Detail.php
 *
 * @date 2018-11-22
 * @author liguizhi <liguizhi@ucfgroup.com>
 */

namespace api\controllers\ncfph;

use api\controllers\NcfphRedirect;
use libs\web\Form;
use libs\utils\Aes;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package api\controllers\deals
 */
class DealsDetail extends NcfphRedirect {
    const IS_H5 = true;

    private $phAction = '/deals/detail';

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int", "message" => "id is error"),
            'token' => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
        }

        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
        }

        $this->form->data['id'] = intval($this->form->data['id']);
    }

    public function invoke() {

        $data = $this->form->data;
        $loginUser = $this->getUserByToken(false);
        if (empty($loginUser)) {
            //app退出登录后，token置为空，防止redirect到普惠wap后从cookie取缓存token By liguizhi
            $data['token'] = '';
        }

        $dealId = Aes::encryptForDeal($data['id']);
        $ncfphData = array('dealid' => $dealId, 'token' => $data['token']);
        return $this->ncfphRedirect($this->phAction, $ncfphData);
    }
}
