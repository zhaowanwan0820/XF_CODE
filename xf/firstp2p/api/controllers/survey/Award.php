<?php
/**
 * Index controller class file.
 *
 * @author 杜学风<duxuefeng@ucfgroup.com>
 * @date   2016-07-26
 **/

namespace api\controllers\survey;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use libs\utils\Rpc;
use core\service\UserService;
use core\service\RiskAssessmentService;

/**
 * 输入手机号
 * 领取优惠券
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Award extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        //为了使该页面能在浏览器中打开
        //以后app和浏览器都可以打开的h5页面写在web里，而不是写在app中
        $_SERVER['HTTP_VERSION'] = 500;
        $this->form = new Form();
        $this->form->rules = array(
            'result_id' => array(
                'filter' => 'required',
                'message' => 'result_id is required',
            ),
            'percent' => array(
                'filter' => 'required',
                'message' => 'precent is required',
            ),
            'mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码格式错误",
                "option" => array("regexp" => "/^1[3456789]\d{9}$/")
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        //验证表单令牌
        //避免和用户登录的token混淆，这里将其unset掉
        if (check_token()) {
            unset($_REQUEST['token']);
        } else {
            $this->setErr("ERR_SYSTEM");
            return false;
        }

        //用户未登录时，输入手机号领券
        //将号码存入result中
        $r = $this->rpc->local('RiskAssessmentService\updateAssessmentResultMobile', array($data['result_id'], $data['mobile']));
        if (!$r) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        //根据percent获取不同等级的投资券
        $percent = intval($data['percent']);
        $couponGroupIds = ($percent <= Gift::BOUNDARY_OF_RICH) ? Gift::DISCOUNT_OF_RICH : Gift::DISCOUNT_OF_POOR;
        //使用手机号码领券，分为手机号码已注册和未注册两种情况。
        $userInfo = $this->rpc->local('UserService\getUserIdByMobile', array($data['mobile'], true));
        if ($userInfo) {
            //检查以前是否领取过
            $isFirst = $this->rpc->local('RiskAssessmentService\isFirstAssess', array($userInfo['id'], $data['mobile']));
            if (!$isFirst) {
                $this->errno = 3;
                $this->error = "很抱歉，您已经领取过了券";
                return false;
            }
            $couponToken = Gift::FLAG.$userInfo['id'];
            $result = $this->rpc->local('O2OService\acquireDiscounts', array($userInfo['id'], $couponGroupIds, $couponToken));
            if (!$result) {
                $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
                return false;
            }
            $res = array(
                'is_give' => $result,
            );
            $this->json_data = $res;
            return true;
        }

        //未注册手机号，领券
        //检查以前是否领取过
        $isFirst = $this->rpc->local('RiskAssessmentService\isFirstAssess', array(-1, $data['mobile']));
        if (!$isFirst) {
            $this->errno = 3;
            $this->error = "很抱歉，您已经领取过了券";
            return false;
        }
        $quesInfo = $this->rpc->local('RiskAssessmentService\getEnabledQuestion', array(RiskAssessmentService::TYPE_QUESTION));
        if (!$quesInfo) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $result = $this->rpc->local('MarketingService\acqiureLogQuestionnaire', array($data['mobile'], $couponGroupIds, $quesInfo['id']), "marketing");
        $res = array(
            'is_give' => $result,
        );
        if(!$res){
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $this->json_data = $res;
    }
}
