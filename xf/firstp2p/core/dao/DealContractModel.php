<?php
/**
 * DealContractModel class file.
 *
 * @author wangyiming@ucfgroup.com
 **/

namespace core\dao;

use core\service\DealAgencyService;
use core\service\DealService;
use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\service\DealLoanTypeService;
use NCFGroup\Protos\Contract\RequestGetTplsByDealId;
use libs\utils\Logger;
use libs\utils\Rpc;

use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;

/**
 * 合同签署记录表，以标的为维度，记录借款人和担保公司签署记录
 *
 * @author wangyiming@ucfgroup.com
 **/
class DealContractModel extends BaseModel {
    /**
     * 满标时生成两条未签署记录 如果合同为新的签署规则（4*n+2） contract_version = 2
     * @param array $deal
     * @return bool
     */
    public function create($deal) {
        if (!$deal || !$deal['id']) {
            return false;
        }

        $list = $this->findAll("`deal_id`='{$deal['id']}'");
        if ($list) {
            return false;
        }

        $log_arr = array(
            "deal_id" => $deal['id'],
        );

        $this->db->startTrans();

        try {
            // 借款人记录
            $this->user_id = $deal['user_id'];
            $this->deal_id = $deal['id'];
            $this->create_time = time();
            $this->contract_tpl_type = $deal['contract_tpl_type'];
            $this->agency_id = 0;
            $this->status = 0;
            $this->sign_time = 0;

            if ($this->insert() === false) {
                throw new \Exception("insert borrow_user contract fail");
            }

            // 担保机构记录
            $this->user_id = 0;
            $this->deal_id = $deal['id'];
            $this->create_time = time();
            $this->contract_tpl_type = $deal['contract_tpl_type'];
            $this->agency_id = $deal['agency_id'];
            $this->status = 0;
            $this->sign_time = 0;

            if ($this->insert() === false) {
                throw new \Exception("insert agency_user contract fail");
            }
            //资产管理机构记录
            if(isset($deal['contract_version'])&&($deal['contract_version'] === 2)){
                $this->user_id = 0;
                $this->deal_id = $deal['id'];
                $this->create_time = time();
                $this->contract_tpl_type = $deal['contract_tpl_type'];
                $this->agency_id = $deal['advisory_id'];
                $this->status = 0;
                $this->sign_time = 0;
                if ($this->insert() === false) {
                    throw new \Exception("insert advisory_user contract fail");
                }
            }

            $this->db->commit();
            Logger::wlog("create_deal_contract:" . json_encode($log_arr), Logger::INFO);
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $log_arr['msg'] = $e->getMessage();
            $content = "create_deal_contract:" . json_encode($log_arr);
            Logger::wlog($content, Logger::ERR);
            \libs\utils\Alarm::push('deal', '生成合同异常', $content);
            return false;
        }

    }

