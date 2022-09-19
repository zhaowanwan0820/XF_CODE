<?php
/**
 * 预约标的匹配服务
 *
 * @date 2016-12-16
 * @author guofeng@ucfgroup.com
 */

namespace core\service;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\ReservationMatchModel;
use core\dao\ReservationConfModel;
use core\dao\UserReservationModel;
use core\service\DealService;
use core\service\DealTagService;
use core\service\UserReservationService;
use core\service\ReservationEntraService;
use libs\utils\Logger;
use core\dao\DealTagModel;
use core\dao\TagModel;
use core\event\ReserveProcessDealEvent;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;

class ReservationMatchService extends BaseService
{
    /**
     * 预约标的日志标识-匹配
     * @var string
     */
    const LOG_IDENTIFY_MATCH = 'RESERVATION_MATCH';

    /**
     * 预约标签
     * @var array
     */
    private static $reserveTagList;

    /**
     * 根据所传参数，获取有效的预约匹配列表
     * @return \libs\db\model
     */
    public function getReserveMatchListByTypeId($typeId = 0, $isEffect = -1, $page = 0, $pageSize = 0, $sortBy = '`id` ASC', $entraId = 0)
    {
        return ReservationMatchModel::instance()->getReserveMatchList($typeId, $isEffect, $page, $pageSize, $sortBy, $entraId);
    }

    /**
     * 获取预约匹配记录
     */
    public function getReserveMatch($typeId, $entraId, $isEffect = -1){
        return ReservationMatchModel::instance()->getReserveMatch($typeId, $entraId, $isEffect);
    }

    /**
     * 创建预约匹配记录
     * @param array $params 参数数组
     */
    public function createReserveMatch($params) {
        // 检查参数等处理
        $params = $this->_checkParams($params);
        if (isset($params['respCode'])) {
            return $params;
        }
        return ReservationMatchModel::instance()->createReserveMatch($params['reserveType'], $params['typeId'], $params['investConf'], $params['advisoryId'], $params['projectIdsArray'], $params['isEffect'], $params['remark'], $params['tagName'], '', 0, $params['entraId']);
    }

    /**
     * 更新预约匹配记录
     * @param array $params 参数数组
     */
    public function updateReserveMatch($params) {
        // 检查参数等处理
        $params = $this->_checkParams($params);
        if (isset($params['respCode'])) {
            return $params;
        }
        return ReservationMatchModel::instance()->updateReserveMatchById($params['id'], $params['reserveType'], $params['typeId'], $params['investConf'], $params['advisoryId'], $params['projectIdsArray'], $params['isEffect'], $params['remark'], $params['tagName'], '', 0, $params['entraId']);
    }

    /**
     * 给指定的项目ID打TAG
     * @param string $projectIds 项目ID
     */
    public function setTagForProjectIds($projectIds, $tagName, $isUpdateStatus = false) {
        if (empty($projectIds)) return false;
        if (is_string($projectIds)) {
            $projectIdsTmp = explode(',', trim($projectIds));
            $projectIds = array_filter(array_map('intval', $projectIdsTmp), 'strlen');
        }
        $dealService = new DealService();
        foreach ($projectIds as $projectId) {
            // 根据项目ID获取标的列表，只有标的状态为[进行中]才会打TAG
            $dealList = $dealService->getDealByProId($projectId, DealModel::$DEAL_STATUS['progressing']);
            if (!empty($dealList)) {
                foreach ($dealList as $item) {
                    // 给指定标的打TAG
                    $this->setTagForDeal($item['id'], array('tag_name'=>$tagName));
                }
            }
        }
    }

