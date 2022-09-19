<?php
/**
 * ContractModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\DealContractModel;
use core\dao\AgencyUserModel;
use core\dao\AgencyContractModel;
use core\dao\ContractContentModel;
use core\dao\darkmoon\DarkmoonDealModel;

use core\service\DealService;
use core\service\GoldService;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Protos\Contract\RequestGetContractByLoadId;
use NCFGroup\Protos\Contract\RequestSignTsaCallback;
use NCFGroup\Protos\Contract\RequestGetContractAttachmentByDealId;
use NCFGroup\Protos\Contract\RequestGetCategoriesLikeTypeTag;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

use libs\utils\Logger;
use libs\utils\Rpc;

/**
 * 合同操作类
 *
 * @author wenyanlei@ucfgroup.com
 **/
class ContractModel extends BaseModel {
    // 附件合同标记
    static $tpl_type_tag_attachment = array(
        1   =>  'ATTACHMENT_GR',
        2   =>  'ATTACHMENT_QY',
    );
    /**
     * 根据投标ID获取合同信息
     *
     * @param $load_id
     * @param bool $deal 可空
     * @param bool $deal_load 可空
     * @param array $contract_type type数组
     * @return bool|\libs\db\Model
     */
    public function getContractByDealLoad($load_id, $deal = false, $deal_load = false, $contract_type = array(1, 4, 5, 7,20, 21, 22, 23, 99),$user_id = 0) {
        if (empty($load_id) || empty($contract_type)) {
            return false;
        }

        if (empty($deal) || empty($deal_load)) {
            return array();
        }

        /*
         * 合同服务双读逻辑
         */

        //先在合同服务中读取
        $rpc = new Rpc('contractRpc');
        $contractRequest = new RequestGetContractByLoadId();
        $contractRequest->setDealId(intval($deal['id']));
        $contractRequest->setLoadId(intval($load_id));
        $contractRequest->setUserId(intval($user_id));
        $contractRequest->setSourceType(intval($deal['deal_type']));
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByLoadId",$contractRequest);
        if($response->errorCode == 0){
            $cont_list = $response->list;
        }

        //如果没有读到，再去P2P读取
        if(empty($cont_list)){
            $cont_list = $this->getContListByLoadid($load_id, $deal_load['user_id'], $contract_type);
            if(empty($cont_list)){
                foreach ($contract_type as $type) {
                    $contract_number[] = "'" . $this->escape(self::genContractNumber($deal['id'], $deal['parent_id'], $deal_load['user_id'], $load_id, $type)) . "'";
                }
                $contract_number_str = implode(',', $contract_number);
                $sql_contract = sprintf("SELECT `id`,`title`,`number` FROM %s WHERE `user_id`='%d' AND `number` IN(%s)", $this->tableName(), $this->escape($deal_load['user_id']), $contract_number_str);
                $cont_list = $this->findAllBySql($sql_contract, false, array(), true);
            }
        }

        return $cont_list;
    }

    /**
     * 获取合同附件
     * @author <fanjingwen@ucfgroup.com>
     * @param  array $deal 标的信息[对应表字段]
     * @return array       [description]
     */
    public function getContractAttachmentByDealLoad($deal,$type = 0)
    {
        if (empty($deal)) {
            return array();
        }

        $requst_attachment = new RequestGetContractAttachmentByDealId();
        if(!empty($type)){
            $requst_attachment->setSourceType(100);
        }
        $requst_attachment->setDealId(intval($deal['id']));
        $rpc = new Rpc('contractRpc');
        $response_attachment = $rpc->go("\NCFGroup\Contract\Services\Tpl","getContractAttachmentByDealId",$requst_attachment);
        $cont_list = array();
        if ($response_attachment->getStatus()) {
            $cont_json = $response_attachment->getJsonData();
            $cont_list = json_decode($cont_json, TRUE);
        }

        return $cont_list;
    }

    /**
     * 根据投资id获取合同列表
     * @param unknown $load_id
     * @param unknown $type
     * @return array
     */
    public function getContListByLoadid($load_id, $load_user_id, $type = array()){
        $condition = "`user_id` = ':user_id' AND `deal_load_id` = ':load_id'";
        if(!empty($type)){
            $condition .= sprintf(" AND `type` IN (%s)", implode(',', $type));
        }
        $params = array(
            ':user_id' => intval($load_user_id),
            ':load_id' => intval($load_id),
        );
        return $this->findAllViaSlave($condition, false, '`id`,`title`,`number`', $params);
    }