    /**
     * 合同服务满标时生成未签署记录
     * @param array $deal
     * @return bool
     */
    public function createNew($deal) {
        if (!$deal || !$deal['id']) {
            return false;
        }
        $list = $this->findAll("`deal_id`='{$deal['id']}'");
        if ($list) {
            return false;
        }

        $log_arr = array(
            "deal_id" => $deal['id'],
        );

        $this->db->startTrans();

        try {
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestGetTplsByDealId();
            $contractRequest->setDealId(intval($deal['id']));
            $contractRequest->setType(0);
            $contractRequest->setSourceType($deal['deal_type']);
            $response = $rpc->go("\NCFGroup\Contract\Services\Tpl", "getTplsByDealId", $contractRequest);
            if ($response && ($response->errorCode == 0)) {
                $this->deal_id = $deal['id'];
                $this->create_time = time();
                $this->contract_tpl_type = $deal['contract_tpl_type'];
                $this->deal_type = $deal['deal_type'];
                $this->status = 0;
                $this->sign_time = 0;
                $insert_sign = 0;
                $deal_service = new DealService();

                $project = DealProjectModel::instance()->find($deal['project_id']);

                foreach ($response->list['data'] as $template) {
                    // 不记录项目合同信息
                    if (ContractTplIdentifierEnum::SERVICE_TYPE_PROJECT == $template['tpl_identifier_info']['serviceType']) {
                        continue;
                    }

                    // 借款人
                    if ($this->isFirstInsert($insert_sign, ContractTplIdentifierEnum::SIGN_ROLE_BORROWER) && isSignRole($template['tpl_identifier_info']['signRole'], ContractTplIdentifierEnum::SIGN_ROLE_BORROWER)) {
                        $insert_sign |= ContractTplIdentifierEnum::SIGN_ROLE_BORROWER;
                        // 借款人记录
                        $this->user_id = $deal['user_id'];
                        $this->agency_id = 0;
                        //借款人代签标记
                        if($project['entrust_sign']){
                            $this->status = 1;
                            $this->sign_time = time();
                        }else{
                            $this->status = 0;
                            $this->sign_time = 0;
                        }

                        if ($this->insert() === false) {
                            throw new \Exception("insert borrow_user contract fail");
                        }
                    }

                    // 担保机构
                    if ($this->isFirstInsert($insert_sign, ContractTplIdentifierEnum::SIGN_ROLE_AGENCY) && $deal['agency_id'] > 0 && isSignRole($template['tpl_identifier_info']['signRole'], ContractTplIdentifierEnum::SIGN_ROLE_AGENCY)) {
                        $insert_sign |= ContractTplIdentifierEnum::SIGN_ROLE_AGENCY;
                        $this->user_id = 0;
                        $this->agency_id = $deal['agency_id'];
                        //担保机构代签标记
                        if($project['entrust_agency_sign']){
                            $this->status = 1;
                            $this->sign_time = time();
                        }else{
                            $this->status = 0;
                            $this->sign_time = 0;
                        }

                        if ($this->insert() === false) {
                            throw new \Exception("insert agency_user contract fail");
                        }
                    }

                    // 咨询机构
                    if ($this->isFirstInsert($insert_sign, ContractTplIdentifierEnum::SIGN_ROLE_ADVISORY) && $deal['advisory_id'] > 0 && isSignRole($template['tpl_identifier_info']['signRole'], ContractTplIdentifierEnum::SIGN_ROLE_ADVISORY)) {
                        $insert_sign |= ContractTplIdentifierEnum::SIGN_ROLE_ADVISORY;
                        $this->user_id = 0;
                        $this->agency_id = $deal['advisory_id'];
                        //资产端代签标记
                        if($project['entrust_advisory_sign']){
                            $this->status = 1;
                            $this->sign_time = time();
                        }else{
                            $this->status = 0;
                            $this->sign_time = 0;
                        }

                        if ($this->insert() === false) {
                            throw new \Exception("insert advisory_user contract fail");
                        }
                    }

                    // 受托机构
                    if ($this->isFirstInsert($insert_sign, ContractTplIdentifierEnum::SIGN_ROLE_ENTRUST_AGENCY) && $deal['entrust_agency_id'] > 0 && isSignRole($template['tpl_identifier_info']['signRole'], ContractTplIdentifierEnum::SIGN_ROLE_ENTRUST_AGENCY) && $deal_service->isDealEx($deal['deal_type'])) {
                        $insert_sign |= ContractTplIdentifierEnum::SIGN_ROLE_ENTRUST_AGENCY;
                        $this->user_id = 0;
                        $this->status = 0;
                        $this->sign_time = 0;
                        $this->agency_id = $deal['entrust_agency_id'];
                        if ($this->insert() === false) {
                            throw new \Exception("insert entrust agency contract fail");
                        }
                    }

                    // 渠道机构
                    if ($this->isFirstInsert($insert_sign, ContractTplIdentifierEnum::SIGN_ROLE_CANAL) && $deal['canal_agency_id'] > 0 && isSignRole($template['tpl_identifier_info']['signRole'], ContractTplIdentifierEnum::SIGN_ROLE_CANAL)) {
                        $insert_sign |= ContractTplIdentifierEnum::SIGN_ROLE_CANAL;
                        $this->user_id = 0;
                        $this->status = 0;
                        $this->sign_time = 0;
                        $this->agency_id = $deal['canal_agency_id'];
                        if ($this->insert() === false) {
                            throw new \Exception("insert entrust agency contract fail");
                        }
                    }
                }
            }else{
                Logger::wlog("create_deal_contract:contract service error", Logger::ERR);
            }

            $this->db->commit();
            Logger::wlog("create_deal_contract:" . json_encode($log_arr), Logger::INFO);
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            $log_arr['msg'] = $e->getMessage();
            $content = "create_deal_contract:" . json_encode($log_arr);
            Logger::wlog($content, Logger::ERR);
            \libs\utils\Alarm::push('deal', '生成合同异常', $content);
            return false;
        }

    }

    /**
     * 判断是否是首条插入
     */
    private function isFirstInsert($insert_sign, $sign)
    {
        return !($insert_sign & $sign);
    }

