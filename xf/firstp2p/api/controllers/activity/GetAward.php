<?php
/**
* 领奖接口
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-12-02
*/
namespace api\controllers\Activity;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\UserFestivalActivitiesService;

class GetAward extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => '登录过期，请重新登录'),
                'ticket' => array('filter' => 'required', 'message' => '参数不正确'),
                'score' => array('filter' => 'required', 'message' => '参数不正确'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $activityInfo = $this->rpc->local('FestivalActivitiesService\getAward', array($userInfo,$data['ticket'],intval($data['score'])));
        if ($activityInfo) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'ret:|'.$activityInfo['log_msg'])));
            unset($activityInfo['log_msg']);
            $this->json_data = $activityInfo;
        } else {
            $this->setErr('ERR_MANUAL_REASON','请稍后再试');
            return false;
        }
    }
}
