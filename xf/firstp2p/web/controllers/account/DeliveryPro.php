<?php
/**
*  增加收获地址请求处理
*  @author zhaohui<zhaohui3@ucfgroup.com>
*  @date 2015年7月22日
*/
namespace web\controllers\account;

use core\service\MsgConfigService;
use web\controllers\BaseAction;
use libs\web\Form;

class DeliveryPro extends BaseAction
{
    private $_error = null;
    private $_flag = false;
    public function init()
    {
        if (!$this->check_login())
            return false;
        $this->form = new Form("post");
        $this->form->rules = array(
                'code'=> array('filter'=>'string'),
                'name' => array("filter"=>"reg", "message"=>"姓名不符合规则,只能为汉字或字母(2-25个)", "option"=>array("optional"=>true,"regexp"=>"/^[A-Za-z\x{4e00}-\x{9fa5}]{2,25}$/u")),
                'mobile'=>array("filter"=>"reg", "message"=>"请输入正确的手机号码(11位)", "option"=>array("optional"=>true,"regexp"=>"/^1[3|4|5|7|8][0-9]\d{8}/")),
                'area' => array('filter' => 'string'),
                'address'=>array("filter"=>"string"),
                'postalcode'=>array("filter"=>"reg", "message"=>"邮政编码不符合规则,请输入6位数字", "option"=>array("optional"=>true,"regexp"=>"/^\d{6}$/")),
                'sp'=>array("filter"=>'int'),
                'isAjax'=>array("filter"=>'int'),
                'phonecode'=>array("filter"=>'string'),
        );
        if (! $this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            if ($this->form->rules["sp"] == 1) {
                echo json_encode(array(
                        'errorCode' => 2,
                        'errorMsg' => $this->_error,
                ));
                 return ;
            }
            return $this->show_error($this->_error, '', 0, 0, url("account/setup"));
            
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $step = 0;
        $step = intval($data['sp']);
        $user_id = intval ( $GLOBALS['user_info']['id'] );
        $user_info = $this->rpc->local('UserService\getUser', array($user_id));
        //短信验证码校验
        if ($step==1 && $data['code']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($user_info['mobile']));
            if ($vcode != $data['code']) {
                if (\es_session::get("sms_deli_verified")) {
                    \es_session::delete("sms_deli_verified");
                }
                echo json_encode(array(
                        'errorCode' => 1,
                        'errorMsg' => '短信校验错误',
                ));
                return;
            } else {
                \es_session::set("sms_deli_verified",1);//防止直接进入delivery
                \es_session::set("delivery_mobile_code",$data['code']);
                $this->rpc->local('MobileCodeService\delMobileCode', array($user_info['mobile']));
                echo json_encode(array(
                        'errorCode' => 0,
                        'errorMsg' => '短信校验正确',
                ));
                return;
                //$this->template = "web/views/v2/account/delivery.html";
            }
        }

        if ($step == 2) {
            unset($data['code']);
            if (\es_session::get("delivery_pro_flag") != 1)
                return $this->show_error('操作失败 ！', '', 0, 0, url("account/setup"));//如果标识不为1或者不存在，则直接跳到设置界面

            if (!\es_session::get("delivery_mobile_code") || $data['phonecode'] != \es_session::get("delivery_mobile_code")) {
                \es_session::delete("sms_deli_verified");//修改成功以后删除进入delivery的标识，防止不发送短信再次进入此页面更改收获地址，防止csrf攻击
                \es_session::delete("delivery_pro_flag");//删除收获地址数据处理标识，防止csrf攻击
                \es_session::delete("delivery_mobile_code");//删除手机验证码
                return $this->show_error('操作失败,手机验证码不正确 ！', '', 0, 0, url("account/setup"));//如果手机验证码不正确，则直接跳转至设置页面
            }

            \es_session::delete("delivery_pro_flag");//删除收获地址数据处理标识，防止csrf攻击
            \es_session::delete("delivery_mobile_code");//删除手机验证码
            unset($data['phonecode']);
            if (!$data['postalcode']) {
                $data['postalcode']=null;
            }
            $data['address']=htmlspecialchars($data['address']);
            $data['user_id']=$user_info['id'];
            $user_deli_info=$this->rpc->local('DeliveryService\getInfoByUid', array($data['user_id']));
            if ($user_deli_info) {
                    $data['id']=$user_deli_info[0]['id'];
                    if (isset($data['isAjax']) && $data['isAjax'] == 1) {
                        unset($data['isAjax']);
                        $user = $this->rpc->local('DeliveryService\updateInfo', array($data));
                        echo json_encode(array(
                                'errorCode' => 0,
                                'errorMsg' => '',
                                'redirect' => '/account/setup',
                        ));
                        return;
                    } else {
                        $user = $this->rpc->local('DeliveryService\updateInfo', array($data));
                        if (user) {
                            \es_session::delete("sms_deli_verified");//修改成功以后删除进入delivery的标识，防止不发送短信再次进入此页面更改收获地址，防止csrf攻击
                            return $this->show_success('收货地址修改成功！', '', 0, 0, url("account/setup"));
                        }
                        return $this->show_error('操作失败 ！', '', 0, 0, url("account/setup"));
                    }
                } else {
                    if (isset($data['isAjax']) && $data['isAjax'] == 1) {
                        unset($data['isAjax']);
                        $user = $this->rpc->local('DeliveryService\updateInfo', array($data,'insert'));
                        echo json_encode(array(
                                'errorCode' => 0,
                                'errorMsg' => '',
                                'redirect' => '/account/setup',
                        ));
                        return;
                    } else {
                        $user = $this->rpc->local('DeliveryService\updateInfo', array($data,'insert'));print_r($user);
                        if (user) {
                            return $this->show_success('收货地址设置成功！', '', 0, 0, url("account/setup"));
                        }
                        return $this->show_error('操作失败 ！', '', 0, 0, url("account/setup"));
                    }
                }
        }
    }
}
