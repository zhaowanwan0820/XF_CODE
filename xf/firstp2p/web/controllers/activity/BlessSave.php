<?php
/**
 * Three Years Old
 * @author longbo
 */
namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\marketing\BlessService;
use core\service\BonusService;


class BlessSave extends BlessBase
{

    public function init()
    {
        parent::init();

        if (!$this->checkTime()) {
            $ret = ['error' => 20004, 'msg' => '活动已结束'];
            return ajax_return($ret);
        }

        $this->form = new Form("post");
        $this->form->rules = array(
            'info' => array("filter" => "string", "option" => array("optional" => true)),
            "mobile" => array('filter' => 'required', "message" => '手机号不能为空'),
        );
        if (!$this->form->validate()) {
            $ret = ['error' => 20001, 'msg' => $this->form->getErrorMsg()];
            return ajax_return($ret);
        }
        $this->mobile = $this->form->data['mobile'];
        $this->info = $this->form->data['info'];

        if (!check_mobile($this->mobile)) {
            $ret = ['error' => 20002, 'msg' => '手机号格式不正确'];
            return ajax_return($ret);
        }

        if(!check_token()) { //验证表单令牌
            $ret = ['error' => 20003, 'msg' => '服务端异常'];
            return ajax_return($ret);
        }
    }

    public function invoke()
    {

        $res = (new BlessService)->createInfo($this->openid, $this->mobile, $this->info);

        if ($res) {
            $res = BonusService::encrypt($res, 'E');
            $ret = array('error' => 0, 'msg' => 'ok', 'data' => ['sn' => $res]);
            return ajax_return($ret);
        }
        $ret = ['error' => 10001, 'msg' => '服务端异常'];
        return ajax_return($ret);
    }

}