    /**
     * 开始签署，将表数据更新为签署中
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     * @param $admID 后台代理借款人签署合同的id
     * @return bool
     */
    public function startSign($deal_id, $user_id, $is_agency=0,$agency_id = 0, $admID = 0) {
        if ($is_agency) {
            $where = sprintf("`agency_id` = '%d' AND `user_id` = '0'",$agency_id);
        } else {
            $where = sprintf("`agency_id` = '0' AND `user_id` = '%d'", $user_id);
        }
        $condition = sprintf("`deal_id` = '%d' AND `sign_time` = '' AND `status` = '0' AND %s", $deal_id, $where);
        $row = $this->findBy($condition);
        if(!empty($row)){
            $data = array(
                'status' => 2,
                'sign_time' => time(),
                'update_time' => time(),
                'adm_id' => $admID,
            );

            return $row->updateOne($data);
        }

        return true;


    }

    /**
     * 签署，将表数据更新为签署中或已签署
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     * @param int $admID [用于借款人代签，代签人后台账户id]
     * @param int $status 1:已签署,2:签署中
     * @return bool
     */
    public function signByRole($deal_id, $user_id, $is_agency=0,$agency_id = 0,$all=false, $admID = 0, $status = 1) {
        if($all){
            $where = "1 = 1";
        }else{
            if ($is_agency) {
                $where = sprintf("`agency_id` = '%d' AND `user_id` = '0'",$agency_id);
            } else {
                $where = sprintf("`agency_id` = '0' AND `user_id` = '%d'", $user_id);
            }
        }
        $condition = sprintf("`deal_id` = '%d' AND `status` in (0,2) AND %s", $deal_id, $where);
        if($all){
            $rows = $this->findAll($condition);
            foreach($rows as $row){
                if($row['status'] <> 1){
                    $data = array(
                        'status' => $status,
                        'sign_time' => time(),
                        'update_time' => time(),
                        'adm_id' => $admID,
                    );
                    if(!$row->updateOne($data)){
                        return false;
                    }
                }
            }
            return true;
        }else{
            $row = $this->findBy($condition);

            if($row){
                if($row['status'] <> 1){
                    $data = array(
                        'status' => $status,
                        'sign_time' => time(),
                        'update_time' => time(),
                        'adm_id' => $admID,
                    );
                    return $row->updateOne($data);
                }
            }else{
                return true;
            }
        }
    }

    /**
     * 完成签署，将表数据更新为签署完成
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     * @return bool
     */
    public function endSign($deal_id, $user_id, $is_agency=0, $agency_id = 0) {
        if ($is_agency) {
            if($agency_id == 0){
                $where = "`agency_id`> 0 AND `user_id` = '0'";
            }else{
                $where = sprintf("`agency_id` =  '%d' AND `user_id` = '0'", $agency_id);
            }

        } else {
            $where = sprintf("`agency_id` = '0' AND `user_id` = '%d'", $user_id);
        }
        $condition = sprintf("`deal_id` = '%d' AND %s", $deal_id, $where);
        if($is_agency && ($agency_id == 0)){
            $rows = $this->findAll($condition);
            foreach($rows as $row){
                $data = array(
                    'status' => 1,
                    'sign_time' => time(),
                    'update_time' => time(),
                );
                if(!$row->updateOne($data)){
                    return false;
                }
            }
            return true;

        }else{
            $row = $this->findBy($condition);
            $data = array(
                'status' => 1,
                'sign_time' => time(),
                'update_time' => time(),
            );
            return $row->updateOne($data);
        }
    }

    /**
     * 获取一个标某用户的签署信息
     * @param $deal_id 标的id
     * @param $own_id user_id 或 agency_id
     * @param $is_agency 是否担保公司
     * @return array
     */
    public function getDealContByUser($deal_id, $user_id, $is_agency=0, $agency_id = 0) {
        if ($is_agency) {
            $where = sprintf("`agency_id` = '%d' AND `user_id` = '0'", $agency_id);;
        } else {
            $where = sprintf("`agency_id` = '0' AND `user_id` = '%d'", $user_id);
        }
        $condition = sprintf("`deal_id` = '%d' AND %s", $deal_id, $where);
        return $this->findBy($condition);
    }

