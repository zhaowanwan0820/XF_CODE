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

class Upvote extends BlessBase
{

    public function init()
    {
        parent::init();

        if (!$this->checkTime()) {
            $ret = ['error' => 20004, 'msg' => '活动已结束'];
            return ajax_return($ret);
        }

        if (empty($this->blessId)) {
            $ret = ['error' => 20001, 'msg' => '缺少参数'];
            return ajax_return($ret);
        }

        if(!check_token()) { //验证表单令牌
            $ret = ['error' => 20003, 'msg' => '服务端异常'];
            return ajax_return($ret);
        }
    }

    public function invoke()
    {
        $res = (new BlessService)->upvote($this->openid, $this->blessId);

        if ($res) {
            $ret = [
                'error' => 0,
                'msg' => 'ok',
                'data' => [
                    'cnt' => intval($res),
                    'img' => $this->wxInfo['user_info']['headimgurl'],
                ],
            ];
            return ajax_return($ret);
        }
        $ret = ['error' => 10001, 'msg' => '服务端异常'];
        return ajax_return($ret);
    }

}


