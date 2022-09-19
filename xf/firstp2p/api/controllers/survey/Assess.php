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
 * 多投首页标的列表
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Assess extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        //为了使该页面能在浏览器中打开
        //以后app和浏览器都可以打开的h5页面写在web里，而不是写在app中
        $_SERVER['HTTP_VERSION'] = 500;
        $this->form = new Form();
        $this->form->rules = array(
            'a_token' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'answers' => array(
                'filter' => 'required',
                'message' => 'answers is required',
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
                $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
                return false;
            }
            $isLogin = 1;
            $userId = $userInfo['id'];
        }
        $quesInfo = $this->rpc->local('RiskAssessmentService\getEnabledQuestion', array(RiskAssessmentService::TYPE_QUESTION));
        if (!$quesInfo) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $res = explode(",", $data['answers']);
        $subjects = array("   ", "  ", "  ", "   ");
        for ($i = 0; $i < 4; $i++) {
            if ($i == 0) {
                $temp = array_slice($res, 0, count($res) - 3);
                $subjects[$i] = implode($temp);
            } else {
                $subjects[$i] = $res[$i + count($res) - 4];
            }
        }
        $ip = get_real_ip();
        //保存做题答案并且返回问卷结果
        $res = $this->rpc->local('RiskAssessmentService\saveQuestionnaireResult', array($isLogin, $userId, $quesInfo['id'], $ip, $subjects));
        if (!$res) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;

        }
        $res['percent'] = $this->toPercent($res['score']);
        $res['share_url'] = get_domain() . "/survey/Index";
        $res['is_login'] = $isLogin;
        $this->json_data = $res;
    }

    /**
     * 针对于懒癌调查的计算公式
     * @param score 分数
     * @return 百分比
     */
    public function toPercent($score)
    {
        //懒癌调查中分数为整数
        $score = floor($score);
        $percent = floatval('0');
        if ($score >= 21) {
            return $percent;
        }
        if ($score <= 1) {
            $percent = floatval('100');
            return $percent;
        }
        return (1 - ($score - 1) / 20) * 100;

    }

}
