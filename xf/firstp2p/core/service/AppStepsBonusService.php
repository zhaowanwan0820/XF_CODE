<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/29
 * Time: 10:44
 */

namespace core\service;

use core\dao\AppStepsBonusModel;
use \core\service\WXBonusService;

class AppStepsBonusService extends BaseService {
    /**
     * 更新用户步数信息,包括用户ID
     * @param $type 1-代表领奖    2-代表请求步数，不会更新领奖时间
     * @param $user_id
     * @param $steps
     * @param $device_no
     * @param int $is_award
     * @param int $id
     * @return mixed
     */
    public function saveStepsBonusById($user_id,$steps,$device_no,$is_award=0,$id=0,$type = 0) {
        $data = array('user_id' => $user_id,'steps' => $steps,'device_no' => $device_no,'is_award' => $is_award,'update_time' => time());
        $nowTime = time();
        if ($is_award == 1 && $type == 1) {
            $data['award_time'] = $nowTime;
        }
        if ($id == 0) {
            $data['create_time'] = $nowTime;
        }
        return AppStepsBonusModel::instance()->saveStepsBonusById($id,$data);
    }

    /**
     * 根据用户的步数获取打败用户的百分比
     * @param int $steps
     * @return bool
     */
    public function getPercentSteps($steps) {
        if (intval($steps) <= 0) {
            return 0;
        }
        return AppStepsBonusModel::instance()->getPercentSteps($steps);
    }

    /**
     * 获取所有符合uid或者device_id的用户
     * @param $uid
     * @param $device_no
     * @return mixed
     */
    public function getInfoByUidDeviceNo($uid,$device_no) {
        $uid = intval($uid);
        $device_no = htmlspecialchars($device_no);
        if ($uid == 0 || empty($device_no)) {
            return false;
        }
        return AppStepsBonusModel::instance()->getInfoByUidDeviceNo($uid,$device_no);
    }

