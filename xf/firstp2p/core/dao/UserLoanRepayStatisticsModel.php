<?php
namespace core\dao;

use core\dao\DealLoanRepayModel;
use libs\db\MysqlDb;

class UserLoanRepayStatisticsModel extends BaseModel {
    public function updateUserAssets($uid,$moneyInfo) {
        $time = time();
        $fields = array(
            'load_repay_money' => isset($moneyInfo['load_repay_money']) ? $moneyInfo['load_repay_money'] : 0,
            'load_earnings' => isset($moneyInfo['load_earnings']) ? $moneyInfo['load_earnings'] : 0,
            'load_tq_impose' => isset($moneyInfo['load_tq_impose']) ? $moneyInfo['load_tq_impose'] : 0,
            'load_yq_impose' => isset($moneyInfo['load_yq_impose']) ? $moneyInfo['load_yq_impose'] : 0,
            'norepay_principal' => isset($moneyInfo['norepay_principal']) ? $moneyInfo['norepay_principal'] : 0,
            'norepay_interest' => isset($moneyInfo['norepay_interest']) ? $moneyInfo['norepay_interest'] : 0,
            'js_norepay_principal' => isset($moneyInfo['js_norepay_principal']) ? $moneyInfo['js_norepay_principal'] : 0,
            'js_norepay_earnings' => isset($moneyInfo['js_norepay_earnings']) ? $moneyInfo['js_norepay_earnings'] : 0,
            'js_total_earnings' => isset($moneyInfo['js_total_earnings']) ? $moneyInfo['js_total_earnings'] : 0,
            'cg_norepay_principal' => isset($moneyInfo['cg_norepay_principal']) ? $moneyInfo['cg_norepay_principal'] : 0,
            'cg_norepay_earnings' => isset($moneyInfo['cg_norepay_earnings']) ? $moneyInfo['cg_norepay_earnings'] : 0,
            'cg_total_earnings' => isset($moneyInfo['cg_total_earnings']) ? $moneyInfo['cg_total_earnings'] : 0,
        );

        $sql = "update firstp2p_user_loan_repay_statistics set load_repay_money=load_repay_money+".$fields['load_repay_money'];
        $sql.=" ,load_earnings=load_earnings+".$fields['load_earnings'];
        $sql.=" ,load_tq_impose=load_tq_impose+".$fields['load_tq_impose'];
        $sql.=" ,load_yq_impose=load_yq_impose+".$fields['load_yq_impose'];
        $sql.=" ,norepay_principal=norepay_principal+".$fields['norepay_principal'];
        $sql.=" ,norepay_interest=norepay_interest+".$fields['norepay_interest'];
        $sql.=" ,js_norepay_principal=js_norepay_principal+".$fields['js_norepay_principal'];
        $sql.=" ,js_norepay_earnings=js_norepay_earnings+".$fields['js_norepay_earnings'];
        $sql.=" ,js_total_earnings=js_total_earnings+".$fields['js_total_earnings'];
        $sql.=" ,cg_norepay_principal=cg_norepay_principal+".$fields['cg_norepay_principal'];
        $sql.=" ,cg_norepay_earnings=cg_norepay_earnings+".$fields['cg_norepay_earnings'];
        $sql.=" ,cg_total_earnings=cg_total_earnings+".$fields['cg_total_earnings'];

        $sql.=" ,update_time=".$time." where user_id=".$uid;
        return $this->execute($sql);
    }

