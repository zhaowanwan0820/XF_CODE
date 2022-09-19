<?php
/**
 * 增加收获地址设置
 *  @author zhaohui<zhaohui3@ucfgroup.com>
 *  @date 2015年7月22日
 */
namespace web\controllers\account;

use core\service\MsgConfigService;
use web\controllers\BaseAction;
use libs\web\Form;

class Delivery extends BaseAction
{
    public function init()
    {
        if (!$this->check_login())
            return false;
    }

    public function invoke()
    {
        if (\es_session::get("sms_deli_verified")==1) {
            \es_session::set("delivery_pro_flag",1);//设置收获地址数据处理标识，防止短信绕过和csrf攻击
            $user_info = $GLOBALS['user_info'];
            $delivery_infor = $this->rpc->local('DeliveryService\getInfoByUid', array($user_info['id']));
            $this->tpl->assign('delivery_infor', $delivery_infor);
            $this->template = "web/views/v2/account/delivery.html";
        } else {
            return $this->show_error('操作失败 ！', '', 0, 0, url("account/setup"));
        }
    }

}