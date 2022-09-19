<?php

/**
 * @abstract 客户端获得闪屏接口
 * @author yutao
 * @date 2015-05-09
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\AdvService;

class Splash extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('POST');
        $this->form->rules = array(
            'screenwidth' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_ERROR'
            ),
            'screenheight' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_ERROR'
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            )
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $data['site_id'] = empty($data['site_id']) ? $this->defaultSiteId : $data['site_id'];
        $splashInfo = AdvService::getSplashInfo($_SERVER['HTTP_OS'], $data['screenwidth'],
            $data['screenheight'], $data['site_id']);

        if (!$splashInfo) {
            $this->setErr('ERR_SPLASH_EMPTY');
        }

        $this->json_data = array(
            "h5title" => $splashInfo['title'],
            "h5url" => $splashInfo['link'],
            "imageurl" => $splashInfo['imageurl'],
            "siteId" => $splashInfo['site_id']
        );
    }
}