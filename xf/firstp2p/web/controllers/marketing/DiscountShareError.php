<?php
/**
 * ERROR页面
 * @date 2016年07月14日
 * @author 王振 <wangzhen3@ucfgroup.com>
 */


namespace web\controllers\marketing;


class DiscountShareError extends DiscountShareBase {

    public function init() {
        parent::init();
    }

    public function invoke() {
        if(!$this->autoCheck()){
            $this->error();return false;
        }
        $this->setDownload();
        $this->template = "web/views/v3/marketing/discount_no.html";
    }
}
