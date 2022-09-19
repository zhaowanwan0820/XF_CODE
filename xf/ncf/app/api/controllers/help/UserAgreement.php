<?php
/**
 * UserAgreement.php
 *
 * @date 2014-03-31
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UserAgreement extends AppBaseAction{

    const IS_H5 = true;

    protected $needAuth = false;
    protected $redirectWapUrl = '/common_adv';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
            "adv" => array("filter" => "string", 'option' => array('optional' => true)),
            "title" => array("filter" => "string", 'option' => array('optional' => true)),
        );

        $this->form->validate();
        $advid = 'regist_protocol';
        $advtitle = '注册协议';
        $this->redirectWapUrl .= "?advid={$advid}&advtitle={$advtitle}";
    }

    public function invoke() {
        return true;
    }
}
