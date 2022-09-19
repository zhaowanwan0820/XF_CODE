<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use libs\utils\Logger;
use core\service\ExchangeProjectService;
use core\service\DealAgencyService;
use core\dao\EnterpriseModel;

class ExchangeApi extends ApiBackend {

    //推标接口
    public function pushpro() {
        $aParData = $this->getParam();
        Logger::info("exchange_pushpro : ".json_encode($aParData));
        $aNot0Field = array('approve_number', 'name', 'jys_number', 'jys_id', 'settle_type', 'asset_type', 'consult_id', 'guarantee_id', 'invest_adviser_id',"business_manage_id", 'consult_type','guarantee_type', 'invest_adviser_type', 'publish_server_type', 'hang_server_type', 'repay_time_type', 'repay_type', 'money_todo', 'fx_uname', 'fx_unum', 'fx_unick');
        $a0to100 = array('consult_rate', 'guarantee_rate', 'invest_adviser_rate', 'invest_adviser_real_rate', 'publish_server_rate', 'publish_server_real_rate', 'hang_server_rate', 'expect_year_rate', 'ahead_repay_rate');
        $aintbg0 = array('amount', 'repay_time', 'lock_days', 'min_amount');
        $aAllField = array_merge($aNot0Field, $a0to100, $aintbg0);
        $aProData = array();
        foreach ($aAllField as $field) {
            if(!isset($aParData[$field])){
                return $this->formatResult(array(), 1001, "字段${field}必传");
            }
            $aProData[$field] = trim($aParData[$field]);
        }

        foreach ($aNot0Field as $field) {
            if(empty($aParData[$field])){
                return $this->formatResult(array(), 1001, "字段${field}不可为空");
            }
        }
        $aError = array(
            'approve_number' => '放款审批单号交易系统已存在',
            'name' => '项目名称交易系统已存在',
            'jys_number' => '交易所备案产品编号已存在',
            "jys_id" => '该交易所不存在',
            "consult_id" => '该咨询机构不存在',
            "guarantee_id" => '该担保机构不存在',
            "invest_adviser_id" => '该投资顾问机构不存在',
            "business_manage_id" => '该业务管理方不存在',
            'consult_rate' => '借款咨询费率错误',
            'guarantee_rate' => '借款担保费率错误',
            'invest_adviser_rate' => '投资顾问费率错误',
            'invest_adviser_real_rate' => '实际投资顾问费率错误',
            'publish_server_rate' => '发行服务费率错误',
            'publish_server_real_rate' => '实际发行服务费率错误',
            'hang_server_rate' => '挂牌服务费率错误',
            'expect_year_rate' => '预期年化收益率错误',
            'ahead_repay_rate' => '提前还款违约金费率错误',
            'amount' => '借款金额错误',
            'repay_time' => '产品期限不合法',
            'lock_days' => '锁定期错误',
            'min_amount' => '最低起投金额错误',
        );
        //唯一字段
        $oExchangeProjectService = new ExchangeProjectService();
        $aUniqField = array('approve_number', 'name', 'jys_number');
        foreach ($aUniqField as $field) {
            $isExist = $oExchangeProjectService->isExistStringField($field, $aProData[$field]);
            if($isExist){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //机构id
        $oDealAgencyService = new DealAgencyService();
        $aJg = array(
            "jys_id" => 9,//交易所
            "consult_id" => 2,//咨询机构
            "guarantee_id" => 1,//担保机构
            "invest_adviser_id" => 11,//投资顾问机构
            "business_manage_id" => 12,//业务管理方
        );
        foreach ($aJg as $field => $iAgencyType) {
            $aJys = $oDealAgencyService->getDealAgencyListByType($iAgencyType);
            if(empty($aJys[$aProData[$field]])){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //1、2枚举
        $a12Field = array("settle_type", "asset_type", 'consult_type', 'guarantee_type', 'invest_adviser_type', 'publish_server_type', 'hang_server_type', 'repay_time_type');
        foreach($a12Field as $field){
            if(!in_array($aProData[$field], array(1,2))){
                return $this->formatResult(array(), 1002, "$field 值不合法");
            }
        }
        //费率
        foreach($a0to100 as $field){
            $fieldval = $this->getParam($field);
            $aProData[$field] = floatval($fieldval);
            if($aProData[$field] > 100 || $aProData[$field] < 0){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //整型数
        foreach($aintbg0 as $field){
            $fieldval = $this->getParam($field);
            $aProData[$field] = intval($fieldval);
            if($aProData[$field] < 0){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //回款类型
        if(!in_array($aProData['repay_type'], array(1,2,3,4))
            || ($aProData['repay_type'] ==1 && $aProData['repay_time_type'] ==2)
            || ($aProData['repay_type'] !=1 && $aProData['repay_time_type'] == 1)
        ){
            return $this->formatResult(array(), 1002, "还款方式与产品期限类型不一致");
        }
        //发行人信息校验
        $oEnterpriseModel = new EnterpriseModel();
        $aFX = $oEnterpriseModel->getByCredentialsNo($aProData['fx_unum']);
        $aProData['fx_uid'] = 0;
        foreach ($aFX as $fx) {
            if(trim($fx['company_name']) == $aProData['fx_uname'] && trim($fx['identifier']) == $aProData['fx_unick']){
                $aProData['fx_uid'] = $fx['user_id'];
            }
        }
        if(empty($aProData['fx_uid'])){
            return $this->formatResult(array(), 1002, "发行人信息匹配失败");
        }

        $iRet = $oExchangeProjectService->addProject($aProData);
        if(!$iRet){
            return $this->formatResult(array(), 1003, "推标失败");
        }

        return $this->formatResult(array("id"=>$iRet));
    }

    //同步项目信息
    public function synpro(){
        $aPData = $this->getParam();
        Logger::info("exchange_synpro : ".json_encode($aPData));
        $sApproveNumber = trim($aPData['approve_number']);
        if(empty($sApproveNumber)){
            return $this->formatResult(array(), 1001, "放款审批单号为空");
        }
        $oExchangeProjectService = new ExchangeProjectService();
        $aPro = $oExchangeProjectService->getByApproveNumber($sApproveNumber);
        if(empty($aPro)){
            return $this->formatResult(array(), 1003, "该项目交易系统不存在");
        }
        if(!in_array($aPro['deal_status'], array(1,2))){
            return $this->formatResult(array(), 1003, "此标状态不可变更信息");
        }
        $aUnsetField = array('approve_number', 'timestamp', 'sign');
        foreach ($aUnsetField as $field) {
            unset($aPData[$field]);
        }
        if($aPro['deal_status'] == 2){
            $aInField = array('invest_adviser_real_rate', 'publish_server_real_rate');
            foreach ($aPData as $field => $value) {
                if(!in_array($field, $aInField)){
                    return $this->formatResult(array(), 1004, "标的状态为进行中不可修改$field");
                }
            }
        }
        $aError = array(
            'jys_number' => '交易所备案产品编号已存在',
            "invest_adviser_id" => '该投资顾问机构不存在',
            "business_manage_id" => '该业务管理方不存在',
            'consult_rate' => '借款咨询费率错误',
            'guarantee_rate' => '借款担保费率错误',
            'invest_adviser_rate' => '投资顾问费率错误',
            'invest_adviser_real_rate' => '实际投资顾问费率错误',
            'publish_server_rate' => '发行服务费率错误',
            'publish_server_real_rate' => '实际发行服务费率错误',
            'hang_server_rate' => '挂牌服务费率错误',
            'expect_year_rate' => '预期年化收益率错误',
            'amount' => '借款金额错误',
            'repay_time' => '产品期限不合法',
            'lock_days' => '锁定期错误',
            'min_amount' => '最低起投金额错误',
        );
        //交易所备案产品编号
        $aUniqField = array('jys_number');
        foreach ($aUniqField as $field) {
            if(!isset($aPData[$field]) || $aPData[$field] == $aPro[$field]){
                continue;
            }
            $isExist = $oExchangeProjectService->isExistStringField($field, $aPData[$field]);
            if($isExist){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //机构id
        $oDealAgencyService = new DealAgencyService();
        $aJg = array(
            "invest_adviser_id" => 11,//投资顾问机构
            "business_manage_id" => 12,//业务管理方
        );
        foreach ($aJg as $field => $iAgencyType) {
            if(!isset($aPData[$field])){
                continue;
            }
            $aJys = $oDealAgencyService->getDealAgencyListByType($iAgencyType);
            if(empty($aJys[$aPData[$field]])){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //费率
        $a0to100 = array('consult_rate', 'guarantee_rate', 'invest_adviser_rate', 'invest_adviser_real_rate', 'publish_server_rate', 'publish_server_real_rate', 'hang_server_rate', 'expect_year_rate');
        foreach($a0to100 as $field){
            if(!isset($aPData[$field])){
                continue;
            }
            $aPData[$field] = floatval($aPData[$field]);
            if($aPData[$field] > 100 || $aPData[$field] < 0){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //1、2枚举
        $a12Field = array("settle_type", 'consult_type', 'guarantee_type', 'invest_adviser_type', 'publish_server_type', 'hang_server_type', 'repay_time_type');
        foreach($a12Field as $field){
            if(!isset($aPData[$field])){
                continue;
            }
            if(!in_array($aPData[$field], array(1,2))){
                return $this->formatResult(array(), 1002, "字段${field}值不合法");
            }
        }
        //整型数
        $aintbg0 = array('amount', 'repay_time', 'lock_days', 'min_amount');
        foreach($aintbg0 as $field){
            if(!isset($aPData[$field])){
                continue;
            }
            $aPData[$field] = intval($aPData[$field]);
            if($aPData[$field] < 0){
                return $this->formatResult(array(), 1002, $aError[$field]);
            }
        }
        //回款类型
        if(isset($aPData['repay_type']) && !in_array($aPData['repay_type'], array(1,2,3,4))){
            return $this->formatResult(array(), 1002, "repay_type value illegal");
        }
        $repay_time_type = isset($aPData['repay_time_type']) ? $aPData['repay_time_type'] : ($aPro['repay_type'] == 1 ? 1 : 2);
        $repay_type = isset($aPData['repay_type']) ? $aPData['repay_type'] : $aPro['repay_type'];
        if(($repay_type ==1 && $repay_time_type ==2) || ($repay_type !=1 && $repay_time_type == 1)
        ){
            return $this->formatResult(array(), 1002, "还款方式与产品期限类型不一致");
        }

        $iRet = $oExchangeProjectService->synpro($aPro['id'], $aPData);
        if($iRet === false){
            return $this->formatResult(array(), 1003, "更新失败");
        }
        return $this->formatResult(array());
    }

    //查询项目信息
    public function getpro(){
        $aPData = $this->getParam();
        $sApproveNumber = trim($aPData['approve_number']);
        if(empty($sApproveNumber)){
            return $this->formatResult(array(), 1001, "approve_number为空");
        }
        $oExchangeProjectService = new ExchangeProjectService();
        $aPro = $oExchangeProjectService->getByApproveNumber($sApproveNumber);
        if(empty($aPro)){
            return $this->formatResult(array(), 1003, "查询的标不存在");
        }
        $aRet = array();
        $aRet['approve_number'] = $aPro['approve_number'];
        $aRet['deal_status'] = $aPro['deal_status'];
        if($aRet['deal_status'] == 3 || $aRet['deal_status'] == 4){
            $aRet['borrow_amount'] = $aPro['real_amount'];
        }
        //$aRet['is_ok'] = $aPro['is_ok'];

        return $this->formatResult($aRet);
    }
}