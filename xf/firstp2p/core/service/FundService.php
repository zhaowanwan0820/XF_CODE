<?php
/**
 * FundService.php
 * 
 * @date 2014-09-30
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace core\service;

use libs\utils\Logger;
use libs\utils\Rpc;
use NCFGroup\Protos\FundGate\ProtoUser;
use core\dao\FundModel;
use core\dao\FundMoneyLogModel;

/**
 * FundService 
 * 
 * @uses BaseService
 * @package default
 */
class FundService extends BaseService {
    /**
     * getList
     * 获取基金列表
     *
     * @param mixed $offset
     * @param int $limit 
     * @access public
     * @return list
     */
    public function getList($offset,$limit=10){
        $data = array();
        $offset = intval($offset);
        $limit = intval($limit);
        if($offset<0){
            $offset = 0;
        }
        if($limit<=0){
            $limit = 10;
        }
        if($limit > 0){
            $fund_model = new FundModel();
            $list = $fund_model->getList($offset,$limit);
        }
        return $list;
    }

    /**
     * getInfo
     * 获取基金信息
     *
     * @param mixed $id
     * @access public
     * @return object
     */
    public function getInfo($id){
        if($id > 0){
            $fund_model = new FundModel();
            $ret = $fund_model->getInfo($id);
            return $ret;
        }
    }


    /**
     * 创建资金记录
     * @param array $data 映射fund_money_log表的数据集合
     * @return mixed <boolean|integer>
     */
    public function createMoneyLog($data)
    {
        return FundMoneyLogModel::instance()->insertData($data);
    }

    /**
     * 读取指定用户基金份额
     * @param integer $userId
     * @return decimal
     */
    public function getFundTotalAmount($userId)
    {
        if (intval($userId) <= 0)
        {
            return '0.00';
        }

        $response = array();
        try
        {
            $request = new ProtoUser();
            $request->setUserId(intval($userId));
            $rpcResponse = $this->requestFund('User', 'getFundTotalAmount', $request);
            if (!empty($rpcResponse) && is_object($rpcResponse)) {
                $response['totalAssets'] = $rpcResponse->getTotalFundAmount();
                $response['totalSpecial'] = $rpcResponse->getTotalSpecialFundAmount();
            }
        } catch(\Exception $e)
        {
            // 异常基金请求 返回
        }
        if (empty($response))
        {
            $response['totalAssets'] = 0.00;
            $response['totalSpecial'] = 0.00;
        }
        return $response;
    }


    /**
     * 判断用户是否有基金份额
     * @param integer $userId 用户Id
     * @return boolean true 有基金份额， false 没有基金份额
     */
    public function userHasFundAssets($userId)
    {
        $response = $this->getFundTotalAmount($userId);
        if (intval($response['totalAssets']) > 0)
        {
            return true;
        }
        return false;
    }

    /**
     * 请求基金rpc
     */
    public function requestFund($service, $method, $request ,$maxRetryTimes=3,$timeout=10,$connectTimeout=10)
    {
        $beginTime = microtime(true);
        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        Logger::info("[req]FundService.{$className}.{$method}:" . json_encode($request, JSON_UNESCAPED_UNICODE));

        try {
            $rpc = new Rpc('fundRpc');
            $response = $rpc->go($service,$method,$request,$maxRetryTimes,$timeout,$connectTimeout);
        } catch (\Exception $e) {
            $exceptionName = get_class($e);
            //\libs\utils\Alarm::push('fund_rpc_exception', $className.'_'.$method,
            //        'request: '.json_encode($request, JSON_UNESCAPED_UNICODE).',ename:' .$exceptionName. ',msg: '.$e->getMessage());
            Logger::error("FundService.$service.$method.$exceptionName:" . $e->getMessage());
            throw $e;
        }
        // TODO log response
        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($res == false) {
            //\libs\utils\Alarm::push('fund_rpc_exception', $className.'_'.$method,'request: '.'time-out');
        }
        $res = ($res == false) ? 'invalid response: ' . var_export($response, true) : mb_substr($res, 0, 1000);
        $elapsedTime = round(microtime(true) - $beginTime, 3);
        Logger::info("[resp][cost:{$elapsedTime}]FundService.{$className}.{$method}:" . $res);
        return $response;
    }

}