    /**
     * 检查标的是否符合匹配规则并打TAG
     * @param int $dealId 标的ID
     * @param boolean $isUpdateStatus 是否更新标的状态
     * @param boolean $isInitDeal 是否初始化标的调用打Tag
     * @param boolean $isReserveProcessDeal 是否预约匹配标的
     */
    public function checkDealAndSetTag($dealId, $isUpdateStatus = false, $isInitDeal = false, $isReserveProcessDeal = false) {
        try {
            // 检查标的是否符合匹配规则
            $dealMatchRet = $this->checkDealIsMatch($dealId);
            if (empty($dealMatchRet) || true !== $dealMatchRet['ret']) {
                throw new \Exception($dealMatchRet['msg'], 1);
            }

            //开启事务
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();

            // 给指定的标的打TAG
            $tagRet = $this->setTagForDeal($dealId, $dealMatchRet['data'], $isInitDeal);
            if (true !== $tagRet) {
                throw new \Exception('set tag for deal is failed or deal tag is exist', 2);
            }

            // 按预约标的规则，更新标的信息
            if ($isUpdateStatus) {
                $updateDealRet = $this->updateDealInfoForReservation($dealId, $dealMatchRet['data']);
                if (true !== $updateDealRet) {
                    throw new \Exception('update dealinfo is failed', 3);
                }
            }

            //匹配预约
            if ($isReserveProcessDeal) {
                $ret = $this->reserveProcessDeal($dealId, $dealMatchRet['data']);
                if (true !== $ret) {
                    throw new \Exception('match reservation failed', 4);
                }
            }

            $db->commit();
            return array('respCode'=>0, 'respMsg'=>'SUCCESS');
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('check_deal_and_setTag_is_exception, dealId:%d, respCode:%s, respMsg:%s', $dealId, $e->getCode(), $e->getMessage()))));
            return array('respCode'=>$e->getCode(), 'respMsg'=>$e->getMessage());//Exception 返回整型（integer）的异常代码
        }
    }

    /**
     * 检查标的是否符合匹配规则
     * @param int $dealId
     */
    public function checkDealIsMatch($dealId) {
        $matchData = array('ret'=>false, 'msg'=>'');
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('check deal is match start, dealId:%d', $dealId))));
        $deal = DealModel::instance()->find($dealId);
        if (empty($deal) || empty($deal['type_id'])) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('deal is not exist or type_id is empty, dealId:%d', $dealId))));
            $matchData['msg'] = 'deal is not exist or type_id is empty';
            return $matchData;
        }
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealId, false);

        // 标的数据
        $matchData['deal'] = $deal;
        $matchData['dealExt'] = $dealExt;
        // 预约到的匹配配置
        $matchData['data'] = array();
        $entraService = new ReservationEntraService();
        $userReservationService = new UserReservationService();

        //查询预约入口
        $investDeadlineArray = $userReservationService->getInvestDeadlineByDeal($deal);
        $deadline = isset($investDeadlineArray['invest_deadline']) ? $investDeadlineArray['invest_deadline'] : 0;
        $deadlineUnit = isset($investDeadlineArray['invest_deadline_unit']) ? $investDeadlineArray['invest_deadline_unit'] : 0;
        $entra = $entraService->getReserveEntra($deadline, $deadlineUnit, $deal['deal_type'], $dealExt['income_base_rate'], $deal['loantype'], -1);
        if (empty($entra)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('entra is not exist, dealId:%d', $dealId))));
            $matchData['msg'] = 'entra is not exist';
            return $matchData;
        }

        // 根据产品类别，获取有效的预约匹配列表
        $reserveMatch = $this->getReserveMatch($deal['type_id'], $entra['id'], ReservationMatchModel::IS_EFFECT_VALID);
        if (empty($reserveMatch)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('matchData is not exist, dealId:%d', $dealId))));
            $matchData['msg'] = 'matchData is not exist';
            return $matchData;
        }

        $matchData['ret'] = true;
        $matchData['data'] = $reserveMatch;
        $matchData['msg'] = 'deal is match success';
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('check deal is match end, dealId:%d, msg:%s', $dealId, $matchData['msg']))));
        return $matchData;
    }

    /**
     * 给标的设置TAG
     * @param int $dealId 标的ID
     * @param array $matchData 匹配规则数据
     * @param boolean $isUpdateStatus 是否更新标的状态
     * @param boolean $isInitDeal 是否初始化标的调用打Tag
     * @throws \Exception
     * @return boolean
     */
    public function setTagForDeal($dealId, $matchData, $isInitDeal = false) {
        if ($dealId <= 0 || empty($matchData) || empty($matchData['tag_name'])) {
            return false;
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('set_tag_for_deal_is_start, dealId:%d, tagName:%s', $dealId, $matchData['tag_name']))));
        try {
            // 检查预约标签是否已存在
            $dealTagService = new DealTagService();
            $tagNameList = $dealTagService->getTagListByDealId($dealId);
            if (!empty($tagNameList) && array_intersect(ReservationMatchModel::$tagNameList, $tagNameList)) {
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('set_tag_for_deal_has_exist, dealId:%d, tagName:%s', $dealId, json_encode(array_intersect(ReservationMatchModel::$tagNameList, $tagNameList))))));
                return true;
            }
            // 自动上标接口上标的时候，只打[优先预约]Tag的标的
            if (true === $isInitDeal && $matchData['reserve_type'] != ReservationMatchModel::RESERVE_TYPE_DEFAULT_RESERVING) {
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('set_tag_for_deal_is_not_tag_1, dealId:%d, tagName:%s, reserveType:%d', $dealId, $matchData['tag_name'], $matchData['reserve_type']))));
                return true;
            }
            // 添加预约标签
            if (!$dealTagService->insert($dealId, $matchData['tag_name'])) {
                throw new \Exception(sprintf('insert deal tag failed, dealId:%d, tagName:%s', $dealId, $matchData['tag_name']));
            }
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('set_tag_for_deal_is_success, dealId:%d, tagName:%s', $dealId, $matchData['tag_name']))));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('set_tag_for_deal_is_exception, dealId:%d, tagName:%s, exceptionMsg:%s', $dealId, $matchData['tag_name'], $e->getMessage()))));
            return false;
        }
    }

    /**
     * 按预约标的规则，更新标的信息
     * @param int $dealId
     * @param array $matchData
     * @param array $deal
     */
    public function updateDealInfoForReservation($dealId, $matchData, $deal = array()) {
        if ($dealId <= 0 || empty($matchData) || empty($matchData['reserve_type'])) {
            return false;
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('update_dealinfo_for_reservation_is_start, dealId:%d, reserveType:%d', $dealId, $matchData['reserve_type']))));
        $updateRet = false;
        empty($deal) && $deal = DealModel::instance()->findViaSlave($dealId);
        switch ($matchData['reserve_type']) {
            case ReservationMatchModel::RESERVE_TYPE_DEFAULT_RESERVING: // 优先预约投资
                // 只给预约服务启动类型为[优先预约投资]的标的，进行报备
                $dealService = new DealService();
                if($dealService->isNeedReportToBank($dealId)) {
                    Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('deal_report_request_start, dealId:%d, reserveType:%d', $dealId, $matchData['reserve_type']))));
                    $deal = DealModel::instance()->find($dealId);
                    $reportService = new \core\service\P2pDealReportService();
                    $reportService->dealReportRequest($deal->getRow());
                }

                // 更新标的状态，标的状态为[等待确认]、[预约中]，预约服务启动类型为[优先预约投资]
                if ($deal['deal_status'] == DealModel::$DEAL_STATUS['waiting']) {
                    try {
                        $deal['deal_status'] = DealModel::$DEAL_STATUS['reserving'];
                        $deal['is_effect'] = 1;
                        $deal['start_time'] = get_gmtime(); //更新上线时间
                        $deal['update_time'] = get_gmtime();
                        //专享交易所修改起投额为1000元，智能投资
                        if (in_array($deal['deal_type'], [DealModel::DEAL_TYPE_EXCLUSIVE, DealModel::DEAL_TYPE_EXCHANGE])) {
                            $deal['is_float_min_loan'] = DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_NO; //关闭浮动起投
                            $deal['min_loan_money'] = DealModel::DEAL_MIN_LOAN_UNIT;
                        }
                        if (!$deal->save()) {
                            throw new \Exception(sprintf('update_deal_status_failed, dealId:%d', $dealId));
                        }

                        //更新站点为 mulandaicn
                        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['mulandaicn'];
                        \FP::import("app.deal");
                        update_deal_site($dealId, array($siteId));

                        $updateRet = true;
                        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('update_dealinfo_for_reservation_is_success, dealId:%d, reserveType:%d', $dealId, $matchData['reserve_type']))));
                    } catch (\Exception $e) {
                        Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_MATCH, sprintf('update_dealinfo_for_reservation_is_exception, dealId:%d, reserveType:%d, exceptionMsg:%s', $dealId, $matchData['reserve_type'], $e->getMessage()))));
                    }
                }
                break;
            default:
                $updateRet = true;
                break;
        }
        return $updateRet;
    }

    /**
     * 根据标的ID，检查标的是否属于预约标
     * @param int $dealId
     */
    public function isReservationDeal($dealId) {
        $dealTagService = new DealTagService();
        $tagNameList = $dealTagService->getTagListByDealId($dealId);
        if (empty($tagNameList)) return false;
        foreach ($tagNameList as $tagName) {
            if (!empty($tagName) && in_array($tagName, array(ReservationMatchModel::TAGNAME_RESERVATION_1, ReservationMatchModel::TAGNAME_RESERVATION_2))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据预约的TAG名称数组，批量获取TAGID
     */
    public function getTagIdsByReserveTag() {
        $tagIds = TagModel::instance()->getTagIdsByNameList(array(ReservationMatchModel::TAGNAME_RESERVATION_1, ReservationMatchModel::TAGNAME_RESERVATION_2));
        return empty($tagIds['tagIds']) ? array() : $tagIds['tagIds'];
    }

    /**
     * 根据预约的TAG名称数组，批量获取TAGLIST
     */
    public function getTagListByReserveTag() {
        if (isset(self::$reserveTagList)) {
            return self::$reserveTagList;
        }
        $tagIds = TagModel::instance()->getTagIdsByNameList(array(ReservationMatchModel::TAGNAME_RESERVATION_1, ReservationMatchModel::TAGNAME_RESERVATION_2));
        self::$reserveTagList = empty($tagIds['tagList']) ? array() : $tagIds['tagList'];
        return self::$reserveTagList;
    }

    /**
     * 检查参数等处理
     * @param array $params
     */
    private function _checkParams($params) {
        if (empty($params['reserveType']) || empty(ReservationMatchModel::$reserveTypeConfig[$params['reserveType']])) {
            return array('respCode'=>'01', 'respMsg'=>'预约类型启动服务不能为空');
        }
        if (empty($params['typeId'])) {
            return array('respCode'=>'02', 'respMsg'=>'产品类别不能为空');
        }
        if (empty($params['entraId'])) {
            return array('respCode'=>'03', 'respMsg'=>'预约入口不能为空');
        }

        // 配置ID
        $id = !empty($params['id']) ? intval($params['id']) : 0;
        $ret = $this->checkUniqueMatch($params['typeId'], $params['entraId'], $id);
        if (!$ret) {
            return array('respCode'=>'05', 'respMsg'=>'配置已经存在，请检查');
        }
        return $params;
    }

    /**
     * 检查唯一匹配
     */
    private function checkUniqueMatch($typeId, $entraId, $id= 0) {
        $matchData = ReservationMatchModel::instance()->getReserveMatch($typeId, $entraId);
        return !empty($matchData) && $matchData['id'] != $id ? false : true;
    }

    /**
     * 匹配预约
     */
    public function reserveProcessDeal($dealId, $matchData) {
        //上标触发匹配开关
        if((int)app_conf('RESERVE_SCRIPT_PROCESS_DEAL_SWITCH') === 0) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "上标触发预约匹配开关已关闭")));
            return true;
        }

        //网贷标的上标不触发匹配
        $deal = DealModel::instance()->find($dealId);
        if ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "网贷标的上标不触发匹配，需先批量发送信息披露")));
            return true;
        }

        //优先预约投资标的，上标即匹配预约
        if ($matchData['reserve_type'] == ReservationMatchModel::RESERVE_TYPE_DEFAULT_RESERVING) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "上标触发预约匹配, dealId: " . $dealId)));
            $userReservationService = new UserReservationService();
            $userReservationService->pushDeal($dealId);
        }
        return true;
    }
}