    /**
     * 生成合同编号
     */
    public static function genContractNumber($deal_id, $deal_parent_id, $user_id, $load_id, $type = NULL) {
        $load_id = str_replace(",", "", $load_id);
        //判断子母单和普通单的情况
        if ($deal_parent_id == -1) {
            return str_pad($deal_id,6,"0",STR_PAD_LEFT).'01'.str_pad($type,2,"0",STR_PAD_LEFT).str_pad($user_id,8,"0",STR_PAD_LEFT).str_pad($load_id,10,"0",STR_PAD_LEFT);
        } elseif ($deal_parent_id > 0) {
            return str_pad($deal_id . '02' . $deal_parent_id . $type . $user_id . $load_id, 16, "0", STR_PAD_LEFT);
        } else {
            return str_pad($deal_id . '03' . $type . $user_id . $load_id, 16, "0", STR_PAD_LEFT);
        }
    }

    /**
     * 获取某个用户拥有合同的标id(去重)
     *
     * @param int $user_id
     * @param bool $make_page
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getDealidByUserid($user_id, $make_page, $page = 1, $page_size = 0){
        $condition = 'user_id = ":user_id" ORDER BY CASE WHEN `sign_time`=0 THEN 0 ELSE 1 END, `sign_time` DESC, `id` DESC';

        $limit = '';
        if($make_page){
            $page = intval($page) >= 1 ? intval($page) : 1;
            $page_size = intval($page_size) > 0 ? intval($page_size) : app_conf("PAGE_SIZE");
            $limit = sprintf(" LIMIT %d, %d", ($page - 1) * $page_size, $page_size);
        }

        $params = array(
            ':user_id' => intval($user_id),
        );
        return $this->findAllViaSlave($condition . $limit, true, 'DISTINCT(`deal_id`) as deal_id', $params);
    }

    /**
     * 获取某个用户拥有合同的标id(去重)
     *
     * @param int $user_id
     * @param bool $make_page
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getDealidByUseridNew($user_id, $make_page, $page = 1, $page_size = 0,$isP2p = false){
        if($isP2p === false){
            $condition = 'user_id = ":user_id" ORDER BY `id` DESC';
        } else {
            $condition = sprintf('user_id = ":user_id" AND deal_type IN ( %s) ORDER BY `id` DESC', DealModel::DEAL_TYPE_ALL_P2P);
        }
        $limit = '';
        if($make_page){
            $page = intval($page) >= 1 ? intval($page) : 1;
            $page_size = intval($page_size) > 0 ? intval($page_size) : app_conf("PAGE_SIZE");
            $limit = sprintf(" LIMIT %d, %d", ($page - 1) * $page_size, $page_size);
        }

        $params = array(
            ':user_id' => intval($user_id),
        );
        $deal_model = new DealLoadModel();
        return $deal_model->findAllViaSlave($condition . $limit, true, 'DISTINCT(`deal_id`) as deal_id', $params);
    }

    /**
     * 普通用户合同借款列表
     *
     * @param int $user_id 用户id
     * @param string $limit 分页查询
     * @param boolen $is_show_attachment 是否获取附件合同标的
     * @return array
     */
    public function getUserContDeals($user_id, $make_page = true, $page = 1, $page_size = 0, $is_show_attachment = true, $isP2p = false){
        if (false === $is_show_attachment) {
            // 获取附件合同的分类id
            $request_category = new RequestGetCategoriesLikeTypeTag();
            $request_category->setTypeTag("ATTACHMENT%");
            $rpc = new Rpc('contractRpc');
            $response_category = $rpc->go("\NCFGroup\Contract\Services\Category","getCategoryLikeTypeTag",$request_category);
            $categories = $response_category->getList();
            $not_in_cont = '';
            $attachment_category_id_arr = array();
            foreach ($categories as $category) {
                $attachment_category_id_arr[] = $category['id'];
            }
            $attachment_category_ids = implode(',', $attachment_category_id_arr);
            if (!empty($attachment_category_ids)) {
                $not_in_cont = " AND `contract_tpl_type` NOT IN ({$attachment_category_ids}) ";
            }
        }

        $count = 0;
        $list = array();
        $ids_arr = $this->getDealidByUseridNew($user_id, $make_page, $page, $page_size,$isP2p);
        if($ids_arr){
            $fields = 'id,name,user_id,borrow_amount,income_fee_rate,loantype,repay_time,type_id,deal_type';
            $deal_model = DealModel::instance();
            $ids_res = implode(',', array_map('array_shift', $ids_arr));
            $cnt_arr = $this->getDealidByUseridNew($user_id, false);
            $cnt_res = implode(',', array_map('array_shift', $cnt_arr));
            $count = $deal_model->countViaSlave(sprintf("is_delete = 0 AND id IN (%s) %s", $cnt_res, $not_in_cont));
            $list = $deal_model->findAllViaSlave(sprintf("is_delete = 0 AND id IN (%s) %s ORDER BY FIELD(`id`, %s)", $ids_res, $not_in_cont, $ids_res), true, $fields);
        }
        return array('count' => $count, 'list' => $list);
    }

