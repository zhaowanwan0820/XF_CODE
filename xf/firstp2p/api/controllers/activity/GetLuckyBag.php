<?php
/**
* 获取找福袋奖励
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-01-03
*/
namespace api\controllers\Activity;

use libs\web\Form;
use api\controllers\AppBaseAction;

class GetLuckyBag extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => '登录过期,请重新登录'),
                'ticket' => array('filter' => 'required', 'message' => 'ticket不能为空'),
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
        $activityInfo = $this->rpc->local('FestivalActivitiesService\getLuckyBag', array($userInfo['id'],$data['ticket']));
        if ($activityInfo['log_msg']) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'ret:|'.$activityInfo['log_msg'])));
            unset($activityInfo['log_msg']);
        }
        $this->json_data = $activityInfo;
    }
}