    /**
     * 获取借款人合同列表
     * @param int $user_id
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getBorrowUserContDeals($user_id, $page, $page_size) {
        $page_size = $page_size > 0 ? $page_size : app_conf("PAGE_SIZE");
        $start = ($page-1) * $page_size;
        $params = array(
            ':user_id' => $user_id,
            ':start' => $start,
            ':page_size' => $page_size,
        );
        $condition_cnt = "`user_id` = ':user_id'";
        $condition = $condition_cnt . " ORDER BY `status`, `sign_time` DESC, `create_time` DESC LIMIT :start, :page_size";

        $count = $this->countViaSlave($condition_cnt, $params);
        $list = $this->findAll($condition, true, '*', $params);

        if ($list) {
            $deal_service = new DealService();
            foreach ($list as $k => $v) {
                $deal = $deal_service->getDeal($v['deal_id'], true);
                $list[$k] = $deal;
                $list[$k]['sign_status'] = $v['status'];
            }
        }

        return array('count' => $count, 'list' => $list);
    }

    /**
     * 获取担保合同列表
     * @param int $user_id
     * @param array $agency_info
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getAgencyUserContDeals($user_id, $agency_info, $page, $page_size) {
        $page_size = $page_size > 0 ? $page_size : app_conf("PAGE_SIZE");
        $params = array(
            ':agency_id' => intval($agency_info['agency_id']),
            ':start' => ($page-1) * $page_size,
            ':page_size' => $page_size,
        );
        $condition = "`agency_id` = ':agency_id'";

        if ($agency_info['is_hy']) {
            $condition .= " AND `contract_tpl_type` = 'HY'";
        } else {
            $condition .= " AND `contract_tpl_type` != 'HY'";
        }

        $count = $this->countViaSlave($condition, $params);
        $condition .= "ORDER BY `status`, `sign_time` DESC, `create_time` DESC LIMIT :start, :page_size";
        $list = $this->findAll($condition, true, "*", $params);

        if ($list) {
            $deal_service = new DealService();
            foreach ($list as $k => $v) {
                $deal = $deal_service->getDeal($v['deal_id'], true);
                $list[$k] = $deal;
                $list[$k]['sign_status'] = $v['status'];
            }
        }

        return array('count' => $count, 'list' => $list);
    }

    /**
     * 删除一个标的签署记录
     * @param unknown $deal_id
     * @return boolean
     */
    public function delDealContByDealId($deal_id){
        $sql = sprintf("DELETE FROM %s WHERE `deal_id` = %d", self::tableName(), $this->escape($deal_id));
        $del_contract = $this->execute($sql);
        if(!$del_contract){
            return false;
        }
        return true;
    }

    /**
     * 根据条件获取未签署的合同
     * @param string $condition
     * @return array
     */
    public function getUnSignedContractByCondition($condition){
        $sql = "select dc.deal_id,dc.user_id,dc.agency_id from firstp2p_deal_contract dc LEFT JOIN firstp2p_deal d on  dc.deal_id = d.id LEFT JOIN firstp2p_deal_project dp on d.project_id = dp.id where d.deal_status = 2 and dc.status = 0 ";
        if(!empty($condition)){
            $sql .= ' and '.$condition;
        }
        return $this->findAllBySqlViaSlave($sql,true);
    }


    /**
     * 获取标的deal contract是否全部签署完毕
     * @param string $condition
     * @return boolean
     *
     */

    public function getDealContractSignInfo($dealId,$status = null){
        $condition = "`deal_id` = ':dealId'";
        $params = array(
            ':dealId' => intval($dealId),
        );
        if($status !== null){
            $condition.= " AND status = ':status' ";
            $params[':status'] = intval($status);
        }

        $count = $this->countViaSlave($condition, $params);
        return $count;
    }


    /**
     * 检测标的deal contract是否未签署
     * @param string $condition
     * @return boolean
     *
     */

    public function getDealContractUnSignInfo($dealId){
        $result = $this->findAll("`deal_id` = ".intval($dealId),true);
        if(count($result) == 0){
            return false;
        }else{
            foreach($result as $contract){
                if($contract['status'] <> 1){
                    return false;
                }
            }
        }
        //如果count 为0 则记录未生成或还有未签署的记录
        return true;
    }

    /**
     * 获取预约投资签署合同的用户id
     */
    public function getSignUserIdsForReserve($startTime, $endTime)
    {
        $sql = sprintf("SELECT dl.user_id FROM `firstp2p_deal_contract` AS dc 
                LEFT JOIN `firstp2p_deal_load` AS dl ON dc.deal_id = dl.deal_id 
                INNER JOIN `firstp2p_reservation_deal_load` AS rdl ON dl.id = rdl.load_id 
                WHERE dc.sign_time >= %s AND dc.sign_time <= %s AND dc.status = 1 GROUP BY dl.user_id", $startTime, $endTime);
        $result = $this->findAllBySqlViaSlave($sql, true);
        return $result;
    }
} // END class ContractModel extends BaseModel
