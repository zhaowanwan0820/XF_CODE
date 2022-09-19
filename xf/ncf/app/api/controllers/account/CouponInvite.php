<?php
/**
 * CouponInvite.php
 * @date 2014-10-08
 * @author longbo <longbo@ucfgroup.com>
 * 邀请码接口，app3.3版本后使用,
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;

/**
 * 我的优惠码接口
 *
 *
 * tips：优惠券说明;(在firstp2p后台配置，公共配置，优惠券客户端我的优惠码webview页面邀请说明，COUPON_APP_ACCOUNT_COUPON_PAGE_TIPS)
 * shareMsg: 点击邀请button后的分享文案; (在firstp2p后台配置，公共配置， 优惠券客户端点击邀请按钮文案，COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG)
 * Class CouponInvite
 * @package api\controllers\account
 */
class CouponInvite extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "site_id" => array("filter" => "int", "message" => "id error"),
            // 新wap站分享策略
            "from"        => array("filter" => "string"),
            "site_domain" => array("filter" => "string"),
            "site_coupon" => array("filter" => "string"),
            "site_logo"   => array("filter" => "string"),
            "euid_level"  => array("filter" => "string"),
            "euid"        => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = !empty($data['site_id']) ? $data['site_id'] : $this->defaultSiteId;
        $from = isset($data['from']) ? $data['from'] : '';
        $site_domain = isset($data['site_domain']) ? $data['site_domain'] : '';
        $site_coupon = isset($data['site_coupon']) ? $data['site_coupon'] : '';
        $site_logo = isset($data['site_logo']) ? $data['site_logo'] : '';
        $euid_level = isset($data['euid_level']) ? $data['euid_level'] : '';
        $euid = isset($data['euid']) ? $data['euid'] : '';

        $loginUser = $this->user;
        $result = UserService::accountCouponInvite(
            $loginUser['id'],
            $site_id,
            $from,
            $site_domain,
            $site_coupon,
            $site_logo,
            $euid_level,
            $euid
        );

        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
            return false;
        }

        $this->json_data = $result;
    }
}