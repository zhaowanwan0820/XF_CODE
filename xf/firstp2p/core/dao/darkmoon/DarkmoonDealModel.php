<?php
/**
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018-05-05
 * Time: 18:27
 */


namespace core\dao\darkmoon;

use core\dao\BaseModel;
use core\service\darkmoon\ContractService;

class DarkmoonDealModel extends BaseModel {


    // 标类型
    const DEAL_TYPE_OFFLINE_EXCHANGE = 200;  //线下交易所

    // 待签署未生成合同链接
    const DEAL_WATI_STATUS = 0;

    // 签署中，生成合同链接
    const DEAL_SIGNING_STATUS = 1;
    // 已签署
    const STATUS_DEAL_SIGNED = 2;
    // 已完成
    const STATUS_DEAL_COMPLETE = 4;
    // 弃用
    const STATUS_DEAL_DISUSE = 3;

    static public $dealStatus = [
              self::DEAL_WATI_STATUS => '待签署',
              self::DEAL_SIGNING_STATUS =>'签署中',
              self::STATUS_DEAL_SIGNED =>'已签署',
              self::STATUS_DEAL_COMPLETE =>'已盖时间戳',
              self::STATUS_DEAL_DISUSE =>'已作废',
              ];
    /**
     * 判断已经签署完合同
     * @param $deal_id
     */
    public function isSignContract($deal_id){

        if (!is_numeric($deal_id)){
            return false;
        }
        $dealInfo = $this->getInfoById($deal_id);
        // 记录不存在
        if (empty($dealInfo)){
            return false;
        }
        if (self::DEAL_SIGNING_STATUS == $dealInfo['deal_status']){
            return true;
        }

        return false;

    }

    /**
     * 获取标信息
     */
    public function getInfoById($deal_id,$fields = 'deal_status'){

        if (!is_numeric($deal_id) || empty($fields)){
            return false;
        }
        $dealInfo = $this->find($deal_id,$fields,true);
        $ret = empty($dealInfo) ? false : $dealInfo->getRow();

        return $ret;
    }

    public function saveData($data){
        $newData = array();

        if(isset($data['id']) && $data['id'] > 0){
            $res = $this->find($data['id']);
            if(!$res){
                $this->_isNew = true;
                $newData['id'] = $data['id'];
                $newData['create_time'] = time();
            }else{
                $this->_isNew = false;
                $newData = $res->getRow();
            }
        }else{
            $newData['create_time'] = time();
        }

        $newData = array_merge($newData,$data);
        $newData['update_time'] = time();
        foreach($newData as $k=>$v){
            $this->{$k} = trim($v);
        }

        $saveRes = $this->save();
        //调用合同
        (new ContractService())->saveContact($this->id ,$this->contract_tpl_type);
        return ($saveRes && $this->_isNew) ? $this->id : $saveRes;
    }

    /**
     * 更新签署状态为已签署
     */
    public function updateSignStatus($id){

        if (empty($id)){
            return false;
        }

        $sql = 'update '.$this->tableName().' SET deal_status='.self::STATUS_DEAL_SIGNED.',sign_time='.time().' WHERE id='.intval($id).' AND deal_status='.self::DEAL_SIGNING_STATUS;

        return $this->updateRows($sql);
    }
}
