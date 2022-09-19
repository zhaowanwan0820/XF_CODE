<?php
/**
 * ChangeMobile.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\discount;

use libs\web\Form;
use web\controllers\discount\DiscountBase;
use core\service\BonusBindService;
use core\service\DiscountService;

class ChangeMobile extends DiscountBase {

    public function init() {

        $bonusBindService = new BonusBindService();
        $this->template = '';
        $ajax = $_POST ? 1 : 0;
        $this->wxCache = $this->getCookie(self::USER_WEIXIN_INFO);
        if (!$this->wxCache) {
            $this->show_error('获取微信信息失败', '', $ajax);
        }
        $this->openid = $this->wxCache['openid'];
        // 获取绑定的手机号
        $bindInfo = $bonusBindService->getBindInfoByOpenid($this->openid);
        if ($bindInfo) {
            $this->mobile = $bindInfo->mobile;
        }
        $this->mobile = $this->mobile ? $this->mobile : $this->getCookie(self::USER_MOBILE_KEY);
        if (!$this->mobile) {
            $this->show_error('没有绑定手机号', '', $ajax);
            return false;
        }

        $this->tpl->assign('templateInfo', (new DiscountService())->getTemplateInfoBySiteId($this->getSiteId()));
        if (!$ajax) {
            $this->form = new Form("get");
            $this->form->rules = array(
                "sn" => array("filter" => "required", 'message' => "参数错误"),
                "site_id" => array("filter" => "int", "option" => array("optional" => true)),
            );
            if (!$this->form->validate()) {
                $this->show_error($this->form->getErrorMsg(), '', 0, 1);
                return false;
            }
            // 获取jsTicket
            $this->getJsApiSignature();
            $site_id = $this->form->data['site_id'];
            $site_id = $site_id ? $site_id : 1;
            $this->tpl->assign("host", APP_HOST);
            $this->tpl->assign("mobile", $this->mobile);
            $this->tpl->assign("sn", $this->form->data['sn']);
            $this->tpl->assign('site_id', $site_id);
            $this->template = "web/views/wxinvest/changemobile.html";
            return false;
        } else {
            $this->form = new Form("post");
            $this->form->rules = array(
                "sn" => array("filter" => "required", 'message' => "参数错误"),
                "newMobile" => array("filter" => "string", 'option' => array('optional' => true)),
                "token_id" => array("filter"=>'string'),
            );
            if (!$this->form->validate()) {
                $this->show_error($this->form->getErrorMsg(), '', 1, 1);
                return false;
            }

            // 验证表单令牌
            if(!check_token()){
                return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], '', 1, 1);
            }
        }
    }

    public function invoke() {
        $mobile = $this->form->data['newMobile'];
        if ($mobile) {
            if (!is_mobile($mobile)) {
                $this->show_error('手机号码格式不正确', '', 1 , 1);
                return false;
            }

            $bonusBindService = new BonusBindService();
            if ($bonusBindService->bindUser($this->openid, $mobile)) {
                 $this->show_success('更新成功', '', 1);
            } else {
                 $this->show_error('更新失败', '', 1);
            }
        }

        return false;
    }
}