    /**
     * 根据用户的loan_repay 表数据重置用户数据
     * @param $uid
     * @param $moneyInfo
     * @return mixed
     */
    public function resetUserAsset($uid,$moneyInfo) {
        $time = time();
        $fields = array(
            'load_repay_money' => isset($moneyInfo['load_repay_money']) ? $moneyInfo['load_repay_money'] : 0,
            'load_earnings' => isset($moneyInfo['load_earnings']) ? $moneyInfo['load_earnings'] : 0,
            'load_tq_impose' => isset($moneyInfo['load_tq_impose']) ? $moneyInfo['load_tq_impose'] : 0,
            'load_yq_impose' => isset($moneyInfo['load_yq_impose']) ? $moneyInfo['load_yq_impose'] : 0,
            'norepay_principal' => isset($moneyInfo['norepay_principal']) ? $moneyInfo['norepay_principal'] : 0,
            'norepay_interest' => isset($moneyInfo['norepay_interest']) ? $moneyInfo['norepay_interest'] : 0,
            'js_norepay_principal' => isset($moneyInfo['js_norepay_principal']) ? $moneyInfo['js_norepay_principal'] : 0,
            'js_norepay_earnings' => isset($moneyInfo['js_norepay_earnings']) ? $moneyInfo['js_norepay_earnings'] : 0,
            'js_total_earnings' => isset($moneyInfo['js_total_earnings']) ? $moneyInfo['js_total_earnings'] : 0,
        );
        $sql = "update firstp2p_user_loan_repay_statistics set load_repay_money=".$fields['load_repay_money'];
        $sql.=" ,load_earnings=".$fields['load_earnings'];
        $sql.=" ,load_tq_impose=".$fields['load_tq_impose'];
        $sql.=" ,load_yq_impose=".$fields['load_yq_impose'];
        $sql.=" ,norepay_principal=".$fields['norepay_principal'];
        $sql.=" ,norepay_interest=".$fields['norepay_interest'];
        $sql.=" ,js_norepay_principal=".$fields['js_norepay_principal'];
        $sql.=" ,js_norepay_earnings=".$fields['js_norepay_earnings'];
        $sql.=" ,js_total_earnings=".$fields['js_total_earnings'];
        $sql.=" ,update_time=".$time." where user_id=".$uid;
        return $this->execute($sql);
    }

