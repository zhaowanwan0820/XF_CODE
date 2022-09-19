<?php
/**
 * 领取结果页面
 * @date 2016年07月14日
 * @author 王振 <wangzhen3@ucfgroup.com>
 */


namespace web\controllers\marketing;
use libs\web\Url;

class DiscountShareResult extends DiscountShareBase {

    public function init() {
        $this->mobile = self::decode($_REQUEST['m']);
        parent::init();
    }

    public function invoke() {

        if(!$this->autoCheck()){
            $this->error();return false;
        }

        if(!isset($_COOKIE['ma']) || $_COOKIE['ma'] != md5($this->mobile)){
            $web_url = new Url();
            $url = $web_url::getConfHttpProtocol().APP_HOST.'/marketing/DiscountShareInfo?id='.$this->ec_id.'&cn='.$this->cn;
            header("Location:".$url);
            exit;
        }

        $this->getDisCountList();
        $this->getDisCountListByMobile();
        $this->setDownload();
        $this->setInviteButton();
        $this->tpl->assign('m',$_REQUEST['m']);
        $this->tpl->assign('changeMobileUrl','/marketing/DiscountShareChangeMobile?m='.$_REQUEST['m'].'&id='.$this->ec_id.'&cn='.$this->cn.'&from_platform='.$this->fromPlatform);
        $this->template = "web/views/marketing/discount_share_result.html";
    }

    /**
     *验证参数
     */
    protected function autoCheck(){
        if(!parent::autoCheck()){
            return false;
        }

        if(empty($this->mobile)){
            $this->error_code = 2;
            return false;
        }

        return true;
    }
}
