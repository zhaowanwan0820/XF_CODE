<?php
/**
* FestivalActivitiesService.php
*
* @date 2016-12-02
* @author zhaohui <zhaohui3@ucfgroup.com>
*/

namespace core\service;

use core\dao\FestivalActivitiesModel;
use core\service\UserFestivalActivitiesService;
use core\service\O2OService;
use libs\lock\LockFactory;
use libs\utils\Alarm;
use core\service\ApiConfService;
use core\service\candy\CandyActivityService;
/**
 * Class FestivalActivitiesService
 * @package core\service
 */
class FestivalActivitiesService extends BaseService {

    /**
     * 查询当前可进行的活动(如果同时存在多个有效的活动，取id最小的那个做为当前的活动)
     * @param
     * @return 活动信息
     */
    public function getActivityInfo() {
        $time = time();
        $FestivalActivities = FestivalActivitiesModel::instance();
        $fields = 'id,type,name,duration,img_conf,prize_conf,count_limit_day,count_limit,start_time,end_time';
        //活动有效，并且在有效期范围之内,如果有多条则取最小id那个
        $condition = "`start_time` < '{$time}' and `end_time` > '{$time}' and `is_effect` = '1' ORDER BY `id` ASC LIMIT 1";
        return $FestivalActivities->getActivityInfoByCondition($condition,$is_array=true,$fields, $params = array());
    }
    /**
     * 查询所有活动开始时间距离当前最近的活动
     * @param
     * @return 活动信息
     */
    public function getActivityNearInfo() {
        $time = time();
        $FestivalActivities = FestivalActivitiesModel::instance();
        $fields = 'id,type,name,duration,img_conf,prize_conf,count_limit_day,count_limit,start_time,end_time';
        //活动有效，并且在有效期范围之内,如果有多条则取最小id那个
        $condition = "`start_time` > '{$time}' ORDER BY `start_time` ASC LIMIT 1";
        return $FestivalActivities->getActivityInfoByCondition($condition,$is_array=true,$fields, $params = array());
    }
    /**
     * 根据活动id查询正在进行的活动信息
     * @param $id活动id
     * @param $is_effect是否查询正在进行的活动
     * @return 活动信息
     */
    public function getActivityInfoById($id,$is_effect = false,$is_slave = false) {
        $FestivalActivities = FestivalActivitiesModel::instance();
        $fields = 'id,type,name,duration,img_conf,prize_conf,count_limit_day,count_limit,start_time,end_time,is_effect';
        return $FestivalActivities->getActivityInfoById($id,$is_effect,$fields,$is_slave);
    }
    /**
     * 更新活动信息
     * @param  更新数据信息 id：活动id，name:活动名称，type：活动类型 ，duration：活动持续时间，img_conf:图片相关配置，prize_conf：奖励相关配置
     * @return boolean
     */
    public function updateActivityInfo($data) {
        if (!empty($data)) {
            return FestivalActivitiesModel::instance()->updateActivityInfo($data);
        }
        return false;
    }

