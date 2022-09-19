<?php
/**
 * 信力分配试算
 */

use libs\db\Db;

class XinliAction extends CommonAction
{

    public function index()
    {
        //参数处理
        $xb_year = isset($_REQUEST['xb_year']) ? addslashes(trim($_REQUEST['xb_year'])) : 1; //亿个
        $money_day = isset($_REQUEST['money_day']) ? addslashes(trim($_REQUEST['money_day'])) : 16; //万元
        $conf_deal = isset($_REQUEST['conf_deal']) ? intval($_REQUEST['conf_deal']) : 600;
        $conf_zx = isset($_REQUEST['conf_zx']) ? intval($_REQUEST['conf_zx']) : 400;
        $conf_dt = isset($_REQUEST['conf_dt']) ? intval($_REQUEST['conf_dt']) : 500;
        $conf_coupon = isset($_REQUEST['conf_coupon']) ? intval($_REQUEST['conf_coupon']) : 1000;
        $conf_login = isset($_REQUEST['conf_login']) ? intval($_REQUEST['conf_login']) : 10;
        $log_date = isset($_REQUEST['log_date']) ? addslashes(trim($_REQUEST['log_date'])) : date('Y-m-d');

        if (empty($_REQUEST ['conf_deal'])) {
            $_REQUEST ['xb_year'] = "1";
            $_REQUEST ['money_day'] = "16";
            $_REQUEST ['conf_deal'] = "600";
            $_REQUEST ['conf_zx'] = "400";
            $_REQUEST ['conf_dt'] = "500";
            $_REQUEST ['conf_coupon'] = "1000";
            $_REQUEST ['conf_login'] = "10";
            $_REQUEST ['log_date'] = date('Y-m-d');
        }

        $xb_year = intval($xb_year * 100000000);
        $xb_day = floor(intval($xb_year)/(365));
        $this->assign('xb_day', round($xb_day/10000, 2));

        if (isset($_REQUEST['is_foreach']) && !empty($_REQUEST['is_foreach'])) {
            $candyAccountService = new core\service\candy\CandyAccountService();
            $activityPool = $candyAccountService->getAllActivityTotalToday();
            $activityPool = number_format($activityPool); //信力池
            $this->assign('activityPool', $activityPool);
        }

        $xinliModel = new \core\dao\XinliModel();

        $sql_id = "select (max(id)-2000000) max_id from firstp2p_vip_point_log";
        $id_max = Db::getInstance('vip', 'slave')->getOne($sql_id);

        //当日总分
        $sql_rule = " sum((case source_type when '1' then floor(source_amount*{$conf_deal}/10000) when '2' then floor(source_amount*{$conf_zx}/10000) when '9' then floor(source_amount*{$conf_dt}/10000) when '5' then {$conf_login} when '4' then {$conf_coupon} else 0 end)) sum_point ";
        $sql_deal  = " ,sum((case source_type when '1' then source_amount when '2' then source_amount when '9' then source_amount else 0 end)) sum_deal ";
        $sql_coupon  = " ,sum((case source_type when '4' then source_amount  else 0 end)) sum_coupon ";
        $sql_login  = " ,sum((case source_type when '5' then source_amount  else 0 end)) sum_login ";
        $sql_where = " from firstp2p_vip_point_log where id>{$id_max} and source_type in (1,2,4,5,9) ";
        $sql_where .= " and status=1 and from_unixtime(create_time, '%Y-%m-%d')='{$log_date}' ";
        $sql = "select ".$sql_rule.$sql_deal.$sql_coupon.$sql_login.$sql_where;
        $list_sum = $xinliModel->findBySql($sql);
        $list_sum = $list_sum->getRow();
        $sum_point = $list_sum['sum_point'];
        $sum_deal = round($list_sum['sum_deal']/10000);
        $sum_coupon = round($list_sum['sum_coupon']/1);
        $sum_login = round($list_sum['sum_login']/10000);
        $this->assign('sum_xb_day', $sum_point);
        $this->assign('sum_deal', $sum_deal);
        $this->assign('sum_coupon', $sum_coupon);
        $this->assign('sum_login', $sum_login);

        $sum_point_function = $xinliModel->getActivityTotalByDate($log_date);
        $this->assign('sum_point_function', $sum_point_function);

        //每分能换的T个数
        $one_point = round($xb_day/$sum_point,2);
        $this->assign('one_point', $one_point);

        $money_xb = round($money_day*10000/$xb_day, 2);
        $money_login = round($one_point*$money_xb*$conf_login, 2);
        $money_point = round($one_point*$money_xb, 2);
        $this->assign('money_xb', $money_xb);
        $this->assign('money_point', $money_point);
        $this->assign('money_login', $money_login);

        //按积分来源统计
        $sql_type = "select {$sql_rule}, source_type";
        $sql_type = $sql_type.$sql_where." group by source_type ";
        $list_type = $xinliModel->findAllBySql($sql_type, true);
        $source_type_str = array(
            '1'=>array('conf_xl'=>$conf_deal, 'name'=>'网贷'),
            '2'=>array('conf_xl'=>$conf_zx, 'name'=>'专享'),
            '3'=>array('conf_xl'=>$conf_deal, 'name'=>'黄金'),
            '4'=>array('conf_xl'=>$conf_coupon, 'name'=>'邀请首投'),
            '5'=>array('conf_xl'=>$conf_login, 'name'=>'签到'),
            '9'=>array('conf_xl'=>$conf_dt, 'name'=>'智多新')
        );
        foreach ($list_type as $k=>&$item) {
            if (empty($item['sum_point'])) {
                unset($list_type[$k]);
                continue;
            }
            $item['source_type_str'] = $source_type_str[$item['source_type']]['name'];
            $item['rate'] = round($item['sum_point']*100/$sum_point).'%';
            $item['value'] = round($item['sum_point']/intval($source_type_str[$item['source_type']]['conf_xl']));
        }
        $this->assign('list_type', $list_type);

        //按积分区间段统计分布情况
        $sql = "select count(distinct a.user_id) count_user, sum(a.sum_point) sum_point, round((a.sum_point)/100,0) point_w from ";
        $sql .= "(select {$sql_rule}, user_id ";
        $sql .= $sql_where;
        $sql .= " group by user_id  order by sum_point ";
        $sql .= ") a group by point_w order by point_w desc ";
        $list = $xinliModel->findAllBySql($sql, true);
        foreach ($list as &$item) {
            $item['point_zone'] = $item['point_w'] . '00';
            $item['point_zone_money'] = round($item['point_w'] * 100 * 1.05/ $conf_zx);
            $item['sum_point_rate'] = round($item['sum_point']*100/$sum_point) . '%';
            $item['xb'] = round($item['sum_point']*$xb_day/$sum_point);
            $item['xb_avg'] = round($item['sum_point']*$xb_day/($sum_point*$item['count_user']));
        }
        $this->assign('list', $list);
        $this->display();
    }

}