    /**
     * 保存用户资产数据
     * @param $uid
     * @param $data
     */
    public function saveUserAssets($uid,$data){
        if($this->isExistsUser($uid)) {
            return true;
        }
        $time = time();
        try{
            $sql = "INSERT INTO firstp2p_user_loan_repay_statistics(user_id,load_repay_money,load_earnings,load_tq_impose,load_yq_impose,norepay_principal,norepay_interest,js_norepay_principal,js_norepay_earnings,js_total_earnings,create_time,update_time)";
            $sql.=" VALUES ($uid,"
                .$data['load_repay_money'].","
                .$data['load_earnings'].","
                .$data['load_tq_impose'].","
                .$data['load_yq_impose'].","
                .$data['norepay_principal'].","
                .$data['norepay_interest'].","
                .$data['js_norepay_principal'].","
                .$data['js_norepay_earnings'].","
                .$data['js_total_earnings'].","
                .$time.",".$time.")";
            $res = $GLOBALS['db']->query($sql);
        }catch (\Exception $ex) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'user summary save error')));
            return false;
        }
        return true;
    }

    /**
     * 判断用户资产是否已经同步
     * @param $uid
     * @return mixed
     */
    public function isExistsUser($uid) {
        $sql = "select count(*) as cnt from firstp2p_user_loan_repay_statistics where user_id=".$uid;
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 获取用户资产
     * @param $uid
     * @return mixed
     */
    public function getUserAssets($uid,$get_from_slave=false) {
        $condition = "user_id=".$uid;
        $fields = 'load_repay_money,load_earnings,load_tq_impose,load_yq_impose,norepay_principal,norepay_interest,dt_norepay_principal,dt_repay_interest,dt_load_money,js_norepay_principal,js_norepay_earnings,js_total_earnings,cg_norepay_principal,cg_norepay_earnings,cg_total_earnings';
        return $this->findBy($condition,$fields,array(),$get_from_slave);
    }

    /**
     * 同步用户资产数据
     * @param $uid
     * @param $data
     */
    public function syncUserAssets($user_id,$data) {
        $allowKeys = array('load_repay_money','load_earnings','load_tq_impose','load_yq_impose','norepay_principal','norepay_interest');
        $keys = array_keys($data);
        $diff = array_diff($keys,$allowKeys);
        if(!empty($diff)) {
            throw new \Exception("Not allow keys:".implode(",",$diff));
        }

        $data['load_repay_money']   = $data['load_repay_money'] ? $data['load_repay_money'] : 0;
        $data['load_earnings']      = $data['load_earnings'] ? $data['load_earnings'] : 0;
        $data['load_tq_impose']     = $data['load_tq_impose'] ? $data['load_tq_impose'] : 0;
        $data['load_yq_impose']     = $data['load_yq_impose'] ? $data['load_yq_impose'] : 0;
        $data['norepay_principal']  = $data['norepay_principal'] ? $data['norepay_principal'] : 0;
        $data['norepay_interest']   = $data['norepay_interest'] ? $data['norepay_interest'] : 0;

        $time = time();
        $sql = "INSERT INTO firstp2p_user_loan_repay_statistics(user_id,load_repay_money,load_earnings,load_tq_impose,load_yq_impose,norepay_principal,norepay_interest,create_time,update_time)";
        $sql.=" VALUES ($user_id,".$data['load_repay_money'].",".$data['load_earnings'].",".$data['load_tq_impose'].",".$data['load_yq_impose'].",".$data['norepay_principal'].",".$data['norepay_interest'].",".$time.",".$time.")";

        return $GLOBALS['db']->query($sql);
    }

    /**
     * 初始化用户数据
     * @param $uid
     */
    public function initUserAssets($uid) {
        $data = $this->getUserAssetFromLoanRepay($uid);
        $this->saveUserAssets($uid,$data);
        return $data;
    }

    public function getUserAssetFromLoanRepay($uid) {
        $LoanRepayModel = new DealLoanRepayModel();
        $result = $LoanRepayModel->getUserSummary($uid);

        $data['load_repay_money'] = 0;
        $data['load_earnings'] = 0;
        $data['load_tq_impose'] = 0;
        $data['load_yq_impose'] = 0;
        $data['norepay_principal'] = 0;
        $data['norepay_interest'] = 0;
        $data['js_norepay_principal'] = 0;
        $data['js_norepay_earnings'] = 0;
        $data['js_total_earnings'] = 0;
        //@TODO 初始化用户信息有没有坑

        foreach ($result as $v) {
            if (in_array($v['type'], array(1,2,8,9)) && ($v['status'] == 1)) {
                $data['load_repay_money'] = bcadd($data['load_repay_money'], $v['m'], 2);
            }
            if (in_array($v['type'], array(2,7,9)) && ($v['status'] == 1)) {
                $data['load_earnings'] = bcadd($data['load_earnings'], $v['m'], 2);
            }
            if (in_array($v['type'], array(4)) && ($v['status'] == 1)) {
                $data['load_tq_impose'] = bcadd($data['load_tq_impose'], $v['m'], 2);
            }
            if (in_array($v['type'], array(5)) && ($v['status'] == 1)) {
                $data['load_yq_impose'] = bcadd($data['load_yq_impose'], $v['m'], 2);
            }
            if (in_array($v['type'], array(1,8)) && ($v['status'] == 0)) {
                $data['norepay_principal'] = bcadd($data['norepay_principal'], $v['m'], 2);
            }
            if (in_array($v['type'], array(2,9)) && ($v['status'] == 0)) {
                $data['norepay_interest'] = bcadd($data['norepay_interest'], $v['m'], 2);
            }
        }
        return $data;
    }

    /**
     * 更新用户多投宝资产信息
     * @param $uid
     * @param $moneyInfo
     * @return bool|resource
     */
    public function updateUserDtAsset($uid,$moneyInfo) {
        $time = time();
        if(!$this->isExistsUser($uid)) {
            $this->initUserAssets($uid);
        }
        $fields = array(
            'dt_norepay_principal' => isset($moneyInfo['dt_norepay_principal']) ? $moneyInfo['dt_norepay_principal'] : 0,
            'dt_repay_interest' => isset($moneyInfo['dt_repay_interest']) ? $moneyInfo['dt_repay_interest'] : 0,
            'dt_load_money' => isset($moneyInfo['dt_load_money']) ? $moneyInfo['dt_load_money'] : 0,
        );

        $sql = "update firstp2p_user_loan_repay_statistics set dt_norepay_principal=dt_norepay_principal+".$fields['dt_norepay_principal'];
        $sql.=" ,dt_repay_interest=dt_repay_interest+".$fields['dt_repay_interest'];
        $sql.=" ,dt_load_money=dt_load_money+".$fields['dt_load_money'];
        $sql.=" ,update_time=".$time." where user_id=".$uid;
        return $this->execute($sql);
    }
}
