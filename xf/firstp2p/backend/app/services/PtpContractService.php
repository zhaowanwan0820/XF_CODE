<?php

namespace NCFGroup\Ptp\services;

use core\dao\DealSiteModel;
use core\service\ContractService;
use core\service\ContractNewService;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\RequstDealContractpre;
use NCFGroup\Protos\Ptp\ResponseDealContractpre;
use core\service\ContractPreService;
use core\service\DealService;
use NCFGroup\Protos\Ptp\RequestDealsContract;
use NCFGroup\Protos\Ptp\ResponseDealsContract;
use NCFGroup\Ptp\daos\ContractDAO;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

use core\service\ContractInvokerService;

require_once APP_ROOT_PATH . "/openapi/lib/functions.php";

/**
 * DealService
 * 标相关service
 * @uses ServiceBase
 * @package default
 */
class PtpContractService extends ServiceBase {

    const CONT_LOAN = 1;//借款合同
    const CONT_GUARANT = 4;//保证合同
    const CONT_LENDER = 5;//出借人平台服务协议
    const CONT_ASSETS = 7;//资产收益权回购通知
    const CONT_ENTRUST = 8;//委托投资协议
    
    /**
     * 投资确认查看合同
     */
    public function contractPre(RequstDealContractpre $request){


        $dealId = $request->getId();
        $type = $request->getType();
        $userId = $request->getUserId();
        $money = $request->getMoney();

        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        $response = new ResponseDealContractpre();
        if (empty($deal) || $deal['deal_status'] != 1){
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 21003;
            return $response;
        }

        $tpl_id = $type;
        $fetched_contract = ContractInvokerService::getOneFetchedContractByTplId('viewer', $dealId, $tpl_id, $userId, $money);
        $response->setContent($fetched_contract['content']);

        $response->resCode = RPCErrorCode::SUCCESS;

        return $response;
    }
    /**
     * 获取已投项目，合同内容
     */
    public function getContract(RequestDealsContract $request){
        $id = $request->getId();
        $userId = $request->getUserId();
        $userName = $request->getUserName();
        $dealId = $request->getDealId();
        $contractService = new ContractService();
        $contractNewService = new ContractNewService();
        $contract = $contractNewService->showContract($id,$dealId);
        $response = new ResponseDealsContract();
        // 获取不到内容，但是content会返回
        if (empty($contract) || empty($contract['content'])){
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        // 检查合同所属人一致性
        $userContractInfo = array(
                        'id' => $userId,
                        'user_name' => $userName,
                        );
        $checkUserContract = $contractService->checkContractNew($contract, $userContractInfo);
        if (!$checkUserContract){
            if($contract['type'] <> 99){
                $response->errorCode = 23006;
                return $response;
            }
        }
        $contract['content'] = hide_message($contract['content']);
        $response->setContent((string) $contract['content']);
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;

    }

    /**
     * 通过siteId获取合同内容
     * @param SimpleRequestBase $request
     * @return ResponseBase
     */
    public function getContractList(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        $response = new ResponseBase();
        $condition = array('condition'=>array(),'bind'=>array());
        if(intval( $params["map"]['dealId']) > 0 ){
            $deals = (new DealService())->isExistSiteById($params["map"]['dealId']);
            if($deals[0]["site_id"] == $params["map"]['siteId']){
                $condition['condition'][]='dealId = :dealId:';
                $condition['bind']['dealId'] = $params["map"]['dealId'];
            }else{
                $response->list = null;
                return $response;
            }
        }else{
         if($params["map"]['siteId'] > 0 ){
                $dealIds = (new DealSiteModel())->getDealIdsBySiteId($params["map"]['siteId']);
                if(!empty($dealIds)){
                    $condition['condition'][]='dealId IN (' . implode(',', $dealIds) . ')';
                }else{
                    $response->list = null;
                    return $response;
                }
            }
        }
        if(isset($params["map"]['title']) && $params["map"]['title'] != ''){
            $condition['condition'][]='title = :title:';
            $condition['bind']['title'] = $params["map"]['title'];
        }
        if(isset($params["map"]['number']) && $params["map"]['number'] != ''){
            $condition['condition'][]='number = :number:';
            $condition['bind']['number'] = $params["map"]['number'];
        }
        if(isset($params["map"]['userId']) && intval($params["map"]['userId']) > 0){
            $condition['condition'][]='userId = :userId:';
            $condition['bind']['userId'] = $params["map"]['userId'];
        }
        if(isset($params["map"]['id']) && intval( $params["map"]['id']) > 0){
            $condition['condition'][]='id = :id:';
            $condition['bind']['id'] = $params["map"]['id'];
        }

        $conditions = array('conditions'=>join(' AND ', $condition['condition']), 'bind'=>$condition['bind'],'order' => 'id desc');
        $contractList = ContractDAO::getContractList($params['page'], $conditions);
        $list = array();
        if ($contractList["data"]) {
            foreach ($contractList["data"] as $val) {
                if($val["signTime"]){
                    $val["signDate"] = date("Y-m-d H:i:s",$val["signTime"]);
                }
                if($val["userId"] > 0){
                    $userinfo = get_user_info($val["userId"],true);
                    $val["userName"] = $userinfo["real_name"];
                }else{
                    $agencyinfo = get_agency_info($val["agencyId"]);
                    $val["userName"] = $agencyinfo["name"];
                }
                $deal = (new DealService())->getDeal($val["dealId"]);
                if ($val ['userId'] > 0 && $val['type'] != 3) {
                    $val ['usertype'] = $this->contract_character(2);
                    if ($val ['userId'] == $deal['userId']) {//借款人合同
                        $val ['usertype'] = $this->contract_character(1);
                    }
                } else { //保证人和担保公司
                    $val ['usertype'] = $this->contract_character(3);
                    //担保公司
                    if ($val['userId'] == 0 && $val['agencyId'] > 0) {
                        if($deal['advisory_id'] === $val['agencyId']){
                            $val ['usertype'] = $this->contract_character(5);
                        }else{
                            $val ['usertype'] = $this->contract_character(4);
                        }
                    }
                }
                $list[] = $val;
            }
        }
        $contractList["data"] = $list;
       if (empty($list)) {
            $response->rpcRes = RPCErrorCode::FAILD;
        } else {
            $response->rpcRes = RPCErrorCode::SUCCESS;
            $response->list = $contractList;
        }
        return $response;
    }
    /**
     * 合同所属用户角色显示
     */
    private function contract_character($role){
        $character = array(
            1 => array('role' => 1,'name' => '借款人'),
            2 => array('role' => 2,'name' => '出借人'),
            3 => array('role' => 3,'name' => '保证人'),
            4 => array('role' => 4,'name' => '担保公司'),
            5 => array('role' => 5,'name' => '资产管理方'),
        );
        return $character[$role];
    }
}
