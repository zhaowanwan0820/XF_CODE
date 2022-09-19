<?php
/**
 *  老用户分享页
 * @date 2016年07月11日
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 */


namespace web\controllers\marketing;


class DiscountShareInvite extends DiscountShareBase {

    public function init() {
        parent::init();
    }

    public function invoke() {
        if(!$this->autoCheck() || empty($_REQUEST['m'])){
            $this->error();
            return false;
        }
        $this->mobile = self::decode($_REQUEST['m']);
        if ($this->mobile){
            $this->setUserInfo();
        }

        $this->setInviteButton();
        $this->tpl->assign('isBidMore',$this->userInfo['isOldUser']);
        $this->template = "web/views/marketing/discount_old_user.html";
    }


}
