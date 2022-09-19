<?php
/**
 * Index controller class file.
 *
 * @author 杜学风<duxuefeng@ucfgroup.com>
 * @date   2017-9-12
 **/

namespace web\controllers\survey;

use libs\utils\Rpc;
use libs\weixin\Weixin;
use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use core\service\UserService;
use core\service\WeiXinService;
use core\service\RiskAssessmentService;

/**
 * 问卷调查的首页
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Index extends SurveyBaseAction
{
    const IS_H5 = true;
    const QUESTION_IS_VALID = 1;
    const QUESTION_NOT_VALID = 0;

    public function init()
    {
        //为了使该页面能在浏览器中打开
        //以后app和浏览器都可以打开的h5页面写在web里，而不是写在app中
        $_SERVER['HTTP_VERSION'] = 500;
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
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
        //检查问卷的有效性
        $quesInfo = $this->rpc->local('RiskAssessmentService\getEnabledQuestion', array(RiskAssessmentService::TYPE_QUESTION));
        if(!$quesInfo){
            $this->tpl->assign('is_valid', self::QUESTION_NOT_VALID);
            $this->template = 'web/views/v3/survey/survey.html';
            return false;
        }
        $this->tpl->assign('is_valid', self::QUESTION_IS_VALID);
        $data = $this->form->data;
        if (isset($data['token'])) {
            //如果token存在，则检查token
            $userInfo = $this->getUserByToken();
            if (!$userInfo) {
                $this->setErr($this->errUserHasAward['errno'], $this->errUserHasAward['errmsg']); // 获取oauth用户信息失败
                return false;
            }
            $this->tpl->assign('a_token', $data['token']);
        }
        //微信分享相关
        $this->getJsApiSignature();
        $this->template = 'web/views/v3/survey/survey.html';
    }


    public function getJsApiSignature() {
        $wxService = new WeiXinService();
        $jsApiSingature = $wxService->getJsApiSignature();

        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);
    }
}
