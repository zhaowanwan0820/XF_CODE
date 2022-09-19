<?php
/**
 * 账户资金记录表
 * @date 2018-07-03
 **/
namespace core\dao\account;

use core\dao\ProxyModel;
use libs\db\Db;
use libs\utils\Logger;

use core\enum\DealEnum;

class AccountLogModel extends ProxyModel {

    public $isSplit = 2;

    public $isBackupDb = false;

    public function __construct($params = array()) {
        parent::__construct();
        foreach($params as $key => $value) {
            $this->$key = $value;
        }
        if($this->isBackupDb) {
            $this->db = Db::getInstance('firstp2p_moved', 'slave');
        }
    }

    public function getList($user_id, $t, $log_info, $start, $end, $limit, $list_count = 'only_list', $withoutSupervision = false, $excludedLogInfo = []) {
        $condition = "user_id=:user_id";
        if ($t == 'money') {
            $condition .= " AND (:t <> 0 OR lock_money <> 0)";
        }
        elseif ($t == 'money_only') {
            $condition .= " AND (money <> 0)";
        }
        elseif ($t != '') {
            $condition .= " AND (:t <> 0)";
        }

        if ($withoutSupervision) {
            $condition .= ' AND deal_type != '.DealEnum::DEAL_TYPE_SUPERVISION;
        }

        if(!empty($log_info)){
            $condition .= " AND log_info = ':log_info'";
        }

        if(!empty($start)){
            $condition .= " AND log_time >= :start";
        }

        if(!empty($end)){
            // TODO 为啥加1天...先注释掉, 等发现有问题的时候再查
            //$end = $end + 86400;
            $condition .= " AND log_time <= :end";
        }

        //排除的log_info
        if (!empty($excludedLogInfo)) {
            $condition .= sprintf(" AND log_info NOT IN ('%s')", implode("','", $excludedLogInfo));
        }

        $condition .= ' AND is_delete = 0';
        $order = " ORDER BY `log_time` DESC ,`id` DESC LIMIT :limit";

        $lim = " %d,%d ";
        $limit = sprintf($lim,$limit[0],$limit[1]);

        $params = array(
                    ':user_id' => $user_id,
                    ':t' => $t,
                    ':log_info' => $log_info,
                    ':start' => $start,
                    ':end' => $end,
                    ':limit' => $limit,
                );

        //由于findAllViaSlave专指firstp2p库的从库，所以需要这么判断一下，TODO:统一sql相关方法
        $list = array();
        $count = 0;
        if($this->isBackupDb === false) {
            if($list_count == 'both' || $list_count == 'only_list') {
                $list = $this->findAllViaSlave($condition . $order, true, '*', $params);
            }
            if($list_count == 'both' || $list_count == 'only_count') {
                $count = $this->countViaSlave($condition, $params);
            }
        }else{
            if($list_count == 'both' || $list_count == 'only_list') {
                $list = $this->findAll($condition . $order, true, '*', $params);
            }
            if($list_count == 'both' || $list_count == 'only_count') {
                $count = $this->count($condition, $params);
            }
        }

        return array("list"=>$list,'count'=>$count);
    }

    public function getAccountDetailList($param, $userLogTypes = 4)
    {
        $list = array();
        if (!empty($param['user_id']))
        {
            $condition = "user_id = :user_id";

            if ($param['log_time_start'] > 0) {
                $condition .= " AND log_time >= ':log_time_start'";
            }
            if ($param['log_time_end'] > 0) {
                $condition .= " AND log_time <= ':log_time_end'";
            }
            if (!empty($param['log_info'])) {
                $condition .= " AND log_info = ':log_info'";
            }
            $condition .= " AND deal_type IN (" .$userLogTypes. ")";

            $order = " ORDER BY `id` DESC";
            $condition .= $order;
            $limit = " LIMIT :limit";
            $lim = sprintf("%d, %d ", $param['limit'][0], $param['limit'][1]);

            $params = array(
                    ':user_id' => $param['user_id'],
                    ':log_time_start' => $param['log_time_start'],
                    ':log_time_end' => $param['log_time_end'],
                    ':log_info' => $param['log_info'],
                    ':limit' => $lim,
                );

            //由于findAllViaSlave专指firstp2p库的从库，所以需要这么判断一下，TODO:统一sql相关方法
            if($this->isBackupDb === false)
            {
                $list = $this->findAllViaSlave($condition.$limit, true, "*", $params);
            }
            else
            {
                $list = $this->findAll($condition.$limit, true, '*', $params);
            }

            if(isset($param['isNeedTotal']) && $param['isNeedTotal'] == 1){
                if($this->isBackupDb === false){
                    $total = $this->countViaSlave($condition, $params);
                }else{
                    $total = $this->count($condition, $params);
                }
                return array('list' => $list, 'total' => $total);
            }
        }
        return $list;
    }

}
