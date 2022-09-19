<?php
/**
* 获取有效的节日活动信息
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-12-02
*/
namespace api\controllers\Activity;

use libs\web\Form;
use api\controllers\AppBaseAction;

class GetFestivalActivitiesInfo extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => '登录过期，请重新登录'),
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
        $activityInfo = $this->rpc->local('FestivalActivitiesService\getActivityInfo', array());
        if (isset($activityInfo) && empty($activityInfo)) {
            $res['is_have_game'] = false;
            $res['msg'] = '没有正在进行的活动游戏';
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid'.$userInfo['id'],"没有正在进行的活动游戏")));
            $activityInfo = $this->rpc->local('FestivalActivitiesService\getActivityNearInfo', array());
            if (isset($activityInfo) && empty($activityInfo)) {
                $res['is_have_activity'] = false;
                $res['activity_msg'] = '没有活动';
                $this->json_data = $res;
                return ;
            }
        } else {
            $res['is_have_game'] = true;//有正在进行的活动游戏
        }
        $ret = $activityInfo['0'];
        $ret['is_have_game'] = $res['is_have_game'];//有活动
        $ret['is_have_activity'] = true;//有活动
        $userActivityInfo = $this->rpc->local('UserFestivalActivitiesService\getUserActivityInfo', array($userInfo['id']));
        if ($res['is_have_game'] && $activityInfo['0']['id'] == $userActivityInfo['0']['activity_id'] && !empty($activityInfo['0']['count_limit']) && $userActivityInfo['0']['current_count'] >= $activityInfo['0']['count_limit']) {
            $ret['is_have_game'] = false;
            $ret['msg'] = '游戏次数已达上限';
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid'.$userInfo['id'],"游戏次数已达上限")));
        }

        $flag = $this->timeJudge($userActivityInfo['0']['game_start_time']);
        if ($flag && $activityInfo['0']['id'] == $userActivityInfo['0']['activity_id'] && $res['is_have_game'] && !empty($activityInfo['0']['count_limit_day']) && $userActivityInfo['0']['current_count_day'] >= $activityInfo['0']['count_limit_day']) {
            $ret['is_have_game'] = false;
            $ret['msg'] = '当天游戏次数已达上限';
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid|'.$userInfo['id'],"当天游戏次数已达上限")));
        }

        //$ret['is_have_game'] = $res['is_have_game'];
        //用户领奖凭证
        $ticket = md5($userInfo['id'].$activityInfo['0']['id']);
        $ret['ticket'] = $ticket;
        $ret['img_conf'] = json_decode($activityInfo['0']['img_conf'],true);
        unset($ret['prize_conf']);
        unset($ret['count_limit_day']);
        unset($ret['count_limit']);
        $imgConf = $this->handleImgConf($ret['img_conf']);
        $ret['img_conf'] = $imgConf;
        $this->json_data = $ret;
    }
    //处理img_conf,将配置中的数字脚表去掉
    public function handleImgConf($imgConf) {
        foreach ($imgConf as $key=>$value) {
            if (stripos($key,'drop') !== false) {
                //如果配置为空则不处理，不作为返回数据
                if (count(array_keys ($value, "" )) !=0) {
                    unset($imgConf[$key]);
                    continue;
                }
                foreach ($value as $key1=>$value1) {
                    $pattern = '/_\d\d/i' ;
                    $replacement = '';
                    $keyRe = preg_replace( $pattern , $replacement , $key1);
                    $res[$keyRe] = $value1;
                }
                $imgConf['drop'][] = $res;
                unset($imgConf[$key]);
            }
        }
        return $imgConf;
    }
    //判断当前时间在不在最近开始玩儿游戏的一个自然天内
    private function timeJudge($time) {
        $head_time = mktime(23,59,59,date('m'),date('d'),date('Y'));//当天凌晨时间
        $base_time = mktime(0,0,0,date('m'),date('d'),date('Y'));//当天零点时间
        if($time >= $base_time && $time <= $head_time) {
            return true;
        }
        return false;
    }
}
