<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/11/16
 * Time: 13:32
 */

namespace core\dao;


class DealRepayOplogModel extends BaseModel {

    const REPAY_TYPE_NORMAL = 1; // 正常还款
    const REPAY_TYPE_PRE = 2;   //提前还款
    const REPAY_TYPE_PRE_SELF = 3;  // 借款人前台自助发起
    const REPAY_TYPE_DAIFA = 4 ;//代发还款
    const REPAY_TYPE_PART = 5 ;//部分还款

    /**
    * 获取标的所有有还款的日期
    */
    public function getRepayDays($offset=0,$limit=15,$is_firstp2p=false){
        // 看昨天和之前的，所以最大时间为今天 减少 1秒
        $maxTime = strtotime(date("Y-m-d"))-1-28800;
        $firstp2pSpecialCond = "";
        if($is_firstp2p) {
            $firstp2pSpecialCond = " AND report_status=1 ";
        }
        $sql = "SELECT FROM_UNIXTIME(real_repay_time+28800,'%Y-%m-%d') AS ymd FROM firstp2p_deal_repay_oplog WHERE real_repay_time<={$maxTime} {$firstp2pSpecialCond} GROUP BY ymd ORDER BY ymd DESC LIMIT {$offset},{$limit}";
        $rs =  $this->findAllBySqlViaSlave($sql,true);
        if (is_array($rs) && count($rs) > 0) {
            return $rs;
        } else {
            return array();
        }
    }


    /**
     * 首页获取标的所有有还款的日期
     */
    public function getRepayDaysSite($is_firstp2p=false){
        $maxTime = strtotime(date("Y-m-d"))-1-28800;
        $minTime = strtotime(date("Y-m-d"))-1-115200;
        $j = 0;
        $date = array();
        $limit = 11;

        $firstp2pSpecialCond = "";
        if($is_firstp2p) {
            $firstp2pSpecialCond = " AND report_status=1 ";
        }

        for($i = 0;$j < $limit; $i++){
            $sql = "SELECT count(id) as c FROM firstp2p_deal_repay_oplog WHERE real_repay_time<={$maxTime} {$firstp2pSpecialCond} and real_repay_time>{$minTime}";
            $rs =  $this->findBySqlViaSlave($sql);
            if($rs['c'] > 0){
                $j++;
                $date[]['ymd'] = date('Y-m-d',$maxTime);
            }
            $maxTime = $maxTime - 86400;
            $minTime = $minTime - 86400;
        }

        return $date;
    }



    /**
    * 获取标的所有有还款的日期
    */
    public function getRepayDaysCount($is_firstp2p=false){
        // 看昨天和之前的，所以最大时间为今天 减少 1秒
        $maxTime = strtotime(date("Y-m-d"))-1-28800;

        $firstp2pSpecialCond = "";
        if($is_firstp2p) {
            $firstp2pSpecialCond = " AND report_status=1 ";
        }

        $sql = "SELECT count(distinct(FROM_UNIXTIME(real_repay_time+28800,'%Y-%m-%d'))) AS count FROM firstp2p_deal_repay_oplog WHERE real_repay_time<={$maxTime} {$firstp2pSpecialCond}";
        $rs =  $this->findBySqlViaSlave($sql);
        if (count($rs['count']) > 0) {
            return $rs['count'];
        } else {
            return 0;
        }
    }

    /**
    * 获取标的所有有还款的日期
    */
    public function getRepayDaysDetail($days,$is_firstp2p=false){

        $firstp2pSpecialCond = "";
        if($is_firstp2p) {
            $firstp2pSpecialCond = " AND dro.report_status=1 ";
        }

        $sql = 'select count(*) AS count,dro.operation_type,FROM_UNIXTIME(dro.real_repay_time+28800,"%Y-%m-%d") as ymd
                FROM firstp2p_deal_repay_oplog AS dro
                WHERE  dro.audit_type in (0,3) AND dro.operation_status=1 AND FROM_UNIXTIME(dro.real_repay_time+28800,"%Y-%m-%d") IN ('.$days.') '.$firstp2pSpecialCond.'
                GROUP BY ymd,dro.operation_type ORDER BY ymd DESC';
        $rs =  $this->findAllBySqlViaSlave($sql,true);
        if (is_array($rs) && count($rs) > 0) {
            return $rs;
        } else {
            return array();
        }
    }


    /**
    * 获取某日期的还款详情
    */
    public function getRepayDetailCountByDay($day){
        // 这种写法不能使用索引，造成线上慢sql
/*        $sql = "SELECT count(*) as count FROM firstp2p_deal_repay_oplog AS dro
                WHERE  dro.audit_type IN (0,3) AND dro.operation_status=1 AND
                FROM_UNIXTIME(dro.real_repay_time+28800,'%Y-%m-%d')='{$day}'";*/

        $dayBeginTime = to_timespan($day);
        $dayEndTime = $dayBeginTime + 86399;
        $sql = "SELECT count(*) as count FROM firstp2p_deal_repay_oplog AS dro
                WHERE  dro.audit_type IN (0,3) AND dro.operation_status=1 AND dro.real_repay_time BETWEEN $dayBeginTime AND $dayEndTime";

        $rs =  $this->findBySqlViaSlave($sql);
        if (count($rs['count']) > 0) {
            return $rs['count'];
        } else {
            return 0;
        }
    }


    /**
    * 获取某日期的还款详情
    */
    public function getRepayDetailByDay($day,$offset,$limit){
        $dayBeginTime = to_timespan($day);
        $dayEndTime = $dayBeginTime + 86399;
            // 这种写法不能使用索引废弃掉
//        $sql = "SELECT deal_id,deal_name,operation_type FROM firstp2p_deal_repay_oplog AS dro
//                WHERE  dro.audit_type IN (0,3) AND dro.operation_status=1 AND
//                FROM_UNIXTIME(dro.real_repay_time+28800,'%Y-%m-%d')='{$day}' LIMIT {$offset},{$limit}";
        $sql = "SELECT deal_id,deal_name,operation_type FROM firstp2p_deal_repay_oplog AS dro
                WHERE  dro.audit_type IN (0,3) AND dro.operation_status=1 AND dro.real_repay_time BETWEEN $dayBeginTime AND $dayEndTime    LIMIT {$offset},{$limit}";

        $rs =  $this->findAllBySqlViaSlave($sql,true);
        if (is_array($rs) && count($rs) > 0) {
            return $rs;
        } else {
            return array();
        }
    }

}