    /**
     * 获取某个借款的合同列表
     * @param $user_id 用户id
     * @param $deal_id 借款id
     * @param $option 仅查询需要签署的合同
     * @return array
     */
    public function getDealConts($user_id, $deal_id, $is_agency = 0, $option){
        if($is_agency){
            $condition = sprintf("`deal_id` = '%d' AND `user_id` = 0 AND `agency_id` = %d", intval($deal_id),intval($option['agency_id']));
        }else{
            $condition = sprintf("`deal_id` = '%d' AND `user_id` = '%d'", intval($deal_id), intval($user_id));
        }
        if($option['only_need_sign']){
            $condition .= " AND `type` != 7";
        }else{
            if($option['deal_type']){
                $condition .= sprintf(" AND `type` = %d",intval($option['deal_type']));
            }
        }
        if($option['need_list']){
            $limit = '';
            if($option['make_page']){
                $page = $option['page'];
                $page_size = $option['page_size'];
                $page = intval($page) >= 1 ? intval($page) : 1;
                $page_size = intval($page_size) > 0 ? intval($page_size) : app_conf("PAGE_SIZE");
                $limit = sprintf(" ORDER BY `id` DESC LIMIT %d, %d", ($page - 1) * $page_size, $page_size);
            }
            $res['list'] = $this->findAllViaSlave($condition.$limit, true);
        }
        $res['count'] = $this->countViaSlave($condition);

        if($res['count'] == 0){
            //在合同服务中读取
            $rpc = new Rpc('contractRpc');
            $deal = $this->find($deal_id);
            $contractRequest = new RequestGetContractByDealId();
            $contractRequest->setDealId(intval($deal_id));
            $contractRequest->setSourceType($deal['deal_type']);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByDealId",$contractRequest);
            if($response->errorCode == 0){
                foreach($response->list as $cont_one){
                    if(($cont_one['user_id'] == $user_id)||($cont_one['borrow_user_id'] == $user_id)){
                        $count_list[] = $cont_one;
                    }
                }
                $res['list'] = $count_list;
                $res['count'] = count($count_list);
            }
        }

        return $res;
    }

