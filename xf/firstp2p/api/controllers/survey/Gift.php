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
 * 领取优惠券
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Gift extends AppBaseAction
{
    const IS_H5 = true;
    const FLAG = "QUESTIONNAIRE_GIFT_";  //用于生成券组token
    const BOUNDARY_OF_RICH = 50 ; //小于等于此界限，则触发富人投资券
    const DISCOUNT_OF_RICH = 111; //针对于富人的投资券
    const DISCOUNT_OF_POOR = 222; //针对于穷人的投资券

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
                $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
                return false;
            }
            //检查以前是否领取过
            $isFirst = $this->rpc->local('RiskAssessmentService\isFirstAssess', array($userInfo['id']));
            if (!$isFirst) {
                $this->errno = 3;
                $this->error = "很抱歉，您已经领取过了券";
                return false;
            }
            $is_login = 1;
            //根据percent获取不同等级的投资券
            $percent = intval($data['percent']);

            $couponGroupIds = ($percent <= self::BOUNDARY_OF_RICH) ? self::DISCOUNT_OF_RICH : self::DISCOUNT_OF_POOR;
            $couponToken = self::FLAG.$userInfo['id'];
            $result = $this->rpc->local('O2OService\acquireDiscounts', array($userInfo['id'], $couponGroupIds, $couponToken));
            if (!$result) {
                $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
                return false;
            }
            $res = array(
                'is_give'   => $result,
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
