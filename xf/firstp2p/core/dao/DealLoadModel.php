<?php
/**
 * DealLoad class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;
use core\service\DealLoadService;
use core\service\DtDealService;
use core\dao\DealTransferLogModel;
use libs\utils\PaymentApi;

/**
 * DealLoad class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealLoadModel extends BaseModel {

    const BID_AGE_MIN = 18;
    const BID_AGE_MAX = 70;

    public static $SOURCE_TYPE = array(
        'general'     => 0, //前台正常投标
        'appointment' => 1, //后台预约投标
        'ios'         => 3, //ios客户端
        'android'     => 4, //安卓客户端
        'reservation' => 5, //前台预约投标
        'openapi'     => 6, //openAPI 目前支持即付使用
        'wap'         => 8, //WAP站投资
        'dtb'         => 9, //智多鑫投资
    );

    /**
     * 根据订单id获取投资列表
     * @param $deal_id int 订单id
     * @return array
     */
    public function getDealLoanList($deal_id) {
        $deal_id = intval($deal_id);
        $condition = "`deal_id`='%d' ORDER BY `id` ASC";
        $condition = sprintf($condition, $this->escape($deal_id));
        return $this->findAllViaSlave($condition, false, '*');
    }

    /**
     * 根据订单id获取投资列表（分页）
     * @param  integer $dealId
     * @param  integer $page
     * @param  integer $pageSize
     * @return array
     */
    public function getDealLoanListPageable($dealId, $page = 1, $pageSize = 20)
    {
        $dealId = intval($dealId);
        $page = intval($page);
        $pageSize = intval($pageSize);
        $start = $pageSize * ($page - 1);

        $condition = "`deal_id` = :dealId ORDER BY `id` ASC";
        $total = $this->countViaSlave($condition, [':dealId' => $dealId]);

        $condition .= " LIMIT {$start}, {$pageSize}";
        $list = $this->findAllViaSlave($condition, true, "*", [':dealId' => $dealId]);

        return ['list' => $list, 'total' => intval($total)];

    }

    /**
     * 根据用户id整合投资记录
     * @param $deal_id int
     * @return array
     */
    public function getDealLoanUserList($deal_id) {
        $deal_id = intval($deal_id);
        $sql = "SELECT SUM(`money`) as `m`, `user_id`, `site_id`, `create_time`, COUNT(`id`) AS `c` FROM " . $this->tableName() . " WHERE `deal_id`='{$deal_id}' GROUP BY `user_id`";
        $result = $this->findAllBySql($sql, true, array(), true);
        return $result;
    }

    /**
     * 根据用户id整合投资记录，排除预约投资
     * @param $deal_id int
     * @return array
     */
    public function getNonReserveDealLoanUserList($deal_id) {
        $deal_id = intval($deal_id);
        $sql = "SELECT SUM(`money`) as `m`, `user_id`, `site_id`, `create_time`, COUNT(`id`) AS `c` FROM " . $this->tableName() . " WHERE `deal_id`='{$deal_id}' AND `source_type` != '" . self::$SOURCE_TYPE['reservation'] . "' GROUP BY `user_id`";
        $result = $this->findAllBySql($sql, true, array(), true);
        return $result;
    }


    /**
     * 获取预约投资用户ID
     * @return array
     */
    public function getReserveDealLoanUserIds($startTime, $endTime) {
        $sql = sprintf("SELECT dl.user_id FROM `firstp2p_deal` AS d
                LEFT JOIN `firstp2p_deal_load` AS dl ON d.id = dl.deal_id
                INNER JOIN `firstp2p_reservation_deal_load` AS rdl ON dl.id = rdl.load_id
                WHERE d.repay_start_time >= %s AND d.repay_start_time <= %s GROUP BY dl.user_id", $startTime, $endTime);
        $result = $this->findAllBySqlViaSlave($sql, true);
        return $result;
    }

    /**
     * 根据用户id汇总预约投资记录
     * @param $userId int
     * @return array
     */
    public function getReserveDealLoanSumByUserId($userId, $startTime, $endTime) {
        $sql = sprintf("SELECT COUNT(*) AS c, SUM(dl.money) AS m FROM `firstp2p_deal` AS d
                LEFT JOIN `firstp2p_deal_load` AS dl ON d.id = dl.deal_id
                INNER JOIN `firstp2p_reservation_deal_load` AS rdl ON dl.id = rdl.load_id
                WHERE dl.user_id = %d AND d.repay_start_time >= %d AND d.repay_start_time <= %d", $userId, $startTime, $endTime);
        $result = $this->findAllBySqlViaSlave($sql, true);
        return !empty($result[0]) ? $result[0] : array();
    }

    public function getCountByID($deal_id){
        return $this->count("deal_id=':deal_id'",array(':deal_id'=>$deal_id));
    }

    /**
     * 根据用户id获取投资列表
     * @param $user_id int
     * @param $offset int
     * @param $page_size int
     * @param bool|int $status int 多个状态逗号隔开
     * @param bool $date_start string|false
     * @param bool $date_end string|false
     * @return array('count'=>$count, 'list'=>$list)
     */
    public function getUserLoadList($user_id, $offset, $page_size, $status=0, $date_start=false, $date_end=false, $type = '', $exclude_loantype = 0, $deal_type_id = 0) {
        $user_id = intval($user_id);
        $offset = intval($offset);
        $page_size = intval($page_size);
        //$status = intval($status); //会传入多个status字符串
        $deal_status = ($status == 0) ? "1,2,4,5" : $status;
        $deal_condition_str = '';
        if ($type !== '' && $type !== null) {
            $deal_condition_str .= ' AND d.deal_type IN('.$type.')';
        }
        if (intval($exclude_loantype) > 0) {
            $deal_condition_str .= ' AND d.loantype <> ' . intval($exclude_loantype);
        }

        if (!empty($deal_type_id)) {
            $deal_condition_str .= ' AND d.type_id='.intval($deal_type_id);
        }

        //需要过滤掉多投的标的
        $dt_tag = \core\dao\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT);
        $dt_tag_v3 = \core\dao\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT_V3);
        $dt_tag_arr = array();
        if(!empty($dt_tag)) {
            $dt_tag_arr[] = intval($dt_tag['id']);
        }
        if(!empty($dt_tag_v3)) {
            $dt_tag_arr[] = intval($dt_tag_v3['id']);
        }
        if (!empty($dt_tag_arr)) {
            $deal_condition_str .= sprintf(" AND d.id NOT IN (SELECT `deal_id` FROM %s WHERE `tag_id` IN (%s))", \core\dao\DealTagModel::instance()->tableName(), implode(',',$dt_tag_arr));
        }

        $condition = "FROM %s l WHERE l.`user_id`='%d' AND EXISTS (SELECT 1 FROM %s d WHERE l.`deal_id`=d.`id` AND d.`deal_status` IN (%s) AND d.`parent_id` != '0' AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0 {$deal_condition_str})";
        $condition = sprintf($condition, $this->tableName(), $this->escape($user_id), DealModel::instance()->tableName(), $this->escape(preg_replace("/'|\"/", "", $deal_status)));

        if ($date_start) {
            $condition .= sprintf(" AND l.`create_time`>='%d'", strtotime($this->escape($date_start)));
        }
        if ($date_end) {
            $condition .= sprintf(" AND l.`create_time`<'%d'", strtotime($this->escape($date_end))+3600*24);
        }

        $count_sql = "SELECT COUNT(*) " . $condition;
        $count = $this->countBySql($count_sql, array(), true);
        $sql = "SELECT l.* %s ORDER BY l.`id` DESC LIMIT %d, %d";
        $sql = sprintf($sql, $condition, $this->escape($offset), $this->escape($page_size));
        $result = $this->findAllBySql($sql, false, array(), true);
        return array("count"=>$count, "list"=>$result);
    }

    /**
     * 根据用户id获取投资数目
     * @param $user_id int
     * @return int
     */
    public function getLoanNumByUserId($user_id) {
        $user_id = intval($user_id);
        $condition = "`user_id`='%d' AND `deal_id` IN (SELECT `id` FROM %s WHERE `deal_status` IN (1,2,4,5) AND `parent_id` != '0')";
        $condition = sprintf($condition, $this->escape($user_id), DealModel::instance()->tableName());
        return $this->countViaSlave($condition);
    }

    /**
     * 已投资数目(包括流标等任意情况)
     *
     * @param $user_id int
     * @return integer
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function countByUserId($user_id, $is_slave = true) {
        $user_id = intval($user_id);
        $condition = "`user_id` = '%d'";
        $condition = sprintf($condition, $this->escape($user_id));
        if ($is_slave) {
            return $this->countViaSlave($condition);
        } else {
            return $this->count($condition);
        }
    }

    /**
     * 已成功投资数目(流标、标被删除、无效均不算)
     *
     * @param $user_id int
     * @param $source_type array    来源 3:ios 4:android
     * @param  $source_allow 是否包含source_type
     * @return integer
     */
    public function getCountByUserIdInSuccess($user_id, $source_type = array(),$source_allow=true) {
        $sql = "SELECT count(id) FROM %s WHERE `user_id` = ':user_id'";
        if ( !empty($source_type)) {
            $source_type_str = implode(',', $source_type);
            if($source_allow){
                $sql .= " AND `source_type` in ({$source_type_str})";
            }else{
                $sql .= " AND `source_type` not in ({$source_type_str})";
            }
        }
        $sql .= " AND `deal_id` IN (SELECT `id` FROM %s WHERE `deal_status` IN (1,2,4,5) AND is_delete = '0' AND is_effect = '1')";
        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
        $param[':user_id'] = $user_id;
        $cnt = $this->countBySql($sql,$param,true);
        return intval($cnt);
    }

    /**
     * 获取用户已成功投资金额总数(流标、标被删除、无效均不算)
     *
     * @param $user_id int
     * @return string
    */
    public function getAmountByUserIdInSuccess($user_id) {
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s  WHERE user_id = ':user_id'  AND `deal_id` IN (SELECT `id` FROM %s WHERE `deal_status` IN (1,2,4,5) AND is_delete = '0' AND is_effect = '1' AND `parent_id` != '0')";
        $param = array(':user_id'=>$user_id);
        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
        $result = $this->findBySql($sql,$param, true);
        return $result['sum'];
    }



    /**
     * 获取投资记录及用户信息
     *
     * @param $deal_id int 借款id
     * @param $load_id int 投资id
     * @return array
     */
    public function getLoadDetailInfo($deal_id, $load_id) {
        $deal_id = intval($deal_id);
        $load_id = intval($load_id);
        $loan_sql = "SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time
                FROM %s as d,%s as u
                WHERE d.deal_id = '%d' AND d.user_id = u.id AND d.id = '%d'";
        $sql = sprintf($loan_sql, $this->tableName(), UserModel::instance()->tableName(), $this->escape($deal_id), $this->escape($load_id));
        return $this->findBySql($sql);
    }

    /**
     * 根据cate获取投资人累计投资
     * @param int $site_id
     * @return float
     */
    public function getTotalLoanMoneyByCate($site_id = 0,$cate =0) {

        if(!empty($cate)){
            $sql_cate = sprintf('SELECT `id` FROM %s WHERE `type_id`=%d AND `deal_status` IN (2, 4, 5)',DealModel::instance()->tableName()
                    ,$this->escape($cate));
            $str='';
            foreach ($this->findAllBySql($sql_cate, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $sql_cate=substr($str,1);

        }else{
            $sql_cate = sprintf('SELECT `id` FROM %s WHERE `deal_status` IN (2, 4, 5)',DealModel::instance()->tableName());
            $str='';
            foreach ($this->findAllBySql($sql_cate, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $sql_cate=substr($str,1);
        }
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id` IN ($sql_cate)";
        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());

        if (!empty($site_id)) { //所有分站显示所有的
            $sql .= sprintf(" AND `site_id` in(%s)", $this->escape($site_id));
        }
        $result = $this->findBySql($sql, null, true);

        return $result['sum'];
//         if(!empty($cate)){
//             $sql_cate = sprintf('SELECT `id` FROM %s WHERE `type_id`=%d AND `deal_status` IN (2, 4, 5)',DealModel::instance()->tableName()
//                 ,$this->escape($cate));

//         }else{
//             $sql_cate = sprintf('SELECT `id` FROM %s WHERE `deal_status` IN (2, 4, 5)',DealModel::instance()->tableName());
//         }
//         $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id` IN (".$sql_cate.")";
//         $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
//         if (!empty($site_id)) { //所有分站显示所有的
//             $sql .= sprintf(" AND `site_id` in(%s)", $this->escape($site_id));
//         }
//         $result = $this->findBySql($sql, null, true);

//         return $result['sum'];
    }

    /**
     * 获取投资人累计投资
     * @param int $site_id
     * @return float
     */
    public function getTotalLoanMoney($site_id = 0) {
        $siteCond = '';
        if($site_id != 0){
            $siteCond = sprintf(" AND site_id IN (%s)", $site_id);
        }
        $sql = sprintf("SELECT SUM(`money`) AS `sum` FROM %s WHERE is_repay = 0 %s", $this->tableName(), $siteCond);
        $result = $this->findBySql($sql, null, true);
        return $result['sum'];
    }

    /**
     * 根据订单id获取投资数目 * @param int $deal_id
     * @return array
     */
    public function getLoadCount($deal_id) {
        $deal_id = intval($deal_id);
        $sql = "SELECT COUNT(*) AS `buy_count`, SUM(money) AS `load_money` FROM %s WHERE `deal_id`='%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($deal_id));
        return $this->findBySql($sql, null, false);
    }

    public function getLoanUserList($deal_id) {
        $sql = "SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time FROM "
                .DB_PREFIX."deal_load as d,".DB_PREFIX."user as u WHERE d.deal_id = ".intval($deal_id)." AND d.user_id = u.id";
        return $this->db->get_slave()->getAll($sql);
    }


    /**
     * 获取最近$num 条投资用户信息(不含重复)
     * @param $num
     * @return mixed
     */
    public function getLastLoadList($num){
        $sql = "SELECT u.id,u.mobile,d.money as loan_money FROM  firstp2p_deal_load as d,firstp2p_user as u WHERE  d.user_id = u.id ORDER BY d.id desc limit ".$num;
        return $this->db->get_slave()->getAll($sql);
    }

    public function getLoadUsersNumByTime($startTime){
        $sql = "SELECT count(DISTINCT loan_user_id) as cnt  FROM `firstp2p_supervision_idempotent` WHERE type=1 AND create_time >=".$startTime;
        $cnt = $this->countBySql($sql,array(),true);
        return $cnt;
    }

    /**
     * 根据user_id获取投资人某段时间内累计投资总额
     * @param user_id|int $user_id int
     * @param bool $date_start int|false
     * @param bool $date_end int|false
     * @return float
     * @author zhanglei5@ucfgroup.com
     */
    public function getTotalLoanMoneyByUserId($user_id,$date_start = false, $date_end = false,$deal_status=array(4)) {
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s as dl WHERE dl.user_id = ':user_id' AND `deal_id` IN (SELECT `id` FROM %s WHERE `deal_status` IN (".implode(',',$deal_status)."))";
        $param = array(':user_id'=>$user_id);

        if ($date_start) {
            $sql .= " AND dl.`create_time`>=':date_start'";
            $param[':date_start'] = $date_start;
        }

        if ($date_end) {
            $sql .= " AND dl.`create_time`< ':date_end'";
            $param[':date_end'] = $date_end;
        }

        //strtotime($this->escape($date_end))+3600*24

        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
        $result = $this->findBySql($sql, $param, true);
        return $result['sum'];
    }

    /**
     * 获取项目用户已投资金额总数
     * @param $uid
     * @param $deal_id
     */
    function getUserLoadMoneyByDealid($uid,$deal_id){
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s  WHERE user_id = ':user_id' AND deal_id =':deal_id' ";
        $param = array(':user_id'=>$uid,':deal_id'=>$deal_id);
        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
        $result = $this->findBySql($sql,$param, true);
        return $result['sum'];
    }

    /**
     * 获取交易次数，投资的笔数
     * @param $site_id
     */
    function getDealTimes($site_id) {
        $condition = sprintf("`site_id`='%d' ", $this->escape($site_id));
        return $this->countViaSlave($condition);
    }

    /**
     * 获取用户所有投资的借款id
     * @param $user_id
     * @return string
     */
    public function getUserLoadDealId($user_id = 0){

        //PaymentApi::log('DealLoadModel.getUserLoadDealId.1:'.$user_id);
        $user_id = ($user_id == 0 && !empty($GLOBALS['user_info'])) ? $GLOBALS['user_info']['id'] : intval($user_id);
        if($user_id <= 0){
            return false;
        }
        //PaymentApi::log('DealLoadModel.getUserLoadDealId.2:'.$user_id);

        static $bid_deals = array();
        if(!isset($bid_deals[$user_id])){
            $condition = "user_id = ':user_id'";
            $fields = "DISTINCT(`deal_id`)";
            $result = $this->findAllViaSlave($condition, true, $fields, array(':user_id' => $user_id));
            $deal_id_arr = array();
            if($result){
                foreach($result as $row){
                    $deal_id_arr[] = $row['deal_id'];
                }
            }
            //PaymentApi::log('DealLoadModel.getUserLoadDealId.3:'.serialize($result));
            $bid_deals[$user_id] = $deal_id_arr ? implode(',', $deal_id_arr) : '';
        }
        //PaymentApi::log('DealLoadModel.getUserLoadDealId.4:'.json_encode($bid_deals, JSON_UNESCAPED_UNICODE));

        return $bid_deals[$user_id];
    }

    /**
     * subsidyToLoaner 贴息给借款人
     */
    public function subsidyToLoaner()
    {
        if ($this->isNotYetSubsidy() && $this->getSubsidyMoney() > 0) {
            $this->platformUserPayToLoaner();

            $this->sendWebMessage4Subsidy();
            $this->sendSms4Subsidy();
            $this->subsidyYet();
        }
    }

    /**
     * isNotYetSubsidy 是否没有贴息过
     */
    private function isNotYetSubsidy()
    {
        $dealTransferLog = DealTransferLogModel::instance()->findByOwner($this);
        return empty($dealTransferLog);
    }

    /**
     * subsidyYet 记log, 已贴息过
     */
    private function subsidyYet()
    {
        $dealLoadService = new DealLoadService();
        $dealLoadService->addDealTransferLog($this, app_conf('DEAL_CONSULT_FEE_USER_ID'), $this->user_id, $this->getSubsidyMoney());
    }

    /**
     * pushToFundsTrusteeship 同步到资金托管
     */
    private function pushToFundsTrusteeship()
    {
        $platformUser = UserModel::instance()->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));

        $syncRemoteData[] = array(
            'outOrderId' => $this->deal_id,
            'payerId' => $platformUser->id,
            'receiverId' => $this->getLoaner()->id,
            'repaymentAmount' => bcmul($this->getSubsidyMoney(), 100),
            'curType' => 'CNY',
            'bizType' => 5,
            'batchId' => '',
        );

        if (!FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH)) {
            throw new \Exception('subsidyToLoaner push 失败');
        }
    }

    /**
     * platformUserPayToLoaner 把平台用户的钱转给借款人
     */
    private function platformUserPayToLoaner()
    {
        $money = $this->getSubsidyMoney();
        $loaner = $this->getLoaner();
        $bizToken = [
            'dealId' => $this->deal_id,
            'dealLoadId' => $this->id,
        ];
        $comment = "编号{$this->deal_id},{$this->getDeal()->name}, 投资记录ID:{$this->id} ".UserLogModel::LOG_INFO_SUBSIDY;
        if (!$loaner->changeMoney($money, UserLogModel::LOG_INFO_SUBSIDY, $comment, 0, 0, 0, 0, $bizToken)) {
            throw new \Exception("loaner changeMoney fail 投资记录ID:{$this->id}, money:+{$money}");
        }


        $platformUser = UserModel::instance()->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));

        if (!$platformUser->changeMoney(-$money, UserLogModel::LOG_INFO_SUBSIDY, $comment, 0, 0, 0, 0, $bizToken)) {
            throw new \Exception("platformUser changeMoney fail 投资记录ID:{$this->id}, money:-{$money}");
        }


        $this->pushToFundsTrusteeship();
    }

    /**
     * getSubsidyMoney 获得要贴息的钱
     *
     */
    private function getSubsidyMoney()
    {
        return bcmul($this->getDeal()->getDealExt()->income_subsidy_rate / 100, $this->money, 2);
    }

    /**
     * getDeal 获得标
     */
    public function getDeal()
    {
        return DealModel::instance()->find($this->deal_id);
    }

    /**
     * sendWebMessage4Subsidy 为平台贴息, 发系统消息
     */
    private function sendWebMessage4Subsidy()
    {
        $title = '平台贴息率完成提示';
        $content = <<<CCC
您投资的“{$this->getDeal()->name}”项目已成交并放款给融资人，已得到{$this->getSubsidyMoney()}元的平台贴利率。 请到资金记录查看。
CCC;

        send_user_msg($title, $content, 0, $this->user_id, get_gmtime(), false, true, true);
    }

    /**
     * sendSms4Subsidy 为平台贴息, 发短信
     */
    private function sendSms4Subsidy()
    {
        require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
        $msgcenter = new \Msgcenter();

        $msgData = array(
            $this->getDeal()->name,
            $this->getSubsidyMoney(),
            );
        // SMSSend 平台贴息率完成提示短信, 暂不支持企业用户
        $msgcenter->setMsg($this->getLoaner()->mobile, $this->getLoaner()->id, $msgData, 'TPL_SMS_FIRSTP2P_SUBSIDY', '平台贴息率完成提示');
        $msgcenter->save();
    }

    /**
     * getLoaner 获得借款人
     */
    public function getLoaner()
    {
        return UserModel::instance()->find($this->user_id);
    }

    /**
     * 获取第一个投资
     * @author  pengchanglu@ucfgroup.com
     * @param $deal_id
     * @return array
     */
    public function getDealLoadFirst($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }
        $sql_first = "SELECT * FROM `firstp2p_deal_load` WHERE deal_id = '{$deal_id}' ORDER BY id ASC LIMIT 1";
        $load_first = $this->findBySql($sql_first, null, false);
        return $load_first;
    }

    /**
     * 获取最后一个投资
     * @author  pengchanglu@ucfgroup.com
     * @param $deal_id
     * @return array
     */
    public function getDealLoadLast($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }
        $sql_last = "SELECT * FROM `firstp2p_deal_load` WHERE deal_id = '{$deal_id}' ORDER BY id DESC LIMIT 1";
        $load_last = $this->findBySql($sql_last, null, false);
        return $load_last;
    }

    /**
     * 获取某个标投资最多用户
     * @author  pengchanglu@ucfgroup.com
     * @param $deal_id
     * @return array
     */
    public function getDealLoadMoneyMost($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }
        $sql_more = "SELECT * FROM `firstp2p_deal_load` WHERE deal_id = '{$deal_id}' ORDER BY money DESC, id ASC LIMIT 1";
        $load_more = $this->findBySql($sql_more, null, false);
        return $load_more;
    }

    /**
     * getBefore
     * 在这之前投资情况
     * @param mixed $deal_load_id
     * @param mixed $user_id
     * @access public
     * @return array
     */
    public function getBefore($id, $user_id) {
        $id = intval($id);
        $user_id = intval($user_id);
        if (!$id) {
            return false;
        }

        if (!$user_id) {
            return false;
        }

        $sql = "SELECT `money` FROM `firstp2p_deal_load` WHERE `id` <= ':id' AND `user_id` = ':user_id' order by `id` desc limit 2";
        $load = $this->findAllBySql($sql, true, array(':id' => $id, ':user_id' => $user_id), true);
        return $load;
    }

    /**
     * 获取某个时间段，某个标的投资金额概况
     * @param unknown $deal_id
     * @param number $time_start
     * @param number $time_end
     * @return unknown
     */
    public function getLoadStatByDeal($deal_id, $time_start = 0, $time_end = 0){
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s  WHERE `deal_id` = ':deal_id'";
        $param[':deal_id'] = $deal_id;
        if($time_start){
            $sql .= " AND `create_time` >= ':time_start'";
            $param[':time_start'] = $time_start;
        }
        if($time_end){
            $sql .= " AND `create_time` <= ':time_end'";
            $param[':time_end'] = $time_end;
        }
        $sql = sprintf($sql, $this->tableName(), $this->tableName());
        $result = $this->findBySql($sql, $param, true);
        return $result['sum'] > 0 ? $result['sum'] : 0;
    }

    /**
     * 获取用户可以赎回的利滚利投资列表
     * @param $uid 用户 id
     * @param $time 赎回 时间
     * @return bool|int
     */
    public function getUserCompoundList($uid){
        if(!$uid){
            return 0;
        }
        $sql = " SELECT d.`id` AS deal_load_id ,d.`deal_id` AS deal_id , d.`money`,d.`user_id` AS user_id
        FROM `firstp2p_deal_load` AS d LEFT JOIN `firstp2p_compound_redemption_apply` AS c  ON c.`deal_load_id` = d.id
        WHERE d.user_id = '%s' AND d.deal_type = 1 AND c.`deal_id` IS NULL ";
        $sql = sprintf($sql, $uid);
        //$sql,$is_array=false, $params = array(), $is_slave=false
        $list = $this->findAllBySql($sql, true, array(),true);
        return $list;
    }

    /**
     * getCountByDealIds
     *
     * @param array $deal_ids
     * @access public
     * @return void
     */
    public function getCountByDealIds($deal_ids) {
        if (count($deal_ids) > 0) {
            $ids = implode(',', $deal_ids);
            $sql = "select count(id) as cnt, deal_id from firstp2p_deal_load where deal_id in ($ids) group by firstp2p_deal_load.deal_id";
            $rs = $this->findAllBySql($sql, true);

            if (is_array($rs) && count($rs) > 0) {
                return $rs;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    /**
     * getDealLoadByIds
     * 通过投资记录数组，查询投资信息
     * @param array $deal_ids
     * @access public
     * @return void
     */
    public function getDealLoadByIds($ids) {
        if (count($ids) > 0) {
            foreach($ids as $val){
                $idsArray[] = intval($val);
            }
            $idsString = implode(',', $idsArray);
            $sql = "select id, deal_id, user_id, short_alias, money, site_id  from firstp2p_deal_load where id in ($idsString)";
            $rs = $this->findAllBySql($sql, true);

            if (is_array($rs) && count($rs) > 0) {
                return $rs;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }





    public function setIsrepayByDealId($deal_id, $is_repay = 1) {
        $sql = "UPDATE " . $this->tableName() . " SET `is_repay`='1', `update_time`='".get_gmtime()."' WHERE `deal_id`='{$deal_id}'";
        return $this->db->query($sql);
    }

    /**
     * 根据用户id某个标投资数目
     * @param $deal_id int
     * @param $user_id int
     * @return int
     */
    public function getDealLoanNumByUserId($deal_id, $user_id = 0) {
        $user_id = ($user_id == 0 && !empty($GLOBALS['user_info'])) ? $GLOBALS['user_info']['id'] : intval($user_id);
        if($user_id <= 0){
            return false;
        }
        $condition = "`user_id`='%d' AND `deal_id` = '%d' AND `deal_parent_id` != '0'";
        $condition = sprintf($condition, $this->escape($user_id), $this->escape($deal_id));
        return $this->countViaSlave($condition);
    }

    public function getDealInfoByLoadId($load_id) {
        $sql = 'SELECT d.name, dl.id, dl.deal_id, dl.money FROM firstp2p_deal_load dl, firstp2p_deal d WHERE d.id = dl.deal_id AND dl.id = ' . intval($load_id);
        $deal_load_detail = $this->findBySql($sql, null, true);
        return $deal_load_detail;
    }

    /**
     * 获取用户的第一次投标记录
     */
    public function getFirstDealByUser($userId, $slave = false) {
        $userId = intval($userId);
        if (!$userId) {
            return false;
        }
        $sql_first = "SELECT id, deal_id, money FROM `firstp2p_deal_load` WHERE user_id = '{$userId}' ORDER BY id ASC LIMIT 1";
        $load_first = $this->findBySql($sql_first, null, $slave);
        return $load_first;
    }

    /**
     * 获取用户第一次和第二次的投标记录
     */
    public function getFirstTwoDealByUser($userId, $slave = false) {
        $userId = intval($userId);
        if (!$userId) {
            return false;
        }

        $sql = "SELECT id, deal_id, money FROM `firstp2p_deal_load` WHERE user_id = '{$userId}' ORDER BY id ASC LIMIT 2";
        return $this->findAllBySql($sql, true, null, $slave);
    }

    /**
     * 通过用户user_id获取用户第一次使用邀请码的记录
     * @param int userId
     * @return boolean| array
     */
    public function getCouponFirstByUserId($userId)
    {
        if (!$userId) {
            return false;
        }

        $sql = "select short_alias from `firstp2p_deal_load` WHERE user_id = ".intval($userId)." and short_alias != '' ORDER BY id desc LIMIT 1";
        $first_coupon = $this->findBySql($sql, null, false);
        return $first_coupon;
    }

    /**
     * 通过loantype获取用户投资标的
     * @param int $userId
     * @param int $loantype
     * @param int $offset
     * @param int $count
     * @param bool $isTotal
     * @return array | bool
     * @author longbo
     */
    public function getDealLoadByLoantype($userId = null, $loantype = 0, $offset = 0, $count = 10, $isTotal = false)
    {
        $userId = intval($userId);
        if (empty($userId)) {
            return false;
        }

        $rs = array();
        $deal_tb = DealModel::instance()->tableName();

        $where_str = '';
        if (intval($loantype)) {
            $where_str = ' AND loantype = ' . intval($loantype);
        }

        $condition = " user_id = {$userId} AND deal_id IN (SELECT id FROM {$deal_tb} WHERE deal_status <> 3 {$where_str}) ";
        $sql = "SELECT %s FROM {$this->tableName()} WHERE " . $condition;

        if ($isTotal === true) {
            $sum = " SUM(money) as sum ";
            $sql_sum = sprintf($sql, $sum);
            $sum_res = $this->findBySql($sql_sum, null, true);
            $rs['sum'] = $sum_res['sum'];
        }
        if (intval($count) > 0) {
            $offset = intval($offset);
            $count = intval($count);
            $order_by = " ORDER BY id DESC ";
            $limit = " LIMIT {$offset}, {$count} ";
            $sql_list = sprintf($sql . $order_by . $limit, 'id, deal_id, user_id, money, create_time');
            $rs['list'] = $this->findAllBySql($sql_list, false, array(), true);
            $rs['total'] = $this->countViaSlave($condition);
        }

        return $rs;
    }

    public function checkDailyFirstDeal($userId, $dealLoadId) {

        $sql = "SELECT id, deal_id, money, create_time FROM `firstp2p_deal_load` WHERE user_id = '{$userId}' AND id <= '{$dealLoadId}' ORDER BY id DESC LIMIT 2";
        $loadData = $this->findAllBySql($sql);
        if (count($loadData) == 1) {
            return true;
        }

        $currentDay = strtotime(date('Y-m-d', $loadData[0]['create_time'] + 28800)) - 28800;
        if ($loadData[0]['create_time'] >= $currentDay && $loadData[1]['create_time'] < $currentDay) {
            return true;
        }

        return false;
    }
    /**
     * 根据还款方式获取用户投资情况(公益标)
     * @param int $user_id
     * @param int $loantype
     * @return array
     */
    public function getLoantypeSummaryByUser($user_id, $loantype) {
        if (!$user_id || !$loantype) {
            return false;
        }
        $result = array();

        $sql = "SELECT SUM(`money`) AS `sum`, COUNT(DISTINCT(`deal_id`)) AS `cnt` FROM %s WHERE `user_id` = '%d' AND `deal_id` IN (SELECT `id` FROM %s WHERE `loantype` = '%d' AND `deal_status` != 3)";
        $sql = sprintf($sql, $this->tableName(), $user_id, DealModel::instance()->tableName(), $loantype);
        $res = $this->findBySqlViaSlave($sql);
        $result = array(
            'sum' => $res['sum'],
            'count' => $res['cnt'],
            'deal_id' => 0,
            'money' => 0,
            'loan_time' => 0,
        );
        if ($res['sum'] && $res['cnt']) {
            $sql = "SELECT `deal_id`, SUM(`money`) AS `m`, create_time FROM %s WHERE `user_id` = '%d' AND `deal_id` IN (SELECT `id` FROM %s WHERE `loantype` = '%d' AND `deal_status` != 3) GROUP BY `deal_id` ORDER BY `m` DESC LIMIT 1";
            $sql = sprintf($sql, $this->tableName(), $user_id, DealModel::instance()->tableName(), $loantype);
            $res = $this->findBySqlViaSlave($sql);

            $result['deal_id'] = $res['deal_id'];
            $result['money'] = $res['m'];
            $result['loan_time'] = $res['create_time'];
        }

        return $result;
    }

    /**
     * 根据用户id与标的id获取投资本金
     * @param int $user_id
     * @param sting $str_ids
     * @return float
     */
    public function getDealLoadMoneyByDealIds($user_id, $str_ids) {
        $sql = "SELECT SUM(`money`) AS `m` FROM " . $this->tableName() . " WHERE `user_id` = ':user_id' AND `deal_id` IN (:str_ids)";
        $param = array(
            ':user_id' => intval($user_id),
            ':str_ids' => $str_ids,
        );
        $result = $this->findBySqlViaSlave($sql, $param);
        return $result['m'];
    }

     /**
     * 根据siteid获取用户投资记录
     * @param int $site_id
     * @return model
     */
    public function getLoadBySiteId($site_id = 1, $offset = 0, $count = 1000, $updateTime = 0, $sortType = 0)
    {
        $site_id = intval($site_id);
        $userTable = UserModel::instance()->tableName();
        $fields = "l.id, l.deal_id, l.user_id, l.user_name, l.user_deal_name, l.money, l.short_alias as code, l.create_time, l.update_time, l.is_repay, l.deal_type, l.site_id, u.site_id as user_site, u.group_id";
        $select = "SELECT {$fields} FROM {$this->tableName()} l LEFT JOIN {$userTable} u ON l.user_id = u.id";
        $condition = " WHERE l.site_id = :site_id ";
        $params = array();
        if (intval($updateTime) > 0) {
            $condition .= " AND (l.create_time > :update OR l.update_time > :update) ";
            if ($sortType == 1) {
                $condition .= " ORDER BY l.update_time ASC ";
            } else {
                $condition .= " ORDER BY l.update_time DESC ";
            }
            $params[':update'] = intval($updateTime);
        } elseif ($sortType == 1) {
            $condition .= " ORDER BY l.id ASC ";
        } else {
            $condition .= " ORDER BY l.id DESC ";
        }
        $condition .= " LIMIT :offset, :count ";
        $params[':site_id'] = intval($site_id);
        $params[':offset'] = intval($offset);
        $params[':count'] = intval($count);
        $ret = $this->findAllBySqlViaSlave($select . $condition, true, $params);
        return empty($ret) ? array() : $ret;
    }

    /**
     * 获取单个用户单标的投资金额
     * @param int $user_id
     * @param int $deal_id
     * @return float
     */
    public function getSumByDealUserId($user_id, $deal_id) {
        $sql = sprintf("SELECT SUM(`money`) AS `m` FROM " . $this->tableName() . " WHERE `deal_id`='%d' AND `user_id` = '%d'", $deal_id, $user_id);
        $result = $this->findBySqlViaSlave($sql);
        return $result && $result['m'] ? $result['m'] : 0;
    }

    /**
     * [getLastInsertID 获取最大的id]
     * @author <fanjingwen@ucfgroup.com>
     * @return [int]
     */
    public function getLastInsertID()
    {
        $sql = 'SELECT MAX(`id`) as `max_id` FROM ' . $this->tableName();
        $return = $this->findBySqlViaSlave($sql);
        return $return['max_id'];
    }

    /**
     * [getP2pUserDealData 从指定id开始，获取一定数量的交易信息]
     * @author <fanjingwen@ucfgroup.com>
     * @param  [int] $begin_id          [开始的id]
     * @param  [int] $once_catch_counts [要的数量]
     * @return [array]                  [交易信息]
     */
    public function getP2pUserDealData($begin_id, $once_catch_counts)
    {
        $sql = "SELECT `load_table`.`id`, `user_table`.`real_name`, `user_table`.`idno`, `user_table`.`mobile`, `load_table`.`money`, `load_table`.`create_time` from `firstp2p_deal_load` AS `load_table`  LEFT JOIN `firstp2p_user` AS `user_table` ON load_table.`user_id` = user_table.`id` where load_table.`id` > {$begin_id} AND load_table.`is_repay` = 0 LIMIT {$once_catch_counts}";
        $result = $this->findAllBySqlViaSlave($sql, true);
        return $result;
    }

    /**
     * 披露信息投资人总数
     * @string $deal_types 标的类型
     * @return float
     */
    public function getPublishDistinctUserTotal($deal_types = '') {
        $deal_type_cond = '';
        if(!empty($deal_types)) {
            $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
        }
        $sql = 'SELECT COUNT(DISTINCT(user_id)) as total FROM '. $this->tableName() .' WHERE is_repay = 0 %s ';
        $sql = sprintf($sql,$deal_type_cond);
        $res = $this->findBySqlViaSlave($sql);
        if(empty($res)) {
            return 0;
        }
        return intval($res['total']);
    }

    /*
     * 获取用户在此标的的投资笔数
     * @param int $deal_id
     * @param int $user_id
     * @param boolen $exclude_reservation 是否排除前台预约投标
     * @return int
     */
    public function getDealLoadCountsByUserId($deal_id, $user_id, $exclude_reservation = false) {
        $condition = sprintf('`deal_id` = %d AND `user_id` = %d', $deal_id, $user_id);
        $condition .= $exclude_reservation ? sprintf(' AND source_type != %d ', self::$SOURCE_TYPE['reservation']) : '';
        return $this->count($condition);
    }

    /**
     * 获取非预约标的投资用户 id 集合
     * @param $deal_id int
     * @return array
     */
    public function getDealLoanUserIdsExReservation($deal_id) {
        $cond = sprintf(' `deal_id` = %d AND `source_type` != %d GROUP BY `user_id` ', $deal_id, self::$SOURCE_TYPE['reservation']);
        $res =  $this->findAll($cond, true, '`user_id`');
        $loan_user_id_collection = array();
        foreach ($res as $user_id) {
            $loan_user_id_collection[] = $user_id['user_id'];
        }
        return $loan_user_id_collection;
    }

    /**
     * 根据 deal_id 获取预约投标的 load_id 集合
     * @param int $deal_id
     * @return array
     */
    public function getReserveDealLoadIdsByDealId($deal_id)
    {
        if ($deal_id <= 0) {
            return array();
        }

        $cond = sprintf(' deal_id = %d AND source_type = %d ', $deal_id, self::$SOURCE_TYPE['reservation']);
        $deal_load_arr = $this->findAllViaSlave($cond, true, 'id');

        // 回调，获取 id 集合
        $func_get_load_ids = function ($deal_load) {
            return $deal_load['id'];
        };
        return empty($deal_load_arr) ? array() : array_map($func_get_load_ids, $deal_load_arr);
    }

    /**
     * 查询用户是否有【投资】记录
     * @param int $userId
     * @return \libs\db\model
     */
    public function hasExistByUserId($userId) {
        $data = $this->findByViaSlave("user_id=':user_id' LIMIT 1", 'id', array(':user_id'=>(int)$userId));
        return !empty($data['id']) ? true : false;
    }

    /**
     * 获取用户在某标的的投资总额
     * @param $userId
     * @param $dealId
     * @return int
     */
    public function getUserTotalMoneyByDeal($userId,$dealId){
        $sql = 'SELECT sum(money) as money FROM '. $this->tableName() .' WHERE deal_id = %d AND user_id=%d';
        $sql = sprintf($sql,$dealId, $userId);
        return $this->countBySql($sql);
    }

    /**
     * 获取最新投资动态
     * @param $deal_id int 订单id
     * @return array
     */
    public function getNewLoads($deal_type, $money, $limit = 10, $limitTime = 86400) {
        $deal_type = intval($deal_type);
        $money = floatval($money);
        $limit = intval($limit);
        $last = get_gmtime() - $limitTime;
        $sql = 'SELECT s.* FROM (SELECT id,deal_type,create_time,user_id,money,site_id FROM %s ORDER BY id DESC LIMIT 2000) as s WHERE s.deal_type IN (%s) AND s.money>%s AND s.create_time>%s AND s.site_id=1 ORDER BY s.id DESC LIMIT %s';
        $deal_type_str = $deal_type == 0 ? '0' : '2,3';
        $execSql = sprintf($sql, $this->tableName(), $deal_type_str, $money, $last, $limit);
        return $this->findAllBySqlViaSlave($execSql, true);
    }

    /**
     * 获取用户最新几笔投资
     * @return array
     */
    public function getUserNewLoads($userId, $limit = 10) {
        $userId = intval($userId);
        $limit = intval($limit);
        $condition = "user_id = {$userId}";
        $condition .= " ORDER BY `id` DESC ";
        $condition .= " LIMIT {$limit} ";
        return $this->findAllViaSlave($condition, true, '*');
    }

}
