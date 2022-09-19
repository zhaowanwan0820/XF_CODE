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
 * 领取优惠券
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Gift extends SurveyBaseAction
{
    const IS_H5 = false;

    public function init()
    {
        //为了使该页面能在浏览器中打开
        //以后app和浏览器都可以打开的h5页面写在web里，而不是写在app中
        $_SERVER['HTTP_VERSION'] = 500;
        $this->form = new Form();
        $this->form->rules = array(
            'percent' => array(
                'filter' => 'required',
                'message' => 'percent is required',
            ),
            'result_id' => array(
                'filter' => 'required',
                'message' => 'result_id is required',
            ),
            'a_token' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
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
        $is_login = 0; //登录与否的标志

        //检查用户登录状态
        if (isset($data['a_token'])) {
            //登录用户领券
            $userInfo = $this->getUserByToken(true, $data['a_token']);
            if (!$userInfo) {
                $this->setErr($this->errGetUserFail['errno'], $this->errGetUserFail['errmsg']); // 获取oauth用户信息失败
                return false;
            }
            //检查以前是否领取过
            //显示弹窗，并且前端页面跳转
            $res = $this->rpc->local('RiskAssessmentService\isFirstAssess', array($userInfo['id'], $userInfo['mobile']));
            if (!$res['isFirst']) {
                //获取第一次领券的评估结果
                $data = $res['data'];
                $data['errmsg'] = $this->errUserHasAward['errmsg'];
                $data['percent'] = $this->toPercent($data['score']);
                $this->json_data = $data;
                return false;
            }
            $is_login = 1;
            //根据percent获取不同等级的投资券
            $percent = intval($data['percent']);

            $couponGroupIds = ($percent <= self::BOUNDARY_OF_RICH) ? $this->discountOfRich : $this->discountOfPoor;
            $couponToken = self::FLAG.$userInfo['id'];
            //领取投资券
            $result1 = $this->rpc->local('O2OService\acquireDiscounts', array($userInfo['id'], $couponGroupIds, $couponToken));
            //领取券，更新result里的领券状态
            $result2 = $this->rpc->local("RiskAssessmentService\updateAssessmentResultAward", array($data['result_id' ], UserAssessmentResultModel::GET_AWARD));
            if ((!$result1) || (!$result2)) {
                $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
                return false;
            }
            $res = array(
                'is_give'   => $result1,
                'is_login'  => $is_login,
            );
            $this->json_data = $res;
            return true ;
        }
        //未登录，is_login设置为0
        $resultData = array('is_login' => $is_login);
        $this->json_data = $resultData;
    }
}
