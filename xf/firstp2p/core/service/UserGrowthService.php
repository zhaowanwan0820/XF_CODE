<?php
/**
 * 用户轨迹服务
 * @author longbo <longbo@ucfgroup.com>
 * @date 2015-12-16 20:35:48
 */
namespace core\service;

use core\dao\UserGrowthModel;
use libs\utils\Logger;

class UserGrowthService extends BaseService
{
    const REDIS_GROWTH_KEY = 'user_grow_path';

    public $data_arr = array(
            'invest_money_position',
            'invest_percentage',
            'constellation',
            'constellation_percent',
            'real_name',
            'reg_time',
            'num',
            'fbid_time',
            'fbid_money',
            'freturn_time',
            'all_invest_money',
            'all_return_money',
            'frefer_time',
            'refer_num',
            'bonus_used_money',
            'bonus_send_count',
            'fgybid_time',
            'gybid_num',
            'bonus_ability',
            );
    public function getUserGrowth($user_id = null)
    {
        if (!$user_id) {
            return false;
        }
        $rediscache = \SiteApp::init()->cache;
        $user_growth = $rediscache->hget(self::REDIS_GROWTH_KEY, strval($user_id));
        $growth_data = array();
        if (!empty($user_growth)) {
            $growth_arr = explode(',', $user_growth);
            foreach($growth_arr as $key => $value){
                $growth_data[$this->data_arr[$key]] = $value;
            }
            Logger::info('RedisData: '.implode('|', $growth_data));
        } else {
            try {
                $growth_data = UserGrowthModel::instance()->getUserGrowth($user_id);
                Logger::info('MysqlData: '.implode('|', $growth_data));
            } catch (\Exception $e) {
                Logger::info('MysqlException:'.$e->getMessage());
            }
        }
        if (empty($growth_data)) {
            return array();
        }
        $growth_data['invest_percentage'] = self::_num_format($growth_data['invest_percentage']);
        $growth_data['constellation_percent'] = self::_num_format($growth_data['constellation_percent']);
        $growth_data['bonus_ability'] = self::_num_format($growth_data['bonus_ability']);
        return $growth_data;
    }


    static $threeYearsKey = array (
        'invest' => 'three_years_invest_data',
        'bonus' => 'three_years_bonus_data',
        'medal' => 'three_years_medal_data',
    );
    public function getUserThreeYearsData($user_id = null)
    {
        if (!$user_id) {
            return false;
        }
        $rediscache = \SiteApp::init()->cache;
        $user_invest = $rediscache->hget(self::$threeYearsKey['invest'], strval($user_id));
        $user_bonus = $rediscache->hget(self::$threeYearsKey['bonus'], strval($user_id));
        $user_medal = $rediscache->hget(self::$threeYearsKey['medal'], strval($user_id));
        $growth_data = array();

        $user_invest_arr = explode(',', $user_invest);
        $growth_data['invest_amount'] = ($user_invest_arr[0]) ? $user_invest_arr[0] : '0';
        $growth_data['invest_rank'] = isset($user_invest_arr[1]) ? $user_invest_arr[1] : '0';

        $growth_data['bonus_get'] = $user_bonus ? $user_bonus : '0';

        $user_medal_arr = explode(',', $user_medal);
        $growth_data['medal_count'] = ($user_medal_arr[0]) ?  $user_medal_arr[0] : '0';
        $growth_data['medal_rank'] = isset($user_medal_arr[1]) ?  $user_medal_arr[1] : '0';
        $growth_data['medal_perc'] = isset($user_medal_arr[2]) ?  self::_num_format($user_medal_arr[2]) : '0.0';
        return $growth_data;
    }

    public static function _num_format($num) {
        $res_data = round(100 * floatval($num), 1);
        return floatval($res_data) >= 100 ? '99.9' : strval($res_data);
    }
}
