<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/22
 * Time: 15:32
 */

namespace web\controllers\medal;

use core\service\MedalService;
use web\controllers\BaseAction;
use libs\web\Form;

class Detail extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
//            'site_id' => array('filter' => 'int'),
            'medal_id' => array('filter' => 'int'),
        );
        //没有登录，跳转到登录页面
        if(!$this->check_login()) {
            return false;
        }
        if (!$this->form->validate()) {
            return $this->show_error('参数错误。');
        }
        return true;
    }

    public function invoke() {
        $data = $this->form->data;

        $medalService = new MedalService();
        $request = $medalService->createUserMedalRequestParameter($GLOBALS['user_info']['id'], $data['medal_id']);
        $medalDetail = $medalService->getOneMedalDetail($request);
        if(false === $medalDetail) {
            ajax_return(array());
        } else {
            ajax_return($medalDetail);
        }
        return true;
    }
}