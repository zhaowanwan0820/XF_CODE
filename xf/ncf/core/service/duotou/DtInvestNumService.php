<?php
/**
 * DtInvestNumService.php
 * 用户投资次数服务
 * @date 2017年8月18日
 * @author duxuefeng <duxuefeng@ucfgroup.com>
 */
namespace core\service\duotou;

use core\service\duotou\DuotouService;

class DtInvestNumService extends DuotouService{

    /**
     * 是否今日智多新首投
     * @param unknown $userId 用户Id
     * @return true：今天没有投资
     */
    public function isTodayFirstInvest($userId){
        $request = array(
            'userId' => $userId,
            'date' =>date("Y-m-d") ,
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getInvestNumByDate', $request));
        if(isset($response)){
            $count=$response['data'];
            if($count=='0'){
                return true;
            }
        }
        return false;
    }

    /**
     * 是否智多新首投
     * @param unknown $userId 用户Id
     * @return  true：至今没有投资
     */
    public function isFirstInvest($userId) {
        $request = array(
            'userId' => $userId
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getInvestNumByUserId', $request));
        if(isset($response)){
            $count=$response['data'];
            if($count=='0'){
                return true;
            }
        }
        return false;
    }

    /**
     * 智多新投资次数
     * @param unknown $userId 用户Id
     * @return  投资次数
     */
    public function getInvestNum($userId){
        $request = array(
            'userId' => $userId
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getInvestNumByUserId', $request));
        if(isset($response)){
            return $response['data'];
        }
        return false;
    }

    /**
     * 智多新获取用户进行中的投资数量
     * @param unknown $userId 用户Id
     * @return  投资次数
     */
    public function getUserOngoingLoanCount($userId){
        $request = array(
            'userId' => $userId
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getUserOngoingLoanCount', $request));
        if (empty($response)) {
            throw new \Exception('智多新服务异常');
        }
        return intval($response['data']);
    }
}
