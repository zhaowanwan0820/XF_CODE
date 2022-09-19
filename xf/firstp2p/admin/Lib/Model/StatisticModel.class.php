<?php

class StatisticModel extends CommonModel {

    /**
     * 获取统计数据
     * @param type $startTime
     * @param type $endTime
     * @param type $siteid
     * @return type
     */
    public function getRegStatisticList($startTime, $endTime, $siteid = 1) {
        if (intval($startTime) > 0 && intval($endTime) > 0) {
            //注册用户
            if(!empty($siteid))
            {
                $site_condition = "b.`group_id` ='{$siteid}' and ";
                $condition = "`group_id` ='{$siteid}' and `create_time` >= {$startTime} AND `create_time` < {$endTime} AND `is_effect` = 1 AND `is_delete` = 0";
            }
            else 
            {
                $site_condition = "";
                $condition = "`create_time` >= {$startTime} AND `create_time` <= {$endTime} AND `is_effect` = 1 AND `is_delete` = 0";
            }
            $reg_count = MI('User')->where($condition)->count();
            
            //认证通过的人数
            $sql = "select count(distinct b.id) as audit_count  from `" . DB_PREFIX . "user` b where {$site_condition} b.`idcardpassed` = 1 AND b.`create_time` >= {$startTime} AND b.`create_time`< {$endTime} AND b.`idcardpassed_time` >= {$startTime} AND b.`idcardpassed_time` <= {$endTime} AND b.`is_effect` = 1 AND b.`is_delete` = 0";
            
            $audit_count = $this->query($sql);
            $audit_count = $audit_count[0]['audit_count'];

            //投资人数
            $sql = "select count(distinct a.user_id) as load_count  from `" . DB_PREFIX . "deal_load` a join `" . DB_PREFIX . "user` b on a.user_id=b.id where {$site_condition} b.`create_time` >= {$startTime} AND b.`create_time` < {$endTime} AND b.`is_effect` = 1 AND b.`is_delete` = 0";
            $user_load_count = $this->query($sql);
            $user_load_count = $user_load_count[0]['load_count'];
            
            //投资次数
            $sql = "select count(a.id) as load_count  from `" . DB_PREFIX . "deal_load` a join `" . DB_PREFIX . "user` b on a.user_id=b.id where {$site_condition} b.`create_time` >= {$startTime} AND b.`create_time` < {$endTime} AND b.`is_effect` = 1 AND b.`is_delete` = 0";
            $load_count = $this->query($sql);
            $load_count = $load_count[0]['load_count'];

            $result = array(
                'reg' => $reg_count,
                'audit' => $audit_count,
                'user_load_count' => $user_load_count,
                'load_count' => $load_count,
                'user_rate' => (empty($user_load_count)) ? 0 : number_format($load_count / $user_load_count, 2),//人均投资次数
                'load_rate' => (empty($reg_count)) ? 0 : number_format($user_load_count / $reg_count, 4),//投资转化率
            );
            return $result;
        }
        exit();
    }

    /**
     * 数组排序
     * @param type $arr
     * @param type $sortKey
     * @return type
     */
    public function arraySort($arr,$sortKey,$sortType) {
        $len = count($arr);
        foreach ($arr as $i => $v1) {            
            $flag = false;
            foreach ($arr as $j => $v2) {
                if($sortType === 1)
                {
                    if ($arr[$j][$sortKey] < $arr[$i][$sortKey]) {
                        $x = $arr[$j];
                        $arr[$j] = $arr[$i];
                        $arr[$i] = $x;
                        $flag = true; //发生了交换，故将交换标志置为真
                    }                    
                }
                else
                {                    
                    if ($arr[$j][$sortKey] > $arr[$i][$sortKey]) {
                        $x = $arr[$j];
                        $arr[$j] = $arr[$i];
                        $arr[$i] = $x;
                        $flag = true; //发生了交换，故将交换标志置为真
                    }
                }
            }
        }
        return $arr;
    }

}
