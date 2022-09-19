<?php
/**
 * 2018用户轨迹服务
 * @author majunliang <majunliang@ucfgroup.com>
 * @date 2018-12-12 10:35:48
 */
namespace core\service;

use libs\utils\Logger;
use libs\db\MysqlDb;

class UserGrowth2018Service extends BaseService
{
    public function getUserGrowth($user_id = null)
    {
        if (!$user_id) {
            return false;
        }
        $growth_data = array();
        try {
            $this->db = MysqlDb::getInstance('marketing');
            $sql = "SELECT * FROM ".firstp2p_user_growth_2018." WHERE user_id = '".$user_id."'";
            $growth_data = $this->db->getRow($sql);
            Logger::info('MysqlData: '.implode('|', $growth_data));
          } catch (\Exception $e) {
              Logger::info('MysqlException:'.$e->getMessage());
          }
 
        if (empty($growth_data)){
            return array();
        }
        $growth_data['level'] = $this->getUserLevel($growth_data);
        $growth_data['first_bid_money'] = $this->numFormat($growth_data['first_bid_money']);
        $growth_data['first_bid_time'] = $this->dateFormat($growth_data['first_bid_time']);
        $growth_data['max_bid_money'] = $this->numFormat($growth_data['max_bid_money']);
        $growth_data['max_bid_time'] = $this->dateFormat($growth_data['max_bid_time']);
        $growth_data['max_income_money'] = $this->numFormat($growth_data['max_income_money']);
        $growth_data['max_income_time'] = $this->dateFormat($growth_data['max_income_time']);
        $growth_data['total_bid_money'] = $this->numFormat($growth_data['total_bid_money']);
        $growth_data['annualized_bid_money'] = $this->numFormat($growth_data['annualized_bid_money']);
        $growth_data['total_income'] = $this->numFormat($growth_data['total_income']);
        $growth_data['candy'] = $this->numFormat($growth_data['candy']);
        return $growth_data;
    }
    
    public function getUserLevel($growth_data)
    {
        if ($growth_data["total_bid_money"] >= 2000000) {
            $level = 1;
        } elseif ($growth_data["total_bid_money"] >= 500000) {
            $level = 2;
        } elseif ($growth_data["candy"] >= 300) {
            $level = 3;
        } elseif ($growth_data["total_bid_money"] >= 100000) {
            $level = 4;
        } elseif ($growth_data["times_bid"] >= 25) {
            $level = 5;
        } elseif ($growth_data["times_bid"] <= 5) {
            $level = 6;
        } else {
            $level = 7;
        }
        return $level;
    }
    
    public function dateFormat($time)
    {
        if (!$time) {
            return $time;
        }
        return date("m月d日", $time);
    }
    
    public function numFormat($num)
    {
        if (!$num) {
            return 0;
        }
        //先格式化
        $num = number_format($num, 3, '.', ",");
        //去掉0
        $num = rtrim($num, "0");
        //去掉.
        $num = rtrim($num, ".");
        return $num;
    }
}
