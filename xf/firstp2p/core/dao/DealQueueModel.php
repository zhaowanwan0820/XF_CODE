<?php
/**
 * DealQueueModel.php
 * @author wangyiming@ucfgroup.com
 **/

namespace core\dao;

use core\dao\DealModel;
use core\dao\DealSiteModel;
use core\dao\DealQueueInfoModel;
use core\dao\DealParamsConfModel;
use core\dao\ReservationMatchModel;
use core\dao\DealLoanTypeModel;

use core\service\DealTagService;
use core\service\ReservationMatchService;

class DealQueueModel extends BaseModel {

    //投资期限单位
    const INVEST_DEADLINE_UNIT_DAY = 1; //天
    const INVEST_DEADLINE_UNIT_MONTH = 2; //月
    const INVEST_DEADLINE_UNIT_NULL = 0; //无，用于区别增加本字段之前和之后的队列


    /**
     * 当一个标的满标时，检查此标的是不是标的队列的队首，若是队首，则自动将队列后的标的上标
     * @param int $deal_id
     * @return bool
     */
    public function startDealAutoByQueue() {
        try {
            // 忽略某些符合条件的队列
            if ($this->isIgnored()) {
                return true;
            }

            $arr_deal_id_queue = DealQueueInfoModel::instance()->getDealIdsByQueueId($this->id);
            $del_deal_id_arr  = array();
            foreach ($arr_deal_id_queue as $k => $deal_id) {
                if ($this->_checkDealStartStatus($deal_id) === true) {
                    $this->_startDeal($deal_id);
                    if ($this->_checkDealStartStatus($deal_id) === false) {
                    // 开标后再进行一次检查，如果继续
                        $del_deal_id_arr[] = $arr_deal_id_queue[$k];
                        unset($arr_deal_id_queue[$k]);
                    } else {
                        break;
                    }
                } else {
                    $del_deal_id_arr[] = $arr_deal_id_queue[$k];
                    unset($arr_deal_id_queue[$k]);
                }
            }
        } catch (\Exception $e) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, "ignore", $e->getMessage())));
        }

        return DealQueueInfoModel::instance()->updateDealQueue($this->id, $del_deal_id_arr, $add_deal_id_arr);
    }



    /**
     * 检查一个标的状态是否满足开标状态
     * @param int $deal_id
     * @return bool
     */
    private function _checkDealStartStatus($deal_id) {
        $deal = DealModel::instance()->findBy("`id`=':id' AND `is_delete`='0'", "*", array(":id" => $deal_id));
        if (!$deal) {
            // 标的被删除，则不满足规则
            return false;
        }
        $deal_site = DealSiteModel::instance()->getSiteByDeal($deal_id);
        if ($deal_site['site_id'] == 5) {
            // 如果是木兰贷cn，则一律自动上
            return true;
        }
        if ($deal['is_effect'] == 1 && !in_array($deal['deal_status'], array(0, 1))) {
            // 同时满足标的有效、不在等待确认状态时，不满足规则，否则反之
            return false;
        }
        return true;
    }

    /**
     * 自动上标，即让标的状态修改为进行中、有效、分站修改为主站
     * @param int $deal_id
     * @return bool
     */
    private function _startDeal($deal_id) {
        \FP::import("libs.utils.logger");

        $deal = DealModel::instance()->find($deal_id);

        if(!$deal){
            throw new \Exception( "标的信息不存在dealId:{$deal_id}");
        }

        // 预约标，不进行自动上标
        if (DealModel::$DEAL_STATUS['reserving'] == $deal->deal_status) {
            throw new \Exception( "预约投标，无需进行上标操作");
        }
        $dealService = new \core\service\DealService();
        if($deal->deal_status == DealModel::$DEAL_STATUS['waiting'] && $dealService->isNeedReportToBank($deal_id)){
            $dealReportService = new \core\service\P2pDealReportService();
            $dealReportService->dealReportRequest($deal->getRow());
        }

        $this->db->startTrans();
        try {
            // 如果是木兰贷cn，则修改为主站，其余不变
            $deal_site = DealSiteModel::instance()->getSiteByDeal($deal_id);
            if ($deal_site['site_id'] == 5) {
                $site_id = 1;

                // 根据标的ID，获取TAG列表
                $dealTagService = new DealTagService();
                $tagNameList = $dealTagService->getTagListByDealId($deal_id);
                // 掌众等启动类型1 或者 产品类别为功夫贷或者掌众50天线上 的标的，队列上标时站点改为“普通标(3个月及以上)”
                $is_zhangzhong_tag = (!empty($tagNameList) && in_array(ReservationMatchModel::TAGNAME_RESERVATION_1, $tagNameList));
                $is_zhangzhong = (DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG) == $deal['type_id']);
                $is_xjdgfd = (DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XJDGFD) == $deal['type_id']);
                $is_zzjrxs = (DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZZJRXS) == $deal['type_id']);

                $is_dfd = (DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DFD) == $deal['type_id']);
                $is_hdd = (DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_HDD) == $deal['type_id']);
                $is_grzffq = (DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_GRZFFQ) == $deal['type_id']);
                // 掌众(闪电消费) 队列上标时站点改为“普通标(3个月及以上)”
                if ($is_zhangzhong_tag || $is_xjdgfd || $is_zzjrxs || $is_dfd || $is_hdd || $is_zhangzhong|| $is_grzffq) {
                    $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST']['普通标(3个月及以上)'];
                }

                \FP::import("app.deal");
                update_deal_site($deal_id, array($site_id));
            }

            $deal->is_effect = 1;
            if ($deal->deal_status == 0) {
                $deal->deal_status = 1;
            }
            $deal->start_time = get_gmtime();
            $deal->update_time = get_gmtime();
            if ($deal->save() === false) {
                throw new \Exception("update deal error");
            }

            // 由于的运营的人比较懒，3个月以上的标的加一个TAG
            $arr_deal = $deal->getRow();
            $state_manager = new \core\service\deal\StateManager();
            $state_manager->setDeal($arr_deal);
            $state_manager->setDealParamsConfId($this->deal_params_conf_id);
            $state_manager->work();

            // 上标到线上时，检查是否符合短期标预约匹配规则并打TAG
            $reservationMatch = new ReservationMatchService();
            $reservationMatch->checkDealAndSetTag($deal_id);

            $this->db->commit();
            \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, "succ")));
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, "fail", $e->getMessage())));
            return false;
        }

    }

    /**
     * 根据队列id查询队列中标的信息
     * @param int $id
     * @param string $name
     * @param string $user_ids
     * @param array  $deal_id_arr deal_id限制集合
     * @return array
     */
    public function getDealListByQueueId($queue_id, $name=false, $user_ids=false, $deal_status=false, $deal_id_arr = array(),$reportStatus=''){
        $where_deal = empty($deal_id_arr) ? '' : sprintf(' AND `deal_id` IN (%s) ', implode(',', $deal_id_arr));
        $sql_get_deal_id = sprintf('SELECT `deal_id` FROM %s WHERE `queue_id` = %d %s ORDER BY `sort_num`, `id`', DealQueueInfoModel::instance()->tableName(), $queue_id, $where_deal);
        $sql = sprintf('SELECT d.* FROM %s AS `d` RIGHT JOIN (%s) AS `q` ON `q`.`deal_id` = `d`.`id` WHERE `d`.`id` > 0 ', DealModel::instance()->tableName(), $sql_get_deal_id);
        if ($name) {
            $sql .= " AND `d`.`name` LIKE '%{$name}%'";
        }
        if ($user_ids) {
            $sql .= sprintf(" AND `d`.`user_id` IN (%s)", $this->escape($user_ids));
        }
        if ($deal_status !== false) {
            $sql .= sprintf(" AND `d`.`deal_status`='%d'", $this->escape($deal_status));
        }
        if($reportStatus != ''){
            $sql.=sprintf(" AND `d`.`report_status`='%d'", $this->escape($reportStatus));
        }
        $deal_list = $this->findAllBySql($sql);
        return isset($deal_list) ? $deal_list : array();
    }

    /**
     * 向队列中增加标的
     * @param int $queue_id
     * @param string $deal_ids
     * @param int | boolen $jump_deal_id
     */
    public function insertDealQueue($queue_id, $deal_ids, $jump_deal_id=false) {
        $add_deal_id_arr = empty($deal_ids) ? array() : explode(",", $deal_ids);
        return DealQueueInfoModel::instance()->insertDealsIntoQueue($queue_id, $add_deal_id_arr, $jump_deal_id);
    }

    /**
     * 移动队列中标的位置
     * @param int $queue_id
     * @param int $deal_id
     * @param int $direction 1-向后 2-向前
     * @return bool
     */
    public function moveDealQueue($queue_id, $deal_id, $direction) {
        return DealQueueInfoModel::instance()->moveDeal($queue_id, $deal_id, $direction);
    }

    /**
     * 从队列中删除标的
     * @param int $queue_id
     * @param string $deal_id
     * @return bool
     */
    public function deleteDealQueue($queue_id, $deal_ids)
    {
        return DealQueueInfoModel::instance()->deleteByDealIds($queue_id, explode(",", $deal_ids));
    }

    public function deleteQueues($queue_id_arr)
    {
        try {
            if (empty($queue_id_arr)) {
                return true;
            }

            $this->db->startTrans();
            $sql = sprintf("DELETE FROM %s WHERE `id` in (%s)", $this->tableName(), $this->escape(implode(',', $queue_id_arr)));
            if (false === $this->execute($sql)) {
                throw new \Exception("deal queue error : delete deal queue fail");
            }
            if (false === DealQueueInfoModel::instance()->deleteDealsByQueueIds($queue_id_arr)) {
                throw new \Exception("deal queue info error : delete deals fail");
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $e->getMessage(), "line:" . __LINE__)));
            return false;
        }
    }

    public function deleteQueue($queue_id)
    {
        try {
            $this->db->startTrans();
            $sql = sprintf("DELETE FROM %s WHERE `id`='%d'", $this->tableName(), $this->escape($queue_id));

            if (false === $this->execute($sql)) {
                throw new \Exception("deal queue error : delete deal queue fail");
            }
            if (false === DealQueueInfoModel::instance()->deleteDealsByQueueId($queue_id)) {
                throw new \Exception("deal queue info error : delete deals fail");
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $e->getMessage(), "line:" . __LINE__)));
            return false;
        }

    }

    /**
     * 根据产品类型获取队列
     * 获取deal_queue_info的queue_id的count值最小的队列
     * @param int $type_id
     * @param int $invest_deadline 为deal表的repay_time字段
     * @param int $invest_deadline_unit 为deal表的loantype字段
     * @return false|array
     */
    public function getQueueByTypeId($type_id, $invest_deadline = 0, $invest_deadline_unit = 0)
    {
        //1.标的的投资期限单位loantype(其一些值的解释可到conf/online/dictionary.php中查看),
        //将其转换为DealQueueModel中的投资单位
        if($invest_deadline_unit <= 0){
            $invest_deadline_unit = DealQueueModel::INVEST_DEADLINE_UNIT_NULL;
        }elseif($invest_deadline_unit == 5){
            $invest_deadline_unit = DealQueueModel::INVEST_DEADLINE_UNIT_DAY;
        }else{
            $invest_deadline_unit = DealQueueModel::INVEST_DEADLINE_UNIT_MONTH;
        }
        if(empty($type_id)){
            return false;
        }
        //2.获取该产品类型的队列list
        $condition = sprintf(" `type_id`='%d'", $type_id);
        $res = $this->findAllViaSlave($condition);
        if(count($res) <= 0){
            return false;
        }
        //3.获取匹配期限的队列id
        $matchArr = array();
        foreach($res as $v){
            if(($v['invest_deadline_unit'] == $invest_deadline_unit) && ($v['invest_deadline'] == $invest_deadline)){
                $matchArr[] = $v;
            }
        }
        //4.对于匹配到的队列数量大于1的，返回标的数量最少的队列，以期达到“所有该产品类型该期限的队列里的标的数量相差不大”
        $countMatchArr = count($matchArr);
        if($countMatchArr == 1){
            return $matchArr[0];
        }elseif($countMatchArr > 1){
            //对于匹配到的队列数量大于1的，返回标的数量最少的队列
            return $this->_getMinCountQueue($matchArr);
        }else{
            //对于匹配数量为0的情况
            if($invest_deadline_unit == DealQueueModel::INVEST_DEADLINE_UNIT_NULL){
                //对于输入参数的投资期限为空，找不到匹配的队列就返回false
                return false;
            }
            //对于输入参数投资期限单位不为空，期限匹配数量为零的，再次匹配期限为空的队列
            //如过再次匹配到队列只有一个，则直接返回; 如果有两个以上，再进行平均分配
            $againMatchArr = array();
            foreach($res as $v){
                if(($v['invest_deadline_unit'] == DealQueueModel::INVEST_DEADLINE_UNIT_NULL)&& ($v['invest_deadline'] == 0)){
                    $againMatchArr[] = $v;
                }
            }
            $againCountMatchArr = count($againMatchArr);
            if($againCountMatchArr < 1){
                //对于输入参数的投资期限不为空的情况，匹配不到该投资期限的队列，并且匹配不到投资期限为0的队列
                return false;
            }elseif($againCountMatchArr == 1){
                return $againMatchArr[0];
            }else{
                //对于匹配到的队列数量大于1的，返回标的数量最少的队列
                return $this->_getMinCountQueue($againMatchArr);
            }
        }
        return false;
    }


    /**
     * 获取指定队列中count数值最小的队列
     * @param array array(DealQueueModel1,DealQueueModel2,...)
     * @return DealQueueModel
     */
    private function _getMinCountQueue($matchArr){
        //对于匹配到的队列数量大于1的，返回标的数量最少的队列
        $idArr = array();
        foreach($matchArr as $v){
            $idArr[] = $v['id'];
        }
        $results = DealQueueInfoModel::instance()->getCountsByQueueIds($idArr);
        $minArr = array();
        //获取count数值最小的队列
        foreach($results as $v){
            if(empty($minArr)){
                $minArr = $v;
            }else{
                if($minArr['count'] > $v['count']){
                    $minArr = $v;
                }
            }
        }
        //选择其中count值最小的队列返回
        foreach($matchArr as $v){
            if($v['id'] == $minArr['queue_id']){
                return $v;
            }
        }
    }


    /**
     * 根据指定标的id，上标
     * @param int $dealID
     * @return bool
     */
    public function startOneDeal($dealID)
    {
        return $this->_startDeal($dealID);
    }

    /**
     * 通过队首的标id，找到一个队列
     **/
     public function getDealQueueByFirstDealId($deal_id)
     {
        $sql = sprintf('SELECT * FROM %s WHERE `id` IN (SELECT `queue_id` FROM %s WHERE `deal_id` = %d) AND `is_effect` = 1', DealQueueModel::instance()->tableName(), DealQueueInfoModel::instance()->tableName(), $deal_id);
        $deal_queue = $this->findBySql($sql);
        return empty($deal_queue) ? array() : $deal_queue;
     }

    /**
     * 通过配置方案id，找到一个队列(不管队列是否有效)
     **/
    public function getDealQueueByParamsConfId($deal_params_conf_id, $field = '*')
    {
       $params = array(
           ":deal_params_conf_id" => $deal_params_conf_id,
       );
       return $this->findBy(" `deal_params_conf_id`=':deal_params_conf_id' ", $field, $params);
    }

    /**
     * 判断该队列是否忽略自动上标过程
     **/
    public function isIgnored()
    {
        $head_deal_id = DealQueueInfoModel::instance()->getFirstDealIdByQueueId($this->id);
        $deal = DealModel::instance()->find($head_deal_id);

        // 1.当队列首标是预约标（deal_status=6)时；
        return (isset($deal->deal_status) && DealModel::$DEAL_STATUS['reserving'] == $deal->deal_status) ? true : false;
    }

    // 获取正在运行的队列信息，包含队首标
    public function getRunningDealQueuesWithFirstDeal()
    {
        $arr_deal_queue = $this->findAll("`is_effect` = '1'");
        foreach ($arr_deal_queue as $key => $deal_queue) {
            if ($deal_queue['start_time'] > get_gmtime()) {
                unset($arr_deal_queue[$key]);
            } else {
                $deal_queue['first_deal_id'] = DealQueueInfoModel::instance()->getFirstDealIdByQueueId($deal_queue['id']);
                $arr_deal_queue[$key] = $deal_queue;
            }
        }

        return $arr_deal_queue;
    }
}
