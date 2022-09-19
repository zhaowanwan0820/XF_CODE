<?php

/**
 * PtpDealQueueService.php
 * 
 * Filename: PtpDealQueueService.php
 * Descrition: 上标队列service
 * Author: yutao@ucfgroup.com
 * Date: 16-3-21 下午3:04
 */

namespace NCFGroup\Ptp\services;

use core\dao\DealQueueModel;
use core\dao\DealQueueInfoModel;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Ptp\daos\DealQueueDAO;
use core\service\DealService;
use NCFGroup\Common\Extensions\Base\Pageable;

class PtpDealQueueService extends ServiceBase {

    /**
     * 通过siteId获取上标队列列表
     * @param SimpleRequestBase $request
     * @return ResponseBase
     */
    public function getDealQueueList(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['siteId']);
        $condition = array('conditions' => 'siteId = :siteId:', 'bind' => array('siteId' => $params['siteId']),'order' => 'id desc');
        $dealQueueList = DealQueueDAO::getList($params['page'], $condition);
        $response = new ResponseBase();
        if (empty($dealQueueList)) {
            $response->rpcRes = RPCErrorCode::FAILD;
        } else {
            $response->rpcRes = RPCErrorCode::SUCCESS;
            $response->list = $dealQueueList;
        }
        return $response;
    }

    /**
     * 新增上标队列
     * @param SimpleRequestBase $request
     * @return ResponseBase
     */
    public function addDealQueue(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['siteId']);
        Assert::notEmpty($params['name']);

        //验证上标队列name唯一性
        $isNameExist = DealQueueDAO::isNameExist($params['name']);
        if ($isNameExist) {
            throw new \Exception('队列名称已经存在');
        }

        $insertId = DealQueueDAO::insertQueue($params['name'], $params['note'], $params['isEffect'], $params['siteId'], $params['time']);
        $response = new ResponseBase();
        if (intval($insertId) > 1) {
            $response->rpcRes = RPCErrorCode::SUCCESS;
        } else {
            $response->rpcRes = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * 更新上标队列
     * @param SimpleRequestBase $request
     * @return ResponseBase
     * @throws \Exception
     */
    public function updateDealQueue(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['id']);
        Assert::integer($params['siteId']);
        Assert::notEmpty($params['name']);

        //验证上标队列name唯一性
        $isNameExist = DealQueueDAO::isNameExist($params['name'], $params['id']);
        if ($isNameExist) {
            throw new \Exception('队列名称已经存在');
        }

        $updateRet = DealQueueDAO::updateQueue($params['id'], $params['name'], $params['note'], $params['isEffect'], $params['siteId'], $params['time']);
        $response = new ResponseBase();
        if ($updateRet == 1) {
            $response->rpcRes = RPCErrorCode::SUCCESS;
        } else {
            $response->rpcRes = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * 通过siteId获取上标队列列表
     * @param SimpleRequestBase $request
     * @return ResponseBase
     */
    public function getQueueDeals(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['siteId']);
        Assert::integer($params['queueId']);
        $response = new ResponseBase();
        $deals = (new DealService())->getDealListByQueueId($params['queueId'],$params['siteId']);
        $list = array();
        if ($deals) {
            foreach ($deals as $deal) {
                $deal["deal_queue_name"] = get_deal_queue($deal["id"]);
                $userinfo = get_user_info($deal["user_id"],true);
                $deal["real_name"] = $userinfo["real_name"];
                $deal["user_mobile"] = $userinfo["mobile"];
                $deal["deal_status_name"] = get_deal_status($deal["deal_status"]);
                $deal["real_status_name"] = get_deal_contract_status($deal["id"],'0');
                $deal["agency_name"] = get_deal_contract_sign_status($deal["id"],$deal['agency_id']);
                $deal["advisory_name"] = get_deal_contract_sign_status($deal["id"],$deal['advisory_id']);;
                $list[] = $deal;
            }
        }
        $response->list = $list;
        return $response;
    }
    /**
     * 通过siteId获取未在自动上标队列的标列表
     * @param SimpleRequestBase $request
     * @return ResponseBase
     */
    public function getAddQueueDeals(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['siteId']);;
        $response = new ResponseBase();
        $deals = (new DealService())->getAddQueueDealList($params['siteId']);;
        $list = array();
        if ($deals) {
            foreach ($deals as $deal) {
                $deal["deal_queue_name"] = get_deal_queue($deal["id"]);
                $userinfo = get_user_info($deal["user_id"],true);
                $deal["real_name"] = $userinfo["real_name"];
                $deal["user_mobile"] = $userinfo["mobile"];
                $deal["deal_status_name"] = get_deal_status($deal["deal_status"]);
                $deal["real_status_name"] = get_deal_contract_status($deal["id"],'0');
                $deal["agency_name"] = get_deal_contract_sign_status($deal["id"],$deal['agency_id']);
                $deal["advisory_name"] = get_deal_contract_sign_status($deal["id"],$deal['advisory_id']);;
                $list[] = $deal;
            }
        }
        $response->list = $list;
        return $response;
    }

    public function getQueueById(SimpleRequestBase $request){
        $params = $request->getParamArray();;
        Assert::integer($params['queueId']);
        $response = new ResponseBase();
        $Queue = (new DealService())->getQueueById($params['queueId']);
        $response->list = $Queue;
        return $response;
    }
    //把标的插入到队列
    public function inserDealToQueue(SimpleRequestBase $request){
        $params = $request->getParamArray();
        Assert::integer($params['queueId']);
        $response = new ResponseBase();
        if(!empty($params['ids'])){
            foreach($params['ids'] as $id)
                $Queue = (new DealService())->inserDealToQueue($id,$params['queueId']);
             if ($Queue == 0 ) {
                $response->rpcRes = RPCErrorCode::FAILD;
                 return $response;
             }
        }
        $response->rpcRes = RPCErrorCode::SUCCESS;
        return $response;
    }

    //删除队列中的标的
    public function deleteDealFromQueue(SimpleRequestBase $request){
        $params = $request->getParamArray();
        Assert::integer($params['queueId']);
        $response = new ResponseBase();
        if(!empty($params['ids'])){
            foreach($params['ids'] as $id)
                $Queue = (new DealService())->deleteDealToQueue($id,$params['queueId']);
                if ($Queue == 0 ) {
                    $response->rpcRes = RPCErrorCode::FAILD;
                    return $response;
                }
         }
         $response->rpcRes = RPCErrorCode::SUCCESS;
         return $response;
     }


    public function deleteQueue(SimpleRequestBase $request){
        $params = $request->getParamArray();
        Assert::integer($params['queueId']);

        $response = new ResponseBase();
        if ($params['getDealIds'] && $params['queueId'] > 0) {
            $info = (new DealQueueInfoModel())->getDealIdsByQueueId($params['queueId']);
            $response->dealIds = $info;
        }

        $Queue = (new DealQueueModel())->deleteQueue($params['queueId']);
        if($Queue){
            $response->rpcRes = RPCErrorCode::SUCCESS;
        }else{
            $response->rpcRes = RPCErrorCode::FAILD;
        }

        return $response;
    }


}