    /**
     * 根据ID删除对应的记录
     * @param $id
     * @return mixed
     */
    public function deleteById($id) {
        if(empty($id)) {
            return fasle;
        }
        return AppStepsBonusModel::instance()->deleteById(intval($id));
    }
    /**
     * 获取用户步数信息
     * @param unknown $user_id
     * @param unknown $steps
     * @param unknown $device_no
     * @return void|multitype:number string NULL unknown
     */
    public function getStepInfo($user_id,$steps,$device_no) {
        $conf = app_conf('APP_STEPS_BONUS_CONF');
        $confValule = confToArray($conf);
        $result = array();
        $result['is_effect'] = 1;//配置有效
        if (strtotime($confValule['time_end']) < time() || $confValule['is_effect'] == 0 || strtotime($confValule['time_start']) > time()) {
            $result['is_effect'] = 0;//配置无效
            return $result;
        }
        $steps = intval($steps);
        //检查用户步数记录表中是否已经存在该用户
        $checkValue = $this->getInfoByUidDeviceNo($user_id,htmlentities($device_no));
        \libs\utils\logger::info(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'uid:'.$user_id,'device_no:'.$device_no,'check_value:',json_encode($checkValue))));
        //如果为空，则写入数据
        if(empty($checkValue)) {
            try{
                $saveRes = $this->saveStepsBonusById($user_id,$steps,htmlentities($device_no)) ;
            } catch (\Exception $e) {
                \libs\utils\logger::error(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'save failed','uid:'.$user_id,'deviceNo'.$device_no,$e->getMessage())));
                $result['is_effect'] = 0;//配置无效
                return $result;
            }
        }
        //如果非空判断有几条数据
        //如果有一条数据,则说明这个设备只对应一个用户
        //$flag 1-更新数据    2-更新数据（更新设备对应的用户数据）  3-更新数据（更新设备对应用户的数据，删除当前用户对应的数据）
        //$maxTime = mktime(23,59,59,date('m'),date('d'),date('Y'));//当天23:59:59秒
        $minTime = mktime(0,0,0,date('m'),date('d'),date('Y'));//当天0:0:0秒
        $isAward = 0;
        $flag = 0;
        //当天
        if (count($checkValue) == 1 && $checkValue['0']['update_time'] > $minTime) {
            //如果记录没有领过奖,且设备号一样，更新数据
            if ($checkValue['0']['is_award'] == 0 && $checkValue['0']['device_no'] == $device_no) {
                $id = $checkValue['0']['id'];
                $uid = $user_id;
                $isAward = $checkValue['0']['is_award'];
                $flag = 1;
            }
            //如果设备号不一样，当前记录没有领奖，并且用户uid与记录uid不同，则允许更新（将记录更新为当前用户的记录）
            if ($checkValue['0']['is_award'] == 0 && $checkValue['0']['device_no'] != $device_no) {
                $id = $checkValue['0']['id'];
                $uid = $user_id;
                $isAward = $checkValue['0']['is_award'];
                $flag = 1;
            }
            //如果记录用户领过奖，且设备号不同，则不更新，（应为现在该设备没有记录）
            if ($checkValue['0']['is_award'] == 1 && $checkValue['0']['device_no'] != $device_no) {
                $id = $checkValue['0']['id'];
                $uid = $checkValue['0']['user_id'];
                $isAward = $checkValue['0']['is_award'];
                $flag = 0;
            }
            //如果记录用户领过奖，设备号一样，则只更新此设备对应的步数
            if ($checkValue['0']['is_award'] == 1 && $checkValue['0']['device_no'] == $device_no) {
                $id = $checkValue['0']['id'];
                $uid = $checkValue['0']['user_id'];
                $isAward = $checkValue['0']['is_award'];
                $flag = 2;
            }
        }
        //如果通过uid和设备号查出来的用户有两个
        if (count($checkValue) > 1) {
            if ($checkValue['0']['user_id'] == $user_id) {
                $userRes = $checkValue['0'];
                $deviceRes = $checkValue['1'];
            } else {
                $userRes = $checkValue['1'];
                $deviceRes = $checkValue['0'];
            }
        }
        if (count($checkValue) > 1 && $userRes['update_time'] > $minTime && $deviceRes['update_time'] > $minTime) {
            //uid对应的用户没有领过奖，但是设备号对应的用户已经领过奖，则只更新设备对应用户步数$flag = 2
            //uid对应的用户领过奖，但是设备号对应的用户没有领奖，也是只更新设备对应用户步数$flag = 2
            //uid和设备都领过奖，只更新设备对应用户步数$flag = 2
            //uid和设备都没有领过奖，则更新设备对应的用户ID更新为现在的uid，删除uid先前对应的记录$flag = 3;
            if ($userRes['is_award'] == 0 && $deviceRes['is_award'] == 0) {
                $id = $deviceRes['id'];
                $delId = $userRes['id'];
                $uid = $userRes['user_id'];
                $isAward = $deviceRes['is_award'];
                $flag = 3;
            } else {
                $id = $deviceRes['id'];
                $uid = $deviceRes['user_id'];
                $isAward = $deviceRes['is_award'];
                $flag = 2;
            }
        }
        //当天处理结束

        //一个当天一个昨天
        //用户对应记录今天，设备记录昨天
        if (count($checkValue) > 1 && $userRes['update_time'] > $minTime && $deviceRes['update_time'] < $minTime) {
            //uid对应的用户没有领过奖，但是设备号对应的用户已经领过奖，则只更新设备对应用户步数，删除uid先前对应的记录$flag = 3
            //uid对应的用户领过奖，但是设备号对应的用户没有领奖，只更新设备对应用户步数$flag = 2
            //uid和设备都领过奖，只更新设备对应用户步数$flag = 2
            //uid和设备都没有领过奖，则更新设备对应的用户ID更新为现在的uid，删除uid先前对应的记录$flag = 3;
            if (($userRes['is_award'] == 0 && $deviceRes['is_award'] == 1) || ($userRes['is_award'] == 0 && $deviceRes['is_award'] == 0)) {
                $id = $deviceRes['id'];
                $delId = $userRes['id'];
                $uid = $userRes['user_id'];
                //$isAward = $deviceRes['is_award'];
                $isAward = $userRes['is_award'];
                $flag = 3;
            } else {
                $id = $deviceRes['id'];
                $uid = $deviceRes['user_id'];
                $isAward = $deviceRes['is_award'];
                $flag = 2;
            }
        }
        //用户对应昨天记录，设备对应记录今天
        if (count($checkValue) > 1 && $userRes['update_time'] < $minTime && $deviceRes['update_time'] > $minTime) {
            //uid对应的用户没有领过奖，但是设备号对应的用户已经领过奖，则只更新设备对应用户步数$flag = 2
            //uid对应的用户领过奖，但是设备号对应的用户没有领奖，删除uid先前对应的记录$flag = 3
            //uid和设备都领过奖，只更新设备对应用户步数$flag = 2
            //uid和设备都没有领过奖，则更新设备对应的用户ID更新为现在的uid，删除uid先前对应的记录$flag = 3;
            if (($userRes['is_award'] == 1 && $deviceRes['is_award'] == 0) || ($userRes['is_award'] == 0 && $deviceRes['is_award'] == 0)) {
                $id = $deviceRes['id'];
                $delId = $userRes['id'];
                $uid = $userRes['user_id'];
                $isAward = $deviceRes['is_award'];
                $flag = 3;
            } else {
                $id = $deviceRes['id'];
                $uid = $deviceRes['user_id'];
                $isAward = $deviceRes['is_award'];
                $flag = 2;
            }
        }
        //一个当天一个昨天 end

        //第二天处理开始
        if (count($checkValue) == 1 && $checkValue['0']['update_time'] < $minTime) {
            //不管是那种情况都是直接更新数据
            $id = $checkValue['0']['id'];
            $uid = $checkValue['0']['user_id'];
            $isAward = 0;
            $flag = 1;
        }
        //如果通过uid和设备号查出来的用户有两个
        if (count($checkValue) > 1 && $userRes['update_time'] < $minTime && $deviceRes['update_time'] < $minTime) {
            //第二天，不管是哪种情况都是更新当前用户对应的记录，重置获奖记录，删除设备对应的记录
            $id = $userRes['id'];
            $uid = $userRes['user_id'];
            $delId = $deviceRes['id'];
            $isAward = 0;
            $flag = 3;
        }
        //第二天处理结束
        //更新数据
        if ($flag == 1 || $flag == 2) {
            $this->saveStepsBonusById($uid,$steps,htmlentities($device_no),$isAward,$id);
        }
        if ($flag == 3) {
            $GLOBALS['db']->startTrans();
            try {
                $this->deleteById($delId);
                $this->saveStepsBonusById($uid,$steps,htmlentities($device_no),$isAward,$id);
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                \libs\utils\logger::info(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'save failed','uid:'.$user_id,'deviceNo:'.$device_no,'steps:'.$steps,'is_award:'.$isAward,'id:'.$id)));
                $GLOBALS['db']->rollback();
            }
        }
        \libs\utils\logger::info(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'save success','uid:'.$user_id,'deviceNo:'.$device_no,'steps:'.$steps,'is_award:'.$isAward,'id:'.$id)));
        //计算打败了多少人（百分比）
        $percent = $this->getPercentSteps($steps);
        $result['percent'] = '我打败了'.($percent >= 0.01 ? $percent * 100 : 1) . '%'.'的网信用户';
        //获取红包已使用库存量
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $minTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $countKey = 'stepgetbonuskey'.$minTime;//当天0点时间作为key,有效期一天，每天都会更新key
        $countBonus = $redis->get($countKey);
        $result['stock'] = intval($countBonus) >= $confValule['bonus'] ? 0 : 1;//是否还有库存 0-没有了   1-有
        $result['is_award'] = $isAward;//是否已领奖 0-未领 1-已领
        $result['tip'] = $confValule['tip'];
        $result['tips_title'] = empty($confValule['tips_title']) ? '日行万步，吃动两平衡' : $confValule['tips_title'];
        $result['max_steps'] = $confValule['steps'];
        return $result;
    }

    /**
     * 兑换红包
     * @param $user_id
     * @param $steps
     * @param $device_no
     * @return array|bool
     */
    public function stepGetBonus($user_id,$steps,$device_no) {
        $user_id = intval($user_id);
        $steps = intval($steps);
        $device_no = htmlentities($device_no);
        $conf = app_conf('APP_STEPS_BONUS_CONF');
        $confValule = confToArray($conf);
        $result = array();
        $result['is_effect'] = 1;//配置有效
        if (strtotime($confValule['time_end']) < time() || $confValule['is_effect'] == 0) {
            $result['is_effect'] = 0;//配置无效
            return $result;
        }
        $steps = intval($steps);

        //检查用户步数记录表中是否已经存在该用户
        $checkValue = $this->getInfoByUidDeviceNo($user_id,htmlentities($device_no));
        if ($steps < $confValule['steps']) {
            return false;
        }
        \libs\utils\logger::info(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'uid:'.$user_id,'steps:'.$steps,'device_no:'.$device_no,'conf_value:',json_encode($confValule),'check_value',json_encode($checkValue))));
        //如果为空，则写入数据
        if(empty($checkValue) || count($checkValue) > 1) {
            return false;
        }
        //如果非空判断有几条数据
        //如果有一条数据,则说明这个设备只对应一个用户
        if (count($checkValue) == 1) {
            //判断当前用户的设备ID是否和已有的设备ID一样，如果不一样或者已经领过将，则提示已领奖，不更新数据
            if ($checkValue['0']['is_award'] == 1 || $checkValue['0']['device_no'] != $device_no) {
                return false;
            }
            //如果当前用户没有领过奖，且达到了领奖步数,则可以更新数据(只有一个数据说明没有重复的设备号)
            if ($checkValue['0']['is_award'] == 0 &&  $checkValue['0']['steps'] == intval($steps)) {
                $flag = 1;
            } else {
                return false;
            }
        }
        //更新数据
        $result['is_award'] = 0;
        if (count($checkValue) == 1 && $flag == 1) {
            $minTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
            $countKey = 'stepgetbonuskey'.$minTime;//当天0点时间作为key,有效期一天，每天都会更新key
            $maxTime = mktime(23,59,59,date('m'),date('d'),date('Y'));
            $expireTime = $maxTime - time() + 1;//计数器key的有效期
            try{
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                //领奖计数
                $expire = $expireTime;
                $count = $redis->incr($countKey);
                $redis->EXPIRE($countKey,$expireTime);
                \libs\utils\logger::info(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'bonus_conf:'.$confValule['bonus'],'now_count:'.($count-1))));
            } catch (\Exception $e) {
                \libs\utils\logger::error(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'redis is failed','uid:'.$user_id,'deviceNo:'.$device_no,$e->getMessage())));
                return false;
            }
            $GLOBALS['db']->startTrans();
            try{
                //如果已经超过当天限额
                if ($count > $confValule['bonus']) {
                    throw new \Exception('兑换红包失败');
                }
                $update = $this->saveStepsBonusById($user_id,$checkValue['0']['steps'],htmlentities($device_no),1,$checkValue['0']['id'],1);
                if ($update) {
                    //领奖
                    $wx = new WXBonusService();
                    $result = $wx->acqureStepBonus($user_id);
                    if (empty($result)) {
                        throw new \Exception('兑换红包失败');
                    }
                    $result['is_award'] = 1;//是否已领奖 0-未领 1-已领
                    $result['share_url'] = $result['shareUrl'];//红包接口返回
                    $result['share_title'] = $result['shareTitle'];//分享title
                    $result['share_img'] = $result['shareIcon'];//分享图片
                    $result['share_content'] = $result['shareContent'];//分享内容
                    \libs\utils\logger::info(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'save success','uid:'.$user_id,'deviceNo:'.$device_no,'steps:'.$steps)));
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $redis->decr($countKey);
                \libs\utils\logger::error(implode('|',array(str_replace('\\','|',__CLASS__),__FUNCTION__,'save failed','uid:'.$user_id,'deviceNo:'.$device_no,'steps:'.$steps,$e->getMessage())));
                return false;
            }
        }
        return $result;
    }

    public function getWalk($userId)
    {
        $condition = "user_id=:user_id and datediff(current_timestamp,from_unixtime(update_time))=0";
        $params = [':user_id'=>$userId];
        if ($res = AppStepsBonusModel::instance()->findByViaSlave($condition, '*', $params)) {
            return $res->getRow();
        }
        return [];

    }

    public function getStepsByUserId($userId)
    {
        try {
            $condition = "user_id='$userId' AND is_award=0 AND update_time > ".mktime(0, 0, 0);
            $result = AppStepsBonusModel::instance()->findByViaSlave($condition);
            if ($result) {
                $result = $result->getRow();
            }
        } catch (Exception $e) {
            \libs\utils\logger::error(implode('|',[__CLASS__, __FUNCTION__, "uid:$userId", $e->getMessage()]));
            $result = [];
        }

        return $result;
    }

    public function checkUserById($userId)
    {
        try {
            $result = \core\service\marketing\StepBonusService::isLock($userId);
        } catch (\Exception $e) {
            \libs\utils\logger::error(implode('|',[__CLASS__, __FUNCTION__, "uid:$userId", $e->getMessage()]));
            $result = 0;
        }

        return $result;
    }

}