    /**
     * 担保公司合同借款列表
     * @param int $user_id 用户id
     * @param string $limit 分页查询
     * @return array
     */
    public function getAgencyContDeals($user_id, $agency_info, $make_page = true, $page = 1, $page_size = 0){

        $where = sprintf("c.agency_id = %d", $this->escape($agency_info['agency_id']));

        //如果是汇赢(HY)担保帐号,则显示汇赢合同 和 该用户所属担保公司的合同
        if($agency_info['is_hy']){
            $agency_list = AgencyUserModel::instance()->findAllViaSlave(sprintf("user_id = %d", $this->escape($user_id)), true);
            $agency_id_arr = array();
            if($agency_list){
                foreach ($agency_list as $ag_one){
                    $agency_id_arr[] = $ag_one['agency_id'];
                }
            }
            if($agency_id_arr){
                //显示汇赢合同 和 该用户所属担保公司的合同
                $agency_ids = implode(',', $agency_id_arr);
                $where = sprintf("((c.agency_id > 0 AND d.contract_tpl_type = 'HY') OR (c.agency_id IN (%s) AND d.contract_tpl_type != 'HY'))", $this->escape($agency_ids));
            }else{
                //只显示汇赢合同
                $where = sprintf("c.agency_id = %d AND d.contract_tpl_type = 'HY'", $this->escape($agency_info['agency_id']));
            }
        }elseif($agency_info['agency_id'] == $GLOBALS['dict']['HY_DBGS']){
            $where = sprintf("%s AND d.contract_tpl_type != 'HY'", $where);
        }

        $limit = '';
        if($make_page){
            $page = intval($page) >= 1 ? intval($page) : 1;
            $page_size = intval($page_size) > 0 ? intval($page_size) : app_conf("PAGE_SIZE");
            $limit = sprintf("LIMIT %d, %d", ($page - 1) * $page_size, $page_size);
        }

        $deal_fields = 'd.id,d.name,d.user_id,d.borrow_amount,d.income_fee_rate,d.loantype,d.repay_time';

        $list_sql = sprintf("SELECT %s FROM %s d LEFT JOIN %s c ON c.deal_id = d.id WHERE d.is_delete = 0 AND %s GROUP BY c.deal_id ORDER BY CASE WHEN c.sign_time=0 THEN 0 ELSE 1 END, c.sign_time DESC, c.id DESC %s",
                $deal_fields, DealModel::instance()->tableName(), $this->tableName(), $where, $limit);

        $count_sql = sprintf("SELECT COUNT(DISTINCT(deal_id)) FROM %s d LEFT JOIN %s c ON c.deal_id = d.id WHERE d.is_delete = 0 AND %s",
                DealModel::instance()->tableName(), $this->tableName(), $where);

        $res = array(
                'count' => $this->countBySql($count_sql, array(), true),
                'list' => $this->findAllBySql($list_sql, true, array(), true),
        );
        return $res;
    }

    /**
     * 检查表的借款合同签署时间 相关
     * @param unknown $deal_id
     * @param unknown $number
     * @return array
     */
    public function getContListByDeal($deal_id, $number) {
        //查出和当前借款合同 同属一条投资的 所有借款合同
        $condition = "type=1 AND deal_id =':deal_id' AND number=':number'";
        return $this->findAllViaSlave($condition, false, '*', array(':deal_id' => $deal_id,':number' => $number));
    }

    /**
     * 新合同-获取所有借款人咨询服务协议
     * @param unknown $deal_id
     * @param unknown $number
     * @return array
     */
    public function getLenderProtocalDeal($deal_id, $number) {
        //查出和当前借款人咨询服务协议 同属一条投资的 所有借款人咨询服务协议
        $condition = "type=5 AND deal_id =':deal_id' AND number=':number'";
        return $this->findAllViaSlave($condition, false, '*', array(':deal_id' => $deal_id,':number' => $number));
    }

    /**
     * 检查表的借款合同签署时间 相关
     * @param unknown $deal_id
     * @param unknown $user_id
     * @param unknown $number
     * @return array
     */
    public function getContListByUser($deal_id, $user_id, $number) {
        //获取合同编号对应的合同id
        $condition = "deal_id =':deal_id' AND user_id =':user_id' AND number=':number'";
        return $this->findBy($condition, '*', array(':deal_id' => $deal_id,':user_id' => $user_id,':number' => $number), true);
    }

    /**
     * 获取一个标的所有合同
     * @param unknown $deal_id
     * @param string $fields
     */
    public function getContListByDealId($deal_id){
        $condition = "deal_id =':deal_id'";
        return $this->findAll($condition, true, '*', array(':deal_id' => $deal_id));
    }

    /**
     * 删除一个标的合同以及内容
     * @param unknown $deal_id
     * @return boolean
     */
    public function delContByDealId($deal_id){
        $cont_list = $this->getContListByDealId($deal_id);
        if($cont_list){
            $sql = sprintf("DELETE FROM %s WHERE `deal_id` = %d", self::tableName(), $this->escape($deal_id));
            $del_contract = $this->execute($sql);
            if($del_contract){
                $cont_id = array();
                foreach($cont_list as $cont){
                    $cont_id[] = $cont['id'];
                }
                $del_content = ContractContentModel::instance()->mdel($cont_id);
                $del_sign = AgencyContractModel::instance()->delSignByDealId($deal_id);
            }
            return $del_contract;
        }
        return true;
    }

