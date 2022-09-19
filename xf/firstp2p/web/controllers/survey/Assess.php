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

/**
 * 多投首页标的列表
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Assess extends SurveyBaseAction
{
    const IS_H5 = false;

    public function init()
    {
        //为了使该页面能在浏览器中打开
        //以后app和浏览器都可以打开的h5页面写在web里，而不是写在app中
        $_SERVER['HTTP_VERSION'] = 500;
        $this->form = new Form();
        $this->form->rules = array(
            'answers' => array(
                'filter' => 'required',
                'message' => 'answers is required',
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
        $isLogin = 0; //登录与否的标志
        $userId = 0;
        if (isset($data['a_token'])) {
            $userInfo = $this->getUserByToken(true, $data['a_token']);
            if (!$userInfo) {
                $this->setErr($this->errGetUserFail['errno'], $this->errGetUserFail['errmsg']); // 获取oauth用户信息失败
                return false;
            }
            $isLogin = 1;
            $userId = $userInfo['id'];
        }
        $quesInfo = $this->rpc->local('RiskAssessmentService\getEnabledQuestion', array(RiskAssessmentService::TYPE_QUESTION));
        if (!$quesInfo) {
            $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
            return false;
        }
        $answers = json_decode($data['answers'], true);
        $subjects = array();
        foreach($answers as $v){
            if(is_array($v)){
                $subjects[] = implode($v);
                continue;
            }
            $subjects[] = $v;
        }
        $ip = get_real_ip();
        //保存做题答案并且返回问卷结果
        $res = $this->rpc->local('RiskAssessmentService\saveQuestionnaireResult', array($isLogin, $userId, $quesInfo['id'], $ip, $subjects));
        if (!$res) {
            $this->setErr($this->errSystemBusy['errno'], $this->errSystemBusy['errmsg']);
            return false;
        }
        $res['percent'] = $this->toPercent($res['score']);
        $res['share_url'] = get_domain() . "/survey/Index";
        $res['is_login'] = $isLogin;
        $this->json_data = $res;
    }


}
