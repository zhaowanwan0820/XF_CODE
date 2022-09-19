<?php

use core\dao\JobsModel;

class OffexchangeProjectModel extends CommonModel {

    protected $tableName = "exchange_project";
    const RATENUM = 100000;

    public function getOexchangeProjectList($where="", $pageNum=1, $pageSize=10, $order=" `id` desc "){
        $pageNum = max(1, intval($pageNum));
        $pageSize = intval($pageSize);
        $offset = ($pageNum - 1) * $pageSize;
        $list = $this->where ( $where )->order ($order)->limit ( $offset.', '.$pageSize)->findAll ();
        $aRet = array();
        if(empty($list)){
            return $aRet;
        }
        foreach($list as $item){
            $aRet[$item['id']] = $this->formatRate($item);
        }
        return $aRet;
    }

    public function getOexchangeProjectCount($where=""){
        return $this->where($where)->count();
    }

    private function formatRate($item){
        $item['consult_rate'] = number_format($item['consult_rate']/self::RATENUM, 5, ".", ""); //借款咨询费率
        $item['guarantee_rate'] = number_format($item['guarantee_rate']/self::RATENUM, 5, ".", "");
        $item['invest_adviser_rate'] = number_format($item['invest_adviser_rate']/self::RATENUM, 5, ".", "");
        $item['invest_adviser_real_rate'] = number_format($item['invest_adviser_real_rate']/self::RATENUM, 5, ".", "");
        $item['publish_server_rate'] = number_format($item['publish_server_rate']/self::RATENUM, 5, ".", "");
        $item['publish_server_real_rate'] = number_format($item['publish_server_real_rate']/self::RATENUM, 5, ".", "");
        $item['hang_server_rate'] = number_format($item['hang_server_rate']/self::RATENUM, 5, ".", "");
        $item['amount'] = number_format($item['amount']/100, 2, ".", "");
        $item['real_amount'] = number_format($item['real_amount']/100, 2, ".", "");
        $item['expect_year_rate'] = number_format($item['expect_year_rate']/self::RATENUM, 5, ".", ""); //预期年化收益率
        $item['min_amount'] = number_format($item['min_amount']/100, 2, ".", "");
        $item['ahead_repay_rate'] = number_format($item['ahead_repay_rate']/self::RATENUM, 5, ".", "");
        return $item;
    }

    public function getById($id){
        $list = $this->getOexchangeProjectList(" id= ".intval($id));
        if(empty($list[$id])){
            return array();
        }
        return $list[$id];
    }