    /**
     * 获取所有可以补发的合同
     * @param intval $deal_id
     * @param intval $cont_ids
     * @return array
     */
    public function getRepairContByDeal($deal_id, $type = '', $cont_ids = array()) {

        $params = array(':deal_id' => $deal_id);
        $condition = "`deal_id` =':deal_id'";

        if($type){
            $params[':type'] = $type;
            $condition .= " AND `type` = ':type'";
        }else{
            $condition .= " AND `type` IN(1,2,4,5,7)";
        }

        if($cont_ids){
            $params[':cont_ids'] = implode(',', $cont_ids);
            $condition .= " AND `id` IN(:cont_ids)";
        }
        return $this->findAll($condition, true, '*', $params);
    }

    /**
     * 用于单个合同签署后的检查，如果角色合同签署完成，则更新deal_contract表的状态
     * @param int $cont_id
     * @return bool
     */
    public function syncContractStatus($cont_id) {
        $row = $this->find($cont_id);
        if (!$row) {
            return false;
        }

        $params = array(
            ":deal_id" => $row['deal_id'],
            ":agency_id" => $row['agency_id'],
        );

        $deal_contract_model = new DealContractModel();
        if ($row['agency_id']) {
            $params[':agency_id'] = $row['agency_id'];
            $cnt = $this->count("`deal_id`=':deal_id' AND `agency_id`=':agency_id' AND `sign_time`='0'", $params);
            if ($cnt !== false && $cnt == 0) {
                return $deal_contract_model->endSign($row['deal_id'], 0, 1,$row['agency_id']);
            }
        } else {
            $params[':user_id'] = $row['user_id'];
            $cnt = $this->count("`deal_id`=':deal_id' AND `user_id`=':user_id' AND `sign_time`='0'", $params);
            if ($cnt !== false && $cnt == 0) {
                return $deal_contract_model->endSign($row['deal_id'], $row['user_id']);
            }
        }
    }

    /**
     * 签署合同 NEW
     * @param unknown $id_or_arr
     * @return Ambigous <number, boolean>
     */
    public function contSignById($id){
        if($id <= 0){
            return false;
        }
        $sql = sprintf("UPDATE %s SET `sign_time` = '%s' WHERE `sign_time` = '' AND id = '%d'",
                $this->tableName(), time(), $id
        );
        return $this->updateRows($sql);
    }

    /**
     * 二次签署合同
     * @param unknown $id
     * @return Ambigous <number, boolean>
     */
    public function contResignById($id, $status = 0){
        if($id <= 0){
            return false;
        }
        $sql = sprintf("UPDATE %s SET `resign_status` = '%d',`resign_time` = '%s' WHERE `resign_time` = '' AND id = '%d'",
                $this->tableName(), $status, time(), $id
        );
        return $this->updateRows($sql);
    }

