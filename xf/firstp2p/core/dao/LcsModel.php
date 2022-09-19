<?php
/**
 * @author <wangfei5@ucfgroup.com>
 **/

namespace core\dao;

/**
 **/
class LcsModel extends BaseModel {

    const MAX_YEARS = 4;

    public function getCustomersRepaysDetail($referUserId,$year,$month,$day){
        $sql = sprintf("SELECT dlrc.*
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                        firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s'
                        WHERE dlrc.repay_month='%s' AND dlrc.repay_day='%s' ORDER BY dlrc.repay_month DESC, dlrc.repay_day DESC
                        ",intval($year),intval($referUserId),intval($month),intval($day));
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        $data = empty($ret)?array():$ret;
        return $data;
    }


    public function getCustomersRepaysMap($referUserId,$year,$month){
        $sql = sprintf("SELECT dlrc.repay_month,dlrc.repay_day,
                            sum(dlrc.repay_interest+dlrc.repay_principal+dlrc.prepay_principal+dlrc.prepay_interest) AS repay,
                            sum(dlrc.norepay_interest+dlrc.norepay_principal) AS no_repay
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                        firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s'
                        WHERE dlrc.repay_month='%s' GROUP BY dlrc.repay_day
                        ",intval($year),intval($referUserId),intval($month));
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        $data = empty($ret)?array():$ret;
        return $data;
    }

    public function getCustomersMapRepaysList($referUserId,$year,$month,$offset,$count,$repayType){

        if($repayType == 'alRepay'){
            // 已回款,查已正常还款和已提前还款的本金和利息
            $rows = " dlrc.repay_principal+dlrc.prepay_principal AS principalRepayMoney, dlrc.repay_interest+dlrc.prepay_interest AS profitRepayMoney ";
            $condition = " ( dlrc.repay_principal!=0 OR dlrc.prepay_principal!=0 OR dlrc.repay_interest!=0 OR dlrc.prepay_interest!=0 ) ";
        }else{
            // 即将回款，查待还本金和待还利息
            $rows = " dlrc.norepay_principal AS principalRepayMoney, dlrc.norepay_interest AS profitRepayMoney ";
            $condition = " (dlrc.norepay_principal!=0 OR dlrc.norepay_interest!=0) ";
        }
        $sql = sprintf("SELECT dlrc.repay_month,dlrc.repay_day,dlrc.user_id,%s
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                        firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s'
                        WHERE dlrc.repay_month='%s' AND %s
                        ORDER BY dlrc.repay_month ASC, dlrc.repay_day ASC LIMIT %s,%s
                        ",$rows,intval($year),intval($referUserId),intval($month),$condition,intval($offset),intval($count));
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        $data = empty($ret)?array():$ret;
        return $data;
    }

    public function getCustomersMapRepaysListCount($referUserId,$year,$month,$repayType){

        if($repayType == 'alRepay'){
            // 已回款,查已正常还款和已提前还款的本金和利息
            $condition = " ( dlrc.repay_principal!=0 OR dlrc.prepay_principal!=0 OR dlrc.repay_interest!=0 OR dlrc.prepay_interest!=0 ) ";
        }else{
            // 即将回款，查待还本金和待还利息
            $condition = " (dlrc.norepay_principal!=0 OR dlrc.norepay_interest!=0) ";
        }
        $sql = sprintf("SELECT count(*) AS total
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                        firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s'
                        WHERE dlrc.repay_month='%s' AND %s
                        ",intval($year),intval($referUserId),intval($month),$condition);
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getOne($sql);
        $data = empty($ret)?0:$ret;
        return $data;
    }

    public function getCustomersRepaysDetailYMD($referUserId,$year,$month,$day,$offset,$count){
         $sql = sprintf("SELECT dlrc.repay_month,dlrc.repay_day,dlrc.user_id,
                            dlrc.repay_principal+dlrc.prepay_principal AS repay_principal,
                            dlrc.repay_interest+dlrc.prepay_interest AS repay_interest,
                            dlrc.norepay_principal AS no_repay_principal,
                            dlrc.norepay_interest  AS no_repay_interest
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                            firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s'
                        WHERE dlrc.repay_month='%s' AND dlrc.repay_day='%s'  LIMIT %s,%s
                        ",intval($year),intval($referUserId),intval($month),intval($day),intval($offset),intval($count));
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        $data = empty($ret)?array():$ret;
        return $data;
    }

    public function getCustomersMapRepaysStatistics($referUserId,$year,$month,$day=0){
        $sql = sprintf("SELECT
                            count(*) AS total,
                            sum(dlrc.repay_interest+dlrc.repay_principal+dlrc.prepay_principal+dlrc.prepay_interest) AS repay,
                            sum(dlrc.norepay_interest+dlrc.norepay_principal) AS no_repay
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                        firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s'
                        WHERE dlrc.repay_month='%s'
                        ",intval($year),intval($referUserId),intval($month));
        if(!empty($day)){
            $sql .= " AND dlrc.repay_day=$day ";
        }
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        $data = empty($ret)?array():$ret;
        return $data;
    }

    public function getCustomersRepayMonthDate($referUserId){
        $ret = array();
        $year = date("Y");
        for($i=0;$i< self::MAX_YEARS;$i++){
            $sql = sprintf("SELECT %s as repay_year,dlrc.repay_month
                        FROM firstp2p_deal_loan_repay_calendar_%s as dlrc INNER JOIN
                        firstp2p_coupon_bind as cb on dlrc.user_id=cb.user_id AND cb.refer_user_id='%s' group by dlrc.repay_month order by dlrc.repay_month
                        ",intval($year),intval($year),intval($referUserId));
            $thisYearData = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
            $ret = array_merge($ret,$thisYearData);
            $year++;
        }
        $data = empty($ret)?array():$ret;
        return $data;
    }
}
