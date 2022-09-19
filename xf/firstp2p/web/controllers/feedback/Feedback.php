<?php

/**
 * 用户反馈
 */
namespace web\controllers\feedback;

use web\controllers\BaseAction;

class Feedback extends BaseAction
{
    public function invoke() {

        $user_info = $GLOBALS ['user_info'];
        if(empty($user_info['id']) && !empty($_GET['rktoken'])){//没登录有rktoken
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $uid = $redis->get($_GET['rktoken']);
            $redis->del($_GET['rktoken']);
            $uid = intval($uid);
            if($uid > 0){
                \es_session::set('user_info', array('id'=>$uid));
                header('Location:' . get_domain() . '/feedback/feedback');
            }
        }
    }
}