    /**
     * 一键签署,借款人或担保公司单方的（前台调用）
     * @param unknown $deal_id
     * @param unknown $user_id
     * @param number $is_agency
     * @return Ambigous <number, boolean>
     */
    public function contSignByDeal($deal_id, $user_id, $is_agency = 0, $agency_id = 0){
        if($is_agency){
            if($agency_id == 0)
            {
                $where = "`agency_id` > 0 AND `user_id` = 0";
            }else{
                $where = sprintf("`agency_id` = '%d' AND `user_id` = 0",$agency_id);
            }
        }else{
            $where = sprintf("`agency_id` = 0 AND `user_id` = '%d' AND `type` != 7", $user_id);
        }
        $condition = sprintf("`deal_id` = '%d' AND `sign_time` = '' AND %s", $deal_id, $where);
        $this->db->startTrans();
        try {
            $list = $this->findAll($condition);
            foreach ($list as $v) {
                $v->sign_time = time();
                if ($v->save() === false) {
                    throw new \Exception("contract sign fail");
                }
            }

            $deal_contract_model = new DealContractModel();
            $r2 = $deal_contract_model->endSign($deal_id, $user_id, $is_agency, $agency_id);
            if ($r2 === false) {
                throw new \Exception("end sign fail");
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * 一键签署,签署整个标的（后台调用）
     * @param unknown $deal_id
     * @param unknown $user_id
     * @param number $is_agency
     * @return Ambigous <number, boolean>
     */
    public function contSignAllByDeal($deal_id){
        $deal_id = intval($deal_id);
        $deal_info = DealModel::instance()->find($deal_id, 'user_id,agency_id,advisory_id,contract_tpl_type', true);
        if(empty($deal_info)){
            return false;
        }
        $sql = sprintf("UPDATE %s SET `sign_time` = '%s' WHERE `sign_time` = '' AND `type` != 7 AND `deal_id`='%d' AND (`user_id` = '%d' OR `agency_id` > 0)", $this->tableName(), time(), $deal_id, $deal_info['user_id']);
        $r = $this->updateRows($sql);
        if ($r === false) {
            return false;
        }

        $deal_contract_model = new DealContractModel();
        $deal_contract_model->endSign($deal_id, $deal_info['user_id']);
        $deal_contract_model->endSign($deal_id, 0, 1, $deal_info['agency_id']);
        if(((substr($deal_info['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal_info['contract_tpl_type'],0,5)) === 'NQYZR')){
            $deal_contract_model->endSign($deal_id, 0, 1, $deal_info['advisory_id']);
        }
    }

    /**
     * 查询一个合同的签署状态 NEW
     * @param $cont_info 合同信息
     * @param $user_id 用户id
     * @return array
     */
    public function getContSignStatus($cont_info, $user_id){
        $resign_pass = $cont_info['resign_status'];
        if($cont_info['sign_time']){
            $sign_pass = 1;
        }else{
            $sign_status = AgencyContractModel::instance()->getAgencyContractByUser($cont_info['id'], $user_id);
            if($sign_status){
                $sign_pass = $sign_status['pass'];
                $resign_pass = ($resign_pass == 0) ? $sign_status['sign_pass'] : $resign_pass;
            }
        }
        $res['cont_pass'] = ($cont_info['type'] == 7) ? 2 : intval($sign_pass);
        $res['cont_alone_pass'] = intval($resign_pass);
        return $res;
    }

    /**
     * 获取借款合同的借款金额、期限信息 NEW
     * @param $cont_info 合同信息
     * @return array
     */
    public function getLoadByCont($cont_info){
        $return = array();
        $deal_load_model = new DealLoadModel();
        if($cont_info['deal_load_id']){
            $return = $deal_load_model->find($cont_info['deal_load_id'], 'money', true);
        }else{
            $load_list = $deal_load_model->getDealLoanList($cont_info['deal_id']);
            if($load_list){
                $deal_info = DealModel::instance()->find($cont_info['deal_id'], '`id`, `parent_id`', true);
                foreach($load_list as $load_one){
                    $number = self::genContractNumber($deal_info['id'], $deal_info['parent_id'], $load_one['user_id'], $load_one['id'], 1);
                    if($number == $cont_info['number']){
                        $return = $load_one;
                        break;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * 获取合同的签署数量，借款人或担保公司单方的
     * @param unknown $deal_id
     * @param unknown $own_id
     * @param number $is_agency
     * @return Ambigous <number, string, boolean>
     */
    public function getContSignNumByDeal($deal_id, $own_id, $is_agency = 0,$agency_id = null){
        $condition = "`deal_id` = ':deal_id' AND `user_id` = ':user_id' AND `sign_time` > 0 AND `type` != 7";
        $params[':deal_id'] = $deal_id;
        $params[':user_id'] = $own_id;
        $params[':agency_id'] = intval($agency_id);
        if($is_agency){
            $condition .= " AND `agency_id`  = ':agency_id' ";
            $params[':user_id'] = 0;
        }
        return $this->countViaSlave($condition, $params);
    }

    /**
     * 获取合同的已签署数量，整个标的
     * @param unknown $deal_id
     * @param unknown $deal_user_id
     * @return Ambigous <number, string, boolean>
     */
    public function getContSignAllNumByDeal($deal_id, $deal_user_id = 0){
        $deal_id = intval($deal_id);
        if($deal_user_id == 0){
            $deal_info = DealModel::instance()->find($deal_id, 'user_id', true);
            if(empty($deal_info)){
                return false;
            }
            $deal_user_id = $deal_info['user_id'];
        }
        $condition = "`deal_id` = ':deal_id' AND `sign_time` > 0 AND type != 7 AND (`user_id` = ':user_id' OR `agency_id` > 0)";
        $params[':deal_id'] = $deal_id;
        $params[':user_id'] = $deal_user_id;
        return $this->count($condition, $params);
    }

    /**
     * 获取整个标的合同需签署的数量，
     * @param unknown $deal_id
     * @param unknown $own_id
     * @param number $is_agency
     * @return Ambigous <number, string, boolean>
     */
    public function getContNeedSignNumByDeal($deal_id, $deal_user_id){
        $deal_id = intval($deal_id);
        if($deal_user_id == 0){
            $deal_info = DealModel::instance()->find($deal_id, 'user_id', true);
            if(empty($deal_info)){
                return false;
            }
            $deal_user_id = $deal_info['user_id'];
        }
        $condition = "`deal_id` = ':deal_id' AND type != 7 AND (`user_id` = ':user_id' OR `agency_id` > 0)";
        $params[':deal_id'] = $deal_id;
        $params[':user_id'] = $deal_user_id;
        return $this->count($condition, $params);
    }

    /**
     * 获取借款人ID与合同中用户id相同，并且signtime>0 的数据
     * 既 获取借款人已经签署合同的条数
     */
    public function getCountOfBorrower($deal_id, $borrower_id) {
        $condition = "`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `sign_time`>0";
        $params = array(
            ':user_id' => $borrower_id,
            ':deal_id' => $deal_id,
        );
        $ret =  $this->countViaSlave($condition, $params);
        return $ret;
    }

    /**
     * 获取标下agencyid>0并且signtime>0 的数据
     * 既 获取机构以及经签署合同的条数
     */
    public function getCountOfAgency($deal_id) {
        $condition = "`deal_id` = ':deal_id' AND `agency_id`>0  AND `sign_time`>0";
        $params = array(
            ':deal_id' => $deal_id,
        );
        $ret =  $this->countViaSlave($condition, $params);
        return $ret;
    }

    public function getContractIdNumbersByDealId($deal_id){
	    //select * from `firstp2p_contract` where deal_id=10897 group by number
        $condition = "deal_id =':deal_id' GROUP BY number";
        return $this->findAllViaSlave($condition, true, '*', array(':deal_id' => $deal_id));
    }

    public function signTsaCallback($dealId,$number,$type=0,$projectId = 0){

        $dealService = new DealService();
        if($type == 2){
            $goldService = new GoldService();
            $dealInfo = $goldService->getDealById($dealId);
            $dealInfo->deal_type = 100;
        }elseif($type == 4){
            //获取暗月标的信息
            $dealInfo = DarkmoonDealModel::instance()->find($dealId);
            $dealInfo['deal_type'] = DarkmoonDealModel::DEAL_TYPE_OFFLINE_EXCHANGE;
        }elseif($type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER){
            $dealInfo = array('deal_type' => ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER);
        }else{
            $dealInfo = $dealService->getDeal($dealId);
        }
        if((!is_numeric($dealInfo['contract_tpl_type'])) && !in_array($type,[2,4,ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER])){
            $dealId = intval($dealId);
            $condition = "`deal_id`=':deal_id' AND `number`=':number' AND status = 1";
            $params = array(
                ':deal_id' => $dealId,
                ':number' => $number,
            );

            $contracts = $this->findAll($condition,false,"id",$params);

            foreach($contracts as $contractOne){
                $row = $this->find($contractOne['id']);
                $data = array(
                    'status' => 3,
                    'update_time' => time(),
                );
                if(!$row->updateOne($data)){
                    throw new \Exception("update status error ".$contractOne['id']);
                }
            }
        }else{
            $rpc = new Rpc('contractRpc');
            $sourceType = $type == 2?100:$dealInfo['deal_type'];
            $contractRequest = new RequestSignTsaCallback();
            $contractRequest->setDealId(intval($dealId));
            $contractRequest->setNumber(trim($number));
            $contractRequest->setSourceType($sourceType);
            if($type == 1){
                $contractRequest->setType(intval($type));
                $contractRequest->setProjectId(intval($projectId));
            }
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","signTsaCallback",$contractRequest);
            if($response->status != true){
                throw new \Exception("contract RPC status error "."code: ".$response->errCode." msg:".$response->errMsg);
            }
        }


        return true;
    }

    /*
     * 根据标的ID，用户id，合同类型获取合同数量
     */
    public function getContByDealType($dealId,$userId,$type){
        $condition = "deal_id =':dealId' AND user_id = ':userId' AND type = ':type'";
        return $this->count($condition, array(':dealId' => $dealId,':userId' => $userId,':type' => $type));
    }


} // END class ContractModel extends BaseModel