    public function saveOexchangeProject($data){
        $saveData = array();

        $isQR = true; //是否是待确认
        if(!empty($data['id'])){
            $aPro = $this->getById($data['id']);
            if(empty($aPro) || $aPro['deal_status'] > 2){
                return false;
            }
            $isQR = $aPro['deal_status'] == 1;
        }

        if($isQR){
            $saveData['name'] = trim($data['name']);
            $saveData['jys_number'] = trim($data['jys_number']);
            $saveData['jys_id'] = intval($data['jys_id']);
            $saveData['settle_type'] = intval($data['settle_type']);
            $saveData['fx_uid'] = intval($data['fx_uid']);
            $saveData['asset_type'] = intval($data['asset_type']);
            $saveData['consult_id'] = intval($data['consult_id']);
            $saveData['guarantee_id'] = intval($data['guarantee_id']);
            $saveData['invest_adviser_id'] = intval($data['invest_adviser_id']);
            $saveData['business_manage_id'] = intval($data['business_manage_id']);
            $saveData['consult_rate'] = intval(bcmul($data['consult_rate'], self::RATENUM));
            $saveData['consult_type'] = intval($data['consult_type']);
            $saveData['guarantee_rate'] = intval(bcmul($data['guarantee_rate'], self::RATENUM));
            $saveData['guarantee_type'] = intval($data['guarantee_type']);
            $saveData['invest_adviser_rate'] = intval(bcmul($data['invest_adviser_rate'], self::RATENUM));
            $saveData['invest_adviser_type'] = intval($data['invest_adviser_type']);
            $saveData['publish_server_rate'] = intval(bcmul($data['publish_server_rate'], self::RATENUM));
            $saveData['publish_server_type'] = intval($data['publish_server_type']);
            $saveData['hang_server_rate'] = intval(bcmul($data['hang_server_rate'], self::RATENUM));
            $saveData['hang_server_type'] = intval($data['hang_server_type']);
            $saveData['amount'] = intval(bcmul($data['amount'], 100));
            $saveData['repay_type'] = intval($data['repay_type']);
            if($saveData['repay_type'] == 1){
                $saveData['repay_time'] = intval($data['repay_time_day']);
            }elseif($saveData['repay_type'] == 2 || $saveData['repay_type'] == 3){
                $saveData['repay_time'] = intval($data['repay_time']);
            }elseif($saveData['repay_type'] == 4){
                $saveData['repay_time'] = intval($data['repay_time_ji']);
            }
            $saveData['expect_year_rate'] = intval(bcmul($data['expect_year_rate'], self::RATENUM));
            $saveData['lock_days'] = intval($data['lock_days']);
            $saveData['min_amount'] = intval(bcmul($data['min_amount'], 100));
            $saveData['ahead_repay_rate'] = intval(bcmul($data['ahead_repay_rate'], self::RATENUM));
            $saveData['money_todo'] = trim($data['money_todo']);
            $saveData['is_ok'] = intval($data['is_ok']);
        }
        $saveData['invest_adviser_real_rate'] = intval(bcmul($data['invest_adviser_real_rate'], self::RATENUM));
        $saveData['publish_server_real_rate'] = intval(bcmul($data['publish_server_real_rate'], self::RATENUM));

        if(empty($data['id'])){
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $saveData['cuid'] = intval($adm_session['adm_id']);
            return $this->add($saveData);
        }else{
            $saveData['id'] = intval($data['id']);
            $saveData['deal_status'] = intval($data['deal_status']);
            if($saveData['deal_status'] == 2 && $saveData['is_ok'] == 0){
                return false;//编辑为作废同时进行中
            }

            $saveData['utime'] = date('Y-m-d H:i:s');
            $this->startTrans();
            $bRet = $this->save($saveData);
            if(!$bRet){
                $this->rollback();
                return false;
            }
            if(intval(bcmul($aPro['invest_adviser_real_rate'], self::RATENUM)) != $saveData['invest_adviser_real_rate']
                || intval(bcmul($aPro['publish_server_real_rate'], self::RATENUM)) != $saveData['publish_server_real_rate']){//改变了实际费率
                $aBatchList = D('OffexchangeBatch')->getOexchangeBatchList(" `pro_id` = {$saveData['id']} AND deal_status = 1 AND is_ok = 1 AND is_last_start = 1 AND amount > 0 ");
                if(!empty($aBatchList)){//最后批次未放款，重新计算费用
                    $aBatch = array_pop($aBatchList);
                    $bRet = D('OffexchangeBatch')->updateBatchFee($aBatch['id']);
                    if(!$bRet){
                        $this->rollback();
                        return false;
                    }
                }
            }
            if($saveData['deal_status'] == 2){//编辑为进行中自动新加一个批次
                $aBatch = array(
                    'pro_id' => $saveData['id'],
                    'consult_rate' => $saveData['consult_rate'],
                    'guarantee_rate' => $saveData['guarantee_rate'],
                    'invest_adviser_rate' => $saveData['invest_adviser_rate'],
                    'publish_server_rate' => $saveData['publish_server_rate'],
                    'hang_server_rate' => $saveData['hang_server_rate'],
                );
                $bRet = D('OffexchangeBatch')->saveOexchangeBatch($aBatch);
                if(!$bRet){
                    $this->rollback();
                    return false;
                }
            }
            $this->commit();
            if($saveData['deal_status'] == 2){
                $function  = '\core\service\ExchangeProjectService::synProjectStatus';
                JobsModel::instance()->addJob($function, array(array('projectId' => $saveData['id'])));
            }
            return true;
        }
    }

    /**
     * 判断项目名称是否唯一
     * @param string name
     * @param int id
     * return boolean
     */
    public function checkNameIsUnique($id = 0, $name) {
       $res = $this->where("  `name` = '$name' AND `id` != $id AND is_ok = 1" )->findAll();
       if(empty($res)){
           return true;
       }
       return false;
    }

}
