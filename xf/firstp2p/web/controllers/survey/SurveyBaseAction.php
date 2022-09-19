<?php
/**
 * Index controller class file.
 *
 * @author 杜学风<duxuefeng@ucfgroup.com>
 * @date   2017-9-12
 **/

namespace web\controllers\survey;

use libs\web\Form;
use api\controllers\AppBaseAction;
use web\controllers\BaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use libs\utils\Rpc;
use core\service\UserService;

/**
 * 问卷调查的首页
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class SurveyBaseAction extends BaseAction
{
    const FLAG = "QUESTIONNAIRE_GIFT_";  //用于生成券组token
    const BOUNDARY_OF_RICH = 50 ; //小于等于此界限，则触发富人投资券
    protected $current_token_user = false; //用于存用户信息
    public $errGetUserFail = array(
        'errno'     => 40002,
        'errmsg'    => "登录过期了，为了您的账户安全，请重新登录",
    );
    public $errSystemBusy = array(
        'errno'     =>  210067,
        'errmsg' => "人气大爆炸，系统有点忙",
    );
    public $errUserHasAward = array(
        'errno'     =>  3,
        'errmsg' => "每人只能领一次哟，快来赚钱吧",
    );

    public $discountOfRich = 0 ; //针对于富人的投资券
    public $discountOfPoor = 0 ; //针对于穷人的投资券

    public function __construct(){
        parent::__construct();
        $this->discountOfRich = intval(app_conf("QUESTIONNAIRE_COUPON_ACHIEVEMENT"));
        $this->discountOfPoor = intval(app_conf("QUESTIONNAIRE_COUPON_CURE"));
    }



    /**
     * 根据token获取登陆用户信息
     * @param bool $need_err
     * @param string $token
     * @return bool|array
     */
    protected function getUserByToken($need_err = true, $token = '') {
        if (empty($this->current_token_user)) {
            $token = isset($this->form->data['token']) ? $this->form->data['token'] : (!empty($token) ? $token : '');
            if (empty($token)) {
                return false;
            }
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (!empty($token_info['code'])) {
                if ($need_err == true) {
                    $this->setErr($this->errGetUserFail['errno'], $this->errGetUserFail['errmsg']); // 获取oauth用户信息失败
                }
                return false;
            }
            $this->current_token_user = $token_info['user'];
            $GLOBALS['user_info'] = $token_info['user'];
        }

        return $this->current_token_user;
    }
    /**
     * 针对于穷癌调查的计算公式
     * @param score 分数
     * @return 百分比
     */
    public function toPercent($s)
    {
        //穷癌调查中分数为整数
        return $s >= 21 ? 0 : ($s <= 1 ? 100 : (100 - 5*($s -1)));
    }
}
