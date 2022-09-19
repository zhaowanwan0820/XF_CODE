<?php
/**
 *
 * User: jinhaidong
 * Date: 2018-05-05
 * Time: 18:28
 */
namespace core\dao\darkmoon;

use core\dao\BaseModel;
use core\dao\darkmoon\DarkmoonDealModel;

class DarkmoonDealLoadModel extends BaseModel {

    // 待签署
    const SIGN_WAIT_STATUS = 1;

    // 已签署
    const SIGN_ALREADY_STATUS = 2;

    // 废弃
    const SIGN_DISCARD_STATUS = 3;

    static public $signstatus = array(
            self::SIGN_WAIT_STATUS =>"待签署",
            self::SIGN_ALREADY_STATUS =>"已签署",
            self::SIGN_DISCARD_STATUS =>"废弃",
    );

    /**
     *  用户是否需要签署合同
     * @param $userId int
     * @return boole
     */
    public function isSignUserContract($idNo, $deal_id = 0){
        if (empty($idNo)){
            return false;
        }
        $condition = " idno=':idNo' ";
        if (is_numeric($deal_id) && !empty($deal_id)){
            $condition .= ' AND deal_id='.intval($deal_id);
        }
        $condition .= ' AND status='.self::SIGN_WAIT_STATUS.'  ';
        $params = array(
            ':idNo' => $idNo
        );
        $ret = $this->findByViaSlave($condition,'id,deal_id',$params);
        $ret = $ret ? $ret->getRow() : false;

        if (empty($ret['id'])){
            return false;
        }
        if (empty($deal_id)){
            return false;
        }
        // 标状态为签署中
        $deal_model = new DarkmoonDealModel();
        $deal_info = $deal_model->getInfoById($deal_id);
        if (empty($deal_info) ){
            return false;
        }

        if ($deal_info['deal_status'] != DarkmoonDealModel::DEAL_SIGNING_STATUS){
            return false;
        }
        return true;

    }

    /**
     *  合同是否已签完
     * @param $userId int
     * @return boole
     */
    public function isContractSignComplete($idNo, $deal_id = 0){
        if (empty($idNo)){
            return false;
        }

        $condition = " idno=':idNo' ";
        if (is_numeric($deal_id) && !empty($deal_id)){
            $condition .= ' AND deal_id='.intval($deal_id);
        }
        $condition .= ' AND status='.self::SIGN_ALREADY_STATUS.'  ';
        $params = array(
            ':idNo' => $idNo
        );
        $ret = $this->findByViaSlave($condition,'id',$params);
        $ret = $ret ? $ret->getRow() : false;

        if (!empty($ret['id'])){
            return true;
        }

        return false;

    }
    /**
     * 获取单条信息
     */
    public function getInfoByIdnoDealId($idNo, $deal_id,$fields = 'id'){

        if (empty($idNo) || empty($deal_id) || empty($fields)){
            return false;
        }

        $condition = " idno=':idNo' ".' AND deal_id='.intval($deal_id);
        $params = array(
            ':idNo' => $idNo,
        );

        $ret = $this->findBy($condition,$fields,$params,true);
        $ret = $ret ? $ret->getRow() : false;

        return $ret;

    }

    /**
     * 获取多条记录
     */
    public function getAllByIdnoDealId($idNo, $deal_id,$fields = 'id'){

        if (empty($idNo) || empty($deal_id) || empty($fields)){
            return false;
        }
        $condition = " idno=':idNo' ".' AND deal_id='.intval($deal_id);
        $params = array(
            ':idNo' => $idNo,
        );

        $list = $this->findAllViaSlave($condition,true,$fields,$params);

        return $list;

    }
    /**
     * 更新用户投资记录的userId
     * @param $loadId 投资记录
     * @param $userId 用户Id
     * @return bool
     */
    public function updateLoadUserInfo($loadId,$userId) {
        $deal_load_info = DarkmoonDealLoadModel::instance()->findViaSlave($loadId);
        if (empty($deal_load_info)) {
            return false;
        }
        $deal_load_info->user_id = $userId;
        if(false === $deal_load_info->save()){
            return false;
        }
        return true;
    }

    /**
     * 根据标的ID和订单状态获取所有投资记录
     */
    public function getByDealId($deal_id, $status = false) {
        $sql = "deal_id='%d'";
        $sql = sprintf($sql, $this->escape($deal_id));
        if ($status !== false) {
            $sql .= " and status='%d'";
            $sql = sprintf($sql, $this->escape($status));
        }
        return $this->findAll($sql, true);
    }
    /**
     *
     */
    public function getByDealIdUserId($deal_id, $user_id,$status = false) {
        $sql = "deal_id='%d' AND user_id='%d'";
        $sql = sprintf($sql, $this->escape($deal_id),$this->escape($user_id));
        if ($status !== false) {
            $sql .= " and status='%d'";
            $sql = sprintf($sql, $this->escape($status));
        }
        return $this->findAll($sql, true);
    }

    /**
     * 更新投资记录签署状态为已签署
     */
    public function updateSignStatus($id){

        if (empty($id)){
            return false;
        }

        $sql = 'update '.$this->tableName().' SET status='.self::SIGN_ALREADY_STATUS.', sign_time='.time().',update_time='.time().' WHERE id='.intval($id).' AND status='.self::SIGN_WAIT_STATUS;

        return $this->updateRows($sql);
    }

    /**
     * 获取未签署的投资记录
     * @param intval $deal_id
     * @return string
     */
    public function getUnsignCount($deal_id){
        $sql = "deal_id='%d'";
        $sql = sprintf($sql, $this->escape($deal_id));
        $sql .= " and status NOT in ( " . self::SIGN_ALREADY_STATUS.','.self::SIGN_DISCARD_STATUS.')';
        return $this->count($sql);
    }
}
