<?php

namespace core\service;

//use NCFGroup\Task\Services\TaskService AS GTaskService;
use libs\utils\Logger;
use core\dao\ExchangeProjectModel;
use libs\utils\Curl;

class ExchangeProjectService extends BaseService {

    private $model;

    public function __construct(){
        $this->model = new ExchangeProjectModel();
    }

    public function isExistStringField($sField, $sString){
        $sField = trim($sField);
        if(empty($sField)){
            return false;
        }
        $sString = trim($sString);
        $iCount = $this->model->isExistStringField($sField, $sString);
        return !empty($iCount);
    }

    public function addProject($aProData){
        return $this->model->addProject($aProData);
    }

    public function getByApproveNumber($sApproveNumber){
        return $this->model->getByApproveNumber($sApproveNumber);
    }

    public function synpro($id, $aPData){
        return $this->model->synpro($id, $aPData);
    }

    public function getById($iProId){
        return $this->model->getById($iProId);
    }

    //向信贷同步项目状态
    public function synProjectStatus($params){
        Logger::info("syn_pro_stats_start".json_encode($params));
        $iProId = intval($params['projectId']);
        if(empty($iProId)){
            return true;
        }
        $aPro = $this->getById($iProId);
        Logger::info("syn_pro_stats_start".json_encode($aPro));
        if(empty($aPro)){
            return true;
        }
        if(empty($aPro['approve_number'])){//不是信贷推送过来的项目
            return true;
        }
        $url = app_conf('XINDAI_DOMAIN')."/api/cs/fundHuiSApply/stateSync";
        $params = array(
            'approve_number'=>$aPro['approve_number'],
            'deal_status'=>$aPro['deal_status'],
            'client_id'=>'74ba4171a4217265537f4d1b',
            'timestamp'=> date("Y-m-d H:i:s"),
        );
        $salt = app_conf('XINDAI_SALT');
        if($aPro['deal_status'] == 3 || $aPro['deal_status'] == 4){
            $params['borrow_amount'] = $aPro['real_amount']/100;
            $params['sign'] = md5($salt."approve_number{$params['approve_number']}borrow_amount{$params['borrow_amount']}client_id{$params['client_id']}deal_status{$params['deal_status']}timestamp{$params['timestamp']}".$salt);
        }else{
            $params['sign'] = md5($salt."approve_number{$params['approve_number']}client_id{$params['client_id']}deal_status{$params['deal_status']}timestamp{$params['timestamp']}".$salt);
        }

        $iRetry = 0;
        while($iRetry < 3){
            $iRetry++;
            $result = Curl::post($url, $params);
            Logger::info("retry $iRetry 信贷同步返回数据：".json_encode($result));
            $aRet = json_decode($result, true);
            if($aRet === false || intval($aRet['code']) != 0){
                //同步错误
                //return true;
            }else{
                break;
            }
        }
        return true;
    }
}
