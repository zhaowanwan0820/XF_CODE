<?php
/**
 * CouponLevelService.php
 *
 * @date 2014-05-30
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\CouponLevelModel;
use core\dao\CouponLogModel;
use core\dao\UserModel;


\FP::import("libs.utils.logger");

class CouponLevelService {

    /**
     * 缓存时间 秒
     * @var int
     */
    private static $_cache_time = 180;
    protected $levels;
    protected $cache = true;

    public function __construct($cache = true) {
        $this->cache = $cache;
        if($cache == true){ //取缓存
            $this->levels = \SiteApp::init()->dataCache->call(CouponLevelModel::instance(),'getLevels',array(true),self::$_cache_time);
        }
        else{
            $this->levels = CouponLevelModel::instance()->getLevels(true);
        }
    }

    public function getAllLevels(){
        return $this->levels;
    }

    /**
     * 根据会员ID获取会员等级
     *
     * @param $user_id
     * @return bool
     */
    public function getUserLevel($user_id) {
        $user_model = new UserModel();
        $user = $user_model->find($user_id, 'coupon_level_id',$this->cache);
        $level_id = $user['coupon_level_id'];
        if (empty($level_id) || empty($this->levels[$level_id])) {
            return false;
        }
        return $this->levels[$level_id];
    }

    /**
     * 更新会员等级
     *
     * @param bool $user_id 会员ID，为空则全量更新
     * @return array
     */
    public function updateUserLevel($user_id) {
        \logger::info(self::log_info(__FUNCTION__, array('user_id' => $user_id , 'start'=>date('Y-m-d H:i:s'))));
        if(!$user_id){
            return false;
        }
        //会员列表
        $coupon_log_model = new CouponLogModel();
        $user_list = $coupon_log_model->getUserLevelListForUpdate($user_id);
        if(empty($user_list)){
           return false;
        }

        //统计累计金额的天数
        $stat_days = intval(app_conf('COUPON_LEVEL_STAT_DAYS'));
        //投资用户的累计投资金额结果
        $deal_load_list = $coupon_log_model->getLevelUpdateStat($stat_days, $user_id);

        //会员组及下属会员等级
        $group_levels = array();
        foreach ($this->levels as $item) {
            $group_levels[$item['group_id']][$item['id']] = $item;
        }

        $result_update = array(); //更新的记录，包括有效期更新
        $result_keep = array(); //保持不变的记录
        $result_error = array(); //失败的记录

        foreach ($user_list as $user) {
            $arr = array('user_id' => $user_id,'user_info' => $user->getRow());
            \logger::info(self::log_info(__FUNCTION__, $arr));
            $levels = $group_levels[$user['group_id']];
            $log_info = $user['user_name'];
            if (empty($levels)) {
                //所属会员组没有会员等级
                $result_error_group[] = $log_info;
                continue;
            }

            $user_money = isset($deal_load_list[$user['id']]) ? $deal_load_list[$user['id']] : 0;
            $level_id_ok = 0;
            $level_min = array_shift($levels);
            while ($level = array_pop($levels)) {
                $money_ok = $user_money >= $level['money']; //金额是否达到要求
                if ($money_ok) {
                    $user['coupon_level_valid_end'] = get_gmtime() + 86400 * $level['valid_days'];
                    $user['coupon_level_id'] = $level['id'];
                    $level_id_ok = $level['id'];
                    $user->save();
                    $result_update[] = $log_info;
                    break;
                } else if ($user['coupon_level_id'] == $level['id'] && $user['coupon_level_valid_end'] >= get_gmtime()) {
                    $level_id_ok = $level['id'];
                    break;
                } else {
                    //降级
                }
            }

            if (empty($user['coupon_level_id']) || ($user['coupon_level_id'] != $level_id_ok && $user['coupon_level_id'] != $level_min['id'])) {
                $user['coupon_level_valid_end'] = get_gmtime() + 86400 * $level_min['valid_days'];
                $user['coupon_level_id'] = $level_min['id'];
                $user->save();
                $result_update[] = $log_info;
            } else if (!$money_ok) {
                $result_keep[] = $log_info;
            }

        }

        $result = array('update' => $result_update, 'keep' => $result_keep, 'user_id' => $user_id, 'error' => $result_error);

        \logger::info(self::log_info(__FUNCTION__, $result));

        return $result;

    }

    /**
     * 记录日志
     *
     * @param $function_name
     * @param $paramArray
     * @param $result
     * @param string $error
     * @return string
     */
    public static function log_info($function_name, $result) {
        $arr_log[] = get_client_ip();
        $arr_log[] = __CLASS__;
        $arr_log[] = $function_name;
        $arr_log[] = json_encode($result);
        $str_log = implode(" | ", $arr_log);
        return $str_log;

    }


}