    /**
     * 游戏开始，并进行用户信息更新（更新用户参与次数等数据）
     * @param $userInfo,用户信息，$id :活动id
     * @return array
     */
    public function activityGameStart($userInfo,$id) {
        if (!$userInfo || !$id) {
            return false;
        }
        $activityInfo = $this->getActivityInfo();
        if (isset($activityInfo) && empty($activityInfo)) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|活动id|'.$id.'没有相关活动';
            $msg['is_have_game'] = false;
            $msg['msg'] = '没有相关活动';
            return $msg;
        }
        if ($activityInfo['0']['id'] != $id) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|活动id|'.$id.'与当前进行的id不同'.$activityInfo['0']['id'];
            $msg['is_have_game'] = false;
            $msg['msg'] = '请求的活动不存在，请刷新重试';
            return $msg;
        }
        $ret['is_have_game'] = true;
        //用户领奖凭证
        $ticket = md5($userInfo['id'].$activityInfo['0']['id'].time().rand(1000,9999));
        $ret['ticket'] = $ticket;
        $ret['log_msg'] = '用户：|'.$userInfo['id'].'|活动id|'.$id.'游戏可以开始'.'|ticket|'.$ticket;
        //开始活动时，即认为已经用了一次游戏机会，用户游戏表中当日已玩儿次数加一
        $UserFestivalActivitiesService = new UserFestivalActivitiesService();
        $userActivityInfo = $UserFestivalActivitiesService->getUserActivityInfo($userInfo['id']);
        //redis 实例化
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis  === NULL) {
            $alarm_data = __CLASS__.'|'.__FUNCTION__.':|'.'|uid|'.$userInfo['id'].'|activity_id:|'.$activityInfo['0']['id'];
            $alarm_title = '游戏开始redis异常不能更新redis中缓存的用户信息';
            Alarm::push('p2p_festival_activities',$alarm_title,$alarm_data);
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'activitygamestart_redis_is_null:|'.'|uid|'.$userInfo['id'].'|activity_id:|'.$activityInfo['id'],"redis异常不能更新redis中缓存的用户信息")));
        }
        $activityCount = isset($userActivityInfo['0']['activity_count']) ? $userActivityInfo['0']['activity_count'] : 1;
        $redis_key = 'FestivalActivitiesService_'.$userInfo['id'].'_'.$activityInfo['0']['id'].'_'.$activityCount;
        $data = array();
        //如果用户表里没有数据,则此用户为第一次参加活动，将用户信息记入表中
        if (isset($userActivityInfo) && empty($userActivityInfo)) {
            $data = array(
                    'user_id'=>$userInfo['id'],
                    'activity_id' => $activityInfo['0']['id'],
                    'activity_name' => $activityInfo['0']['name'],
                    'activity_type' => $activityInfo['0']['type'],
                    'game_start_time' => time(),
                    'current_count_day' => 1,
                    'current_count' => 1,
                    'activity_count' => 1,
                    'game_count' => 1,
                    'activity_flag' => 1,
                    'ticket' => $ticket,
                    'award' => ''
            );
            $insertInfo = $UserFestivalActivitiesService->insertActivityInfoById($data);
            if (!$insertInfo) {
                $msg['log_msg'] = '用户：|'.$userInfo['id'].'|首次插入用户信息失败|';
                $msg['is_have_game'] = false;
                $msg['msg'] = '获取失败，请稍后再试！';
                return $msg;
            }
            //redis:key为'FestivalActivitiesService_'.$userInfo['id'].'_'.$activityInfo['0']['id'].'_'.$userActivityInfo['0']['activity_count']，永久存储活动名称，活动类型，活动次数，游戏总次数，每次活动获得的奖品情况（包括活动id、分数、奖品、奖品对应的券组）
            $redis->hset($redis_key,'activity_name',$data['activity_name']);
            $redis->hset($redis_key,'activity_type',$data['activity_type']);
            $redis->hset($redis_key,'activity_count',$data['activity_count']);
            $redis->hset($redis_key,'game_count',$data['game_count']);
            $redis->hset($redis_key,'award','');
            return $ret;
        }
        //如果用户表里有数据，则进行后续判断
        if ($userActivityInfo['0']['user_id']) {
            //如果表里的数据活动id和现有的活动id不同，则更新活动信息，包括活动id，活动参与此事，游戏开始时间
            if ($activityInfo['0']['id'] != $userActivityInfo['0']['activity_id']) {
                $data = array(
                        'id' => $userActivityInfo['0']['id'],
                        'activity_id' => $activityInfo['0']['id'],
                        'activity_name' => $activityInfo['0']['name'],
                        'activity_type' => $activityInfo['0']['type'],
                        'game_start_time' => time(),
                        'current_count_day' => 1,
                        'current_count' => 1,
                        'activity_count' => $userActivityInfo['0']['activity_count'] + 1,
                        'game_count' => $userActivityInfo['0']['game_count'] + 1,
                        'activity_flag' => 1,
                        'ticket' => $ticket,
                        'award' => '',
                );
                $updateInfo = $UserFestivalActivitiesService->updateActivityInfoById($data);
                if (!$updateInfo) {
                    $msg['log_msg'] = '用户：|'.$userInfo['id'].'|更新用户信息失败|';
                    $msg['is_have_game'] = false;
                    $msg['msg'] = '获取失败，请稍后再试！';
                    return $msg;
                }
                //redis:key为'FestivalActivitiesService_'.$userInfo['id'].'_'.$activityInfo['0']['id']，永久存储活动名称，活动类型，活动次数，游戏总次数，每次活动获得的奖品情况（包括活动id、分数、奖品、奖品对应的券组）
                $redis_key = 'FestivalActivitiesService_'.$userInfo['id'].'_'.$activityInfo['0']['id'].'_'.$data['activity_count'];
                $redis->hset($redis_key,'activity_name',$data['activity_name']);
                $redis->hset($redis_key,'activity_type',$data['activity_type']);
                $redis->hset($redis_key,'activity_count',$data['activity_count']);
                $redis->hset($redis_key,'game_count',$data['game_count']);
                $redis->hset($redis_key,'award','');
                return $ret;
            }

            //判断是否达到活动总次数限制
            if (!empty($activityInfo['0']['count_limit']) && $userActivityInfo['0']['current_count'] >= $activityInfo['0']['count_limit']) {
                if ($userActivityInfo['0']['activity_flag'] == 1) {
                    $data = array(
                            'id' => $userActivityInfo['0']['id'],
                            'activity_flag' => 0,
                    );
                    $updateInfo = $UserFestivalActivitiesService->updateActivityInfoById($data);
                    if (!$updateInfo) {
                        $msg['log_msg'] = '用户：|'.$userInfo['id'].'|更新用户信息失败|';
                        $msg['is_have_game'] = false;
                        $msg['msg'] = '获取失败，请稍后再试！';
                        return $ret;
                    }
                }
                $msg['log_msg'] = '用户：|'.$userInfo['id'].'|本轮活动的游戏已经结束|'.'count|'.$userActivityInfo['0']['current_count'].'|limit'.$activityInfo['0']['count_limit'];
                $msg['is_have_game'] = false;
                $msg['msg'] = '本轮活动的游戏已经结束';
                return $msg;
            }
            //判断是不是当天游戏时间，如果不是则更新当天的游戏开始时间
            $flag = $this->timeJudge($userActivityInfo['0']['game_start_time']);
            if ($flag) {
                //判断是否达到当天次数的限制
                if (!empty($activityInfo['0']['count_limit_day']) && $userActivityInfo['0']['current_count_day'] >= $activityInfo['0']['count_limit_day']) {
                    $data = array(
                            'id' => $userActivityInfo['0']['id'],
                            'ticket' => '',//将领奖ticket删除
                    );
                    $updateInfo = $UserFestivalActivitiesService->updateActivityInfoById($data);
                    if (!$updateInfo) {
                        $msg['log_msg'] = '用户：|'.$userInfo['id'].'|更新用户信息失败|';
                        $msg['is_have_game'] = false;
                        $msg['msg'] = '获取失败，请稍后再试！';
                        return $ret;
                    }
                    $msg['log_msg'] = '用户：|'.$userInfo['id'].'|您今天的游戏次数已达上限|'.'count_day|'.$userActivityInfo['0']['current_count_day'].'|limit|'.$activityInfo['0']['count_limit_day'];
                    $msg['is_have_game'] = false;
                    $msg['msg'] = '您今天的游戏次数已达上限';
                    return $msg;
                }
                //游戏开始时间是当天游戏时间，当天游戏次数、活动总游戏次数和用户所有活动的游戏次数加1
                $data = array(
                        'id' => $userActivityInfo['0']['id'],
                        'ticket' => $ticket,
                        'current_count_day' => $userActivityInfo['0']['current_count_day'] + 1,
                        'current_count' => $userActivityInfo['0']['current_count'] + 1,
                        'game_count' => $userActivityInfo['0']['game_count'] + 1,
                );
            } else {
                //游戏开始时间不是当天游戏时间，当天游戏次数、活动总游戏次数和用户所有活动的游戏次数加1，并更新游戏开始时间
                $data = array(
                        'id' => $userActivityInfo['0']['id'],
                        'ticket' => $ticket,
                        'current_count_day' => 1,
                        'current_count' => $userActivityInfo['0']['current_count'] + 1,
                        'game_start_time' =>time(),
                        'game_count' => $userActivityInfo['0']['game_count'] + 1,
                );
            }
            $updateInfo = $UserFestivalActivitiesService->updateActivityInfoById($data);
            if (!$updateInfo) {
                $msg['log_msg'] = '用户：|'.$userInfo['id'].'|更新用户信息失败|';
                $msg['is_have_game'] = false;
                $msg['msg'] = '获取失败，请稍后再试！';
                return $msg;
            }
            $redis->hset($redis_key,'game_count',$data['game_count']);
            return $ret;
        }
    }

    /**
     * 领奖
     * @param $userInfo: 用户信息, $ticket: 用户领奖凭证 , $score：用户分数
     * @return array:返回领奖结果信息
     */
    public function getAward($userInfo,$ticket,$score) {
        if (!$userInfo || !$ticket) {
            return false;
        }
        if (!$score) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|分数没传或者为0';
            $msg['is_have_award'] = false;
            $msg['msg'] = '好可惜未中奖';
            return $msg;
        }
        //开始活动时，即认为已经用了一次游戏机会，用户游戏表中当日已玩儿次数加一
        $UserFestivalActivitiesService = new UserFestivalActivitiesService();
        $userActivityInfo = $UserFestivalActivitiesService->getUserActivityInfo($userInfo['id']);
        //如果用户表里没有数据,则返回错误
        if (isset($userActivityInfo) && empty($userActivityInfo)) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|用户表中没有用户游戏信息，应该是没有点击开始游戏，直接请求的接口';
            $msg['is_have_award'] = false;
            $msg['msg'] = '没对应的活动';
            return $msg;
        }
        if ($userActivityInfo['0']['ticket'] != $ticket) {
            $msg['log_msg'] = '用户：'.$userInfo['id'].'的ticket和数据库里的ticket不一样'.$ticket.'数据库里的ticket：'.$userActivityInfo['0']['ticket'];
            $msg['is_have_award'] = false;
            $msg['msg'] = '领奖失败';
            return $msg;
        }
        // 悲观锁
        $lockKey = "FestivalActivitiesService_getAward".$userActivityInfo['0']['activity_id'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 120)) {
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid'.$userInfo['id'],"锁被占用，没有释放")));
            return false;
        }
        $activityInfo = $this->getActivityInfoById(intval($userActivityInfo['0']['activity_id']),true,true);
        if (!$activityInfo) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|用户表里的活动id|'.$userActivityInfo['0']['activity_id'].'|在活动列表里没有';
            $msg['is_have_award'] = false;
            $msg['msg'] = '活动不存在';
            return $msg;
        }
        $prizeConf = json_decode($activityInfo['prize_conf'],true);
        $prizeDiscounts = array();//投资券
        $prizeCoupons = array();//礼券
        $candyAmount = 0;//信力直
        $haveZero = 0;//有符合条件的但是库存没有了
        //查看是否有对应分数的奖励
        foreach ($prizeConf as $key=>$value) {
            if ($score >= $value['low'] && $score < $value['high']) {
                if ($value['type'] == 2)  {
                    //信力奖品不考虑库存
                    $candyAmount = intval($value['prize_id']);
                    $prizeConf[$key]['use_count'] += 1;
                    break;
                } else {
                    if ($value['count'] - $value['use_count'] > 0) {
                        //券组类型0:礼券 1：投资券
                        if ($value['type'] == 0)
                            $prizeCoupons[] = $value['prize_id'];
                        if ($value['type'] == 1)
                            $prizeDiscounts[] = $value['prize_id'];
                        //使用库存+1
                        $prizeConf[$key]['use_count'] += 1;
                    } elseif ($value['count'] - $value['use_count'] == 0) {
                        $haveZero = 1;//有符合条件的但是库存没有了
                    }
                }
            }
        }
        //如果有对应分数的奖励但是奖励库存都没有了，则降级查询的第一个可获得的奖品
        if (empty($prizeCoupons) && empty($prizeDiscounts) && $haveZero == 1) {
            $prizeConfRev = array_reverse($prizeConf);
            foreach ($prizeConfRev as $key=>$value) {
                if ($score > $value['high'] && $value['count'] - $value['use_count'] > 0) {
                    //券组类型0:礼券 1：投资券
                    if ($value['type'] == 0)
                        $prizeCoupons[] = $value['prize_id'];
                    if ($value['type'] == 1)
                        $prizeDiscounts[] = $value['prize_id'];
                    //使用库存+1
                    $prizeConfRev[$key]['use_count'] += 1;
                    break;
                }
            }
            $prizeConf = array_reverse($prizeConfRev);
        }

        if (empty($prizeCoupons) && empty($prizeDiscounts) && $haveZero == 1) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|用户得分可以获得奖品，但是奖品库存已用完';
            $msg['is_have_award'] = false;
            $msg['msg'] = '奖品已抢光';
            return $msg;
        }
        if (empty($prizeCoupons) && empty($prizeDiscounts) && empty($candyAmount)) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|用户得分没有得到相应的奖品';
            $msg['is_have_award'] = false;
            $msg['msg'] = '好可惜未中奖';
            return $msg;
        }
        //去o2o领券
        $o2oService = new O2OService();
        $ret = array();
        $resO2o = '';
        //领投资券
        if (!empty($prizeDiscounts)) {
            foreach ($prizeDiscounts as $value) {
                $resTmp = $o2oService->getDiscountGroup($value);
                if (!$resTmp) {
                    $resTmp = $o2oService->getErrorMsg();
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:'.$userInfo['id'].',领奖失败：'.$resTmp,
                            'activity_id:'.$userActivityInfo['0']['activity_id'],'Discounts:'.$value)));
                } else {
                    $ret['award'][] = $resTmp['name'];
                }
            }

        }
        //领礼券
        if (!empty($prizeCoupons)) {
            foreach ($prizeCoupons as $value) {
                $resTmp = $o2oService->getCouponGroupInfoById($value);
                if (!$resTmp) {
                    $resTmp = $o2oService->getErrorMsg();
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:'.$userInfo['id'].',领奖失败：'.$resTmp,
                             'activity_id:'.$userActivityInfo['0']['activity_id'],'Coupons:'.$value)));
                } else {
                    $ret['award'][] = $resTmp['productName'];
                }
            }
        }
        if ($candyAmount > 0) {
            $ret['award'][] = $candyAmount.'信力';
        }

        if (empty($ret['award'])) {
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|用户请求的券组ido2o中不存在'.'|activity_id:|'.$userActivityInfo['0']['activity_id'];
            $msg['is_have_award'] = false;
            $msg['msg'] = '好可惜未中奖';
            return $msg;
        }

        $GLOBALS['db']->startTrans();
        try {
            //更新活动库存信息
            $data['id'] = $activityInfo['id'];
            $data['prize_conf'] = json_encode($prizeConf);
            $updateActivity = $this->updateActivityInfo($data);
            //将用户ticket删除
            $coupons = isset($prizeCoupons) ? implode(',', $prizeCoupons) : '';
            $discount = isset($prizeDiscounts) ? implode(',', $prizeDiscounts) : '';
            $data['award'] = $userActivityInfo['0']['award'].'|activi_id:|'.$activityInfo['id'].'|score:|'.$score.'|award|'.implode(',',$ret['award']).'|discount|'.$discount.'|coupons:|'.$coupons.'|';
            $data = array(
                    'id' => $userActivityInfo['0']['id'],
                    'award' => $data['award'],
                    'ticket' => '',
            );
            $updateUserInfo = $UserFestivalActivitiesService->updateActivityInfoById($data);
            $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey);//解锁
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey);//解锁
            //\libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid'.$userInfo['id'],"更新用户信息和清除ticket失败", $e->getMessage())));
            $msg['log_msg'] = '用户：|'.$userInfo['id'].'|更新用户信息和清除ticket失败|'.$e->getMessage();
            $msg['is_have_award'] = false;
            $msg['msg'] = '领奖失败，请重试';
            return $msg;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis  === NULL) {
            $alarm_data = __CLASS__.'|'.__FUNCTION__.':|'.'|uid|'.$userInfo['id'].'|activity_id:|'.$activityInfo['id'];
            $alarm_title = '领奖redis异常不能更新redis中缓存的用户信息';
            Alarm::push('p2p_festival_activities',$alarm_title,$alarm_data);
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'getAward_redis_is_null:|'.'|uid|'.$userInfo['id'].'|activity_id:|'.$activityInfo['id'],"redis异常不能更新redis中缓存的用户信息")));
        }
        $redis_key = 'FestivalActivitiesService_'.$userInfo['id'].'_'.$activityInfo['id'].'_'.$userActivityInfo['0']['activity_count'];
        $redis->hset($redis_key,'award',$data['award']);
        //print_r($redis->hget($redis_key,'award'));

        //请求o2o领券接口
        if (!empty($prizeCoupons)) {
            $prizeCoupons = implode ( "," , $prizeCoupons);
            $resO2oCoupons = $o2oService->acquireCoupons($userInfo['id'],$prizeCoupons,$userActivityInfo['0']['ticket']);
        }

        if (!empty($prizeDiscounts)) {
            $prizeDiscounts = implode ( "," , $prizeDiscounts);
            $resO2oDiscounts = $o2oService->acquireDiscounts($userInfo['id'],$prizeDiscounts,$userActivityInfo['0']['ticket']);
        }
        //请求信宝发信力
        if ($candyAmount > 0) {
            $candyAccountService = new CandyActivityService();
            $candyToken = 'game_'.$userActivityInfo['0']['ticket'];
            $candyAccountService->activityCreateByToken($candyToken, $userInfo['id'], $candyAmount, CandyActivityService::SOURCE_TYPE_GAME);
        }

        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:|'.$userInfo['id'].'|score:|'.$score.',领奖成功:'.implode(',',$ret['award']),
                 'activity_id:'.$userActivityInfo['0']['activity_id'],'Coupons:'.$prizeCoupons,'o2o_res:'.$resO2oCoupons.','.$resO2oDiscounts,'Discounts:'.$prizeDiscounts.' candyAmount:'.$candyAmount)));
        $ret['log_msg'] = '用户：|'.$userInfo['id'].'|领奖成功';
        $ret['is_have_award'] = true;
        $ret['msg'] = '奖品将在一小时内发放到您的账户';
        return $ret;
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
    //春节任务找福袋
    /**
     * 获取找福袋活动的信息
     * @param $userId
     * @return 返回活动信息
     */
    private static $_activity_time = 86400;//活动一天都有效
    public function luckyBagInfo($userId) {
        $ret = array(
                'have_activity' => false,
                'have_next' => false,
                //'next_time' => false,
                'next_msg' => '',
                'over_count' => false,
                'ticket' => '',
                'have_stock' => false,
                'bonus_counts' => 0,
                'have_lucky_bag' => false,
        );
        if (!$userId) {
            $ret['log_msg'] = 'userId为空'.$userId;
            return $ret;
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            $alarm_data = __CLASS__.'|'.__FUNCTION__.':|'.'|uid|'.$userId;
            $alarm_title = '找红包,获取红包信息接口redis异常';
            Alarm::push('p2p_festival_activities',$alarm_title,$alarm_data);
            $ret['log_msg'] = 'redis实例化失败';
            return $ret;
        }
        $cacheInfo = $redis->get('AdminFindLuckyBagInfoCache');
        if ($cacheInfo && $cacheInfo != 'no_activity') {
            $luckyBagInfo = json_decode($cacheInfo,true);
        } else {
            $service = new ApiConfService();
            $info = $service->getApiAdvConf('',1,5);
            $luckyBagInfo = empty($info['0']['value']) ? null : json_decode($info['0']['value'],true);
            $cacheRet = !empty($luckyBagInfo) ? $redis->setex('AdminFindLuckyBagInfoCache',432000,$luckyBagInfo) : $redis->setex('AdminFindLuckyBagInfoCache',432000,'no_activity');
        }
        if (!$luckyBagInfo || $cacheInfo == 'no_activity') {
            $ret['log_msg'] = '活动信息为空'.json_encode($info).json_encode($cacheInfo);
            return $ret;
        }
        $startTime = strtotime($luckyBagInfo['start_time']);
        $endTime = strtotime($luckyBagInfo['end_time']);
        //如果活动的时间不在开启时间内，则直接返回false
        if (!$this->judgeLuckybagTime($luckyBagInfo)) {
            $ret['log_msg'] = '活动时间不在开启时间段内'.'uid:'.$userId;
            return $ret;
        }
        //判断是否还有下一轮活动（第二天是否还有活动）
        $ret['have_activity'] = true;
        $baseTime = mktime(0,0,0,date('m'),date('d'),date('Y'));//当天0点时间戳
        if ($endTime - $baseTime >= 86400) {
            $ret['have_next'] = true;
            //$ret['next_time'] = '15:00';
            $ret['next_msg'] = '每天只能找一次，明天继续加油！';
        }
        //判断是否超过游戏次数，并获取需要显示福袋的页面
        $key_name = 'FestivalActivityGetLuckyBag';
        $ret['have_stock'] = $luckyBagInfo['coupons_count'] - $redis->get($key_name.$baseTime) > 0 ? true : false;
        $ret['bonus_counts'] = 5;
        $ret['over_count'] = $redis->get($key_name.$userId.'get_count'.$baseTime) ? true : false;
        //如果用户已经超过了次数限制,则直接返回结果，page_num和ticket返回空
        if ($ret['over_count']) {
            return $ret;
        }
        $pageNum = $redis->get($key_name.$userId.'page_num');
        if (empty($pageNum)) {
            //产生福袋显示的页面
            $ret['page_num'] = rand(1, 6);
            $ret['ticket'] = md5($key_name.$userId.time().rand(1,100));
            $redis->setex($key_name.$userId.'page_num',self::$_activity_time,$ret['page_num']);
            $redis->setex($key_name.$userId.'ticket',self::$_activity_time,$ret['ticket']);
        } else {
            $ret['page_num'] = $pageNum;
            $ret['ticket'] = $redis->get($key_name.$userId.'ticket');
        }
        return $ret;
    }
    /**
     * 找福袋领奖
     * @param $userId: 用户信息, $ticket: 用户领奖凭证
     * @return 返回领奖结果
     */
    public function getLuckyBag($userId,$ticket) {
        $baseStartTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $ret = array(
                'log_msg' => '',
                'type' => '',
                'price' => '',
                'tip' => '',
                'limit' => '',
                'is_have_award' => false,
                'msg' => '很遗憾红包被抢没了',
        );
        if (!$userId || !$ticket) {
            $ret['log_msg'] = 'uid:'.$userId.'或者ticket为空'.$ticket;
            return $ret;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key_name = 'FestivalActivityGetLuckyBag';
        if (!$redis) {
            $alarm_data = __CLASS__.'|'.__FUNCTION__.':|'.'|uid|'.$userId;
            $alarm_title = '找红包,领奖接口个redis异常';
            Alarm::push('p2p_festival_activities',$alarm_title,$alarm_data);
            $ret['log_msg'] = 'redis实例化失败';
            return $ret;
        }

        $ticketCache = $redis->get($key_name.$userId.'ticket');
        if ($ticketCache != $ticket) {
            $ret['log_msg'] = 'uid:'.$userId.'ticket不正确,'.'缓存：'.$ticketCache.'|传参：'.$ticket;
            $ret['msg'] = '请求参数不正确';
            return $ret;
        }
        $cacheInfo = $redis->get('AdminFindLuckyBagInfoCache');
        if ($cacheInfo) {
            $luckyBagInfo = json_decode($cacheInfo,true);
        } else {
            $service = new ApiConfService();
            $info = $service->getApiAdvConf('',1,5);
            $luckyBagInfo = json_decode($info['0']['value'],true);
            $redis->setex('AdminFindLuckyBagInfoCache',432000,$data['value']);
        }
        if (!$luckyBagInfo) {
            $ret['log_msg'] = '获取活动信息为空';
            return $ret;
        }

        if (!$this->judgeLuckybagTime($luckyBagInfo)) {
            $ret['log_msg'] = '活动配置时间不在活动开启时间段内';
            return $ret;
        }

        $stock = $redis->get($key_name.$baseStartTime);
        if ($stock > $luckyBagInfo['coupons_count']) {
            //用户已经领福袋的次数
            $redis->setex($key_name.$userId.'get_count'.$baseStartTime,self::$_activity_time,1);
            $redis->del($key_name.$userId.'ticket');
            $redis->del($key_name.$userId.'page_num');
            $ret['log_msg'] = 'uid|'.$userId.'|库存没有了,已用|'.$stock.'|库存|'.$luckyBagInfo['coupons_count'];
            return $ret;
        }
        $prizeDiscounts = array();//投资券
        $prizeCoupons = array();//礼券
        //去o2o领券
        //券组类型0:礼券 1：投资券
        $o2oService = new O2OService();
        $resO2o = '';
        //领投资券
        if ($luckyBagInfo['coupons_type'] == 1) {
            $resTmp = $o2oService->getDiscountGroup($luckyBagInfo['coupons_id']);
            if (!$resTmp) {
                $resTmp = $o2oService->getErrorMsg();
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:'.$userId.',领取福袋失败：'.$resTmp,$luckyBagInfo['coupons_id'])));
            } else {
                $resO2oDiscounts = $o2oService->acquireDiscounts($userId,$luckyBagInfo['coupons_id'],$ticket);
                //用户已经领福袋的次数
                $redis->setex($key_name.$userId.'get_count'.$baseStartTime,self::$_activity_time,1);
                //用户领奖次数自增，有效期设置为活动持续时间1小时
                $count = $redis->incr($key_name.$baseStartTime);
                if ($count == 1) {
                    $redis->expire($key_name.$baseStartTime,self::$_activity_time);
                }
                if($count > $luckyBagInfo['coupons_count']) {
                    $ret['log_msg'] = '库存没有了,已用|'.$count.'|库存|'.$luckyBagInfo['coupons_count'];
                    return $ret;
                }
                //对获得的奖品进行输出处理
                $ret['price'] = number_format($resTmp['goodsPrice']).'元';
                $ret['tip'] = '投资满'.number_format($resTmp['bidAmount']).'元'.'期限满'.$resTmp['bidDayLimit'].'天可用';
                $tmp = '';
                if ($resTmp['useTimeType'] == 1) {
                    $tmp = $resTmp['useDayLimit']/86400;
                } else {
                    $tmp = ($resTmp['useEndTime'] - $resTmp['useStartTime'])/86400;
                    $tmp = $tmp > 1 ? $tmp : 1;
                }
                $ret['limit'] = $tmp.'天有效期';
                $ret['type'] = '投资券';
            }
        }
        //领礼券,：：：：：注意：：：：：这一期产品说了不会配置礼券,暂时对礼券的返回结果没有处理
        if ($luckyBagInfo['coupons_type'] == 0) {
            $resTmp = $o2oService->getCouponGroupInfoById($luckyBagInfo['coupons_id']);
            if (!$resTmp) {
                $resTmp = $o2oService->getErrorMsg();
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:|'.$userId.',领取福袋失败：'.$resTmp,$luckyBagInfo['coupons_id'])));
            } else {
                $resO2oCoupons = $o2oService->acquireCoupons($userId,$luckyBagInfo['coupons_id'],$ticket);
                //用户已经领福袋的次数
                $redis->setex($key_name.$userId.'get_count'.$baseStartTime,self::$_activity_time,1);
                $count = $redis->incr($key_name.$baseStartTime);
                if ($count == 1) {
                    $redis->expire($key_name.$baseStartTime,self::$_activity_time);
                }
                if($count > $luckyBagInfo['coupons_count']) {
                    $ret['log_msg'] = '库存没有了,已用|'.$count.'|库存|'.$luckyBagInfo['coupons_count'];
                    return $ret;
                }
            }
        }
        if (empty($ret['price'])) {
            $ret['is_have_award'] = false;
            $ret['msg'] = '领奖失败，请重试';
            \libs\utils\Monitor::add('GET_LUCKY_BAG_FAIL');
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:|'.$userId.'|领奖失败:',$ret['price'],$ret['tip'],
                                     $ret['limit'],$ret['type'],'所有用户已领奖品数|'.$count.'|库存|'.$luckyBagInfo['coupons_count'])));
            return $ret;
        }
        \libs\utils\Monitor::add('GET_LUCKY_BAG_SUCCESS');
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:|'.$userId.'|领奖成功:',$ret['price'],$ret['tip'],
                                 $ret['limit'],$ret['type'],'所有用户已领奖品数|'.$count.'|库存|'.$luckyBagInfo['coupons_count'])));
        $redis->del($key_name.$userId.'ticket');
        $redis->del($key_name.$userId.'page_num');
        $ret['is_have_award'] = true;
        $ret['msg'] = '奖品将在一小时内发放到您的账户';
        return $ret;
    }
    private function judgeLuckybagTime($luckyBagInfo = array()) {
        $baseStartTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $baseEndTime = mktime(23,59,59,date('m'),date('d'),date('Y'));
        if (empty($luckyBagInfo)) {
            return false;
        }

        $startTime = strtotime($luckyBagInfo['start_time']);
        $endTime = strtotime($luckyBagInfo['end_time']);
        //如果活动的时间在开启时间内，则返回true
        if ($startTime <= $baseEndTime && $endTime >= $baseStartTime && $endTime > $startTime) {
            return true;
        }
        return false;
    }
}
