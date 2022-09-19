<?php
/**
 * Index controller class file.
 *
 * @author 杜学风<duxuefeng@ucfgroup.com>
 * @date   2016-07-26
 **/

namespace web\controllers\survey;

use libs\web\Form;
use api\controllers\AppBaseAction;
use web\controllers\BaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use libs\utils\Rpc;
use core\service\UserService;
use core\service\RiskAssessmentService;
use core\dao\UserAssessmentResultModel;

/**
 * 输入手机号
 * 领取优惠券
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Award extends SurveyBaseAction
{
    const IS_H5 = false;

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
            $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
            return false;
        }

        //用户未登录时，输入手机号领券
        //将号码存入result中
        $r = $this->rpc->local('RiskAssessmentService\updateAssessmentResultMobile', array($data['result_id'], $data['mobile']));
        if (!$r) {
            $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
            return false;
        }
        //根据percent获取不同等级的投资券
        $percent = intval($data['percent']);
        $couponGroupIds = ($percent <= self::BOUNDARY_OF_RICH) ? $this->discountOfRich : $this->discountOfPoor;
        //使用手机号码领券，分为手机号码已注册和未注册两种情况。
        $userId = $this->rpc->local('UserService\getUserIdByMobile', array($data['mobile'], true));
        if (!empty($userId)) {
            //检查以前是否领取过
            $res = $this->rpc->local('RiskAssessmentService\isFirstAssess', array($userId, $data['mobile']));
            if (!$res['isFirst']) {
                //获取第一次领券的评估结果
                $data = $res['data'];
                $data['errmsg'] = $this->errUserHasAward['errmsg'];
                $data['percent'] = $this->toPercent($data['score']);
                $this->json_data = $data;
                return false;
            }
            $couponToken = Gift::FLAG.$userId;
            //领取投资券
            $result1 = $this->rpc->local('O2OService\acquireDiscounts', array($userId, $couponGroupIds, $couponToken));
            //领取券，更新result里的领券状态
            $result2 = $this->rpc->local("RiskAssessmentService\updateAssessmentResultAward", array($data['result_id'], UserAssessmentResultModel::GET_AWARD));
            if ((!$result1) || (!$result2)) {
                $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
                return false;
            }
            $res = array(
                'is_give'   => $result1,
            );
            $this->json_data = $res;
            return true;
        }

        //未注册手机号，领券
        //检查以前是否领取过
        $res = $this->rpc->local('RiskAssessmentService\isFirstAssess', array(-1, $data['mobile']));
        if (!$res['isFirst']) {
            //获取第一次领券的评估结果
            $data = $res['data'];
            $data['errmsg'] = $this->errUserHasAward['errmsg'];
            $data['percent'] = $this->toPercent($data['score']);
            $this->json_data = $data;
            return false;
        }
        $quesInfo = $this->rpc->local('RiskAssessmentService\getEnabledQuestion', array(RiskAssessmentService::TYPE_QUESTION));
        if (!$quesInfo) {

            $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
            return false;
        }
        //领取投资券
        $result1 = $this->rpc->local('MarketingService\acqiureLogQuestionnaire', array($data['mobile'], $couponGroupIds, $quesInfo['id']), "marketing");
        //领取券，更新result里的领券状态
        $result2 = $this->rpc->local("RiskAssessmentService\updateAssessmentResultAward", array($data['result_id'], UserAssessmentResultModel::GET_AWARD));
        if ((!$result1) || (!$result2)) {
            $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
            return false;
        }
        $res = array(
            'is_give' => $result1,
        );
        $this->json_data = $res;
    }
}
