<?php
/**
 * 多投宝已投项目p2p去向
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 * Date: 2015-12-14
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\service\ContractService;
use libs\utils\Rpc;
use core\service\ncfph\DealService as NcfphDealService;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class FinplanP2P extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'p' => array('filter' => 'int'),
            'loanId' => array('filter' => 'int'),
            'projectId' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $loanId = intval($params['loanId']);
        $projectId= intval($params['projectId']);
        $page = intval($params['p']);
        $page_size = 7;

        $ret = array();
        $rpc = new Rpc('duotouRpc');
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        if($page == 0) {
            $page = 1;
            //查询 待投本金
            $vars = array(
                'loanId' => $loanId,
            );
            $request->setVars($vars);
            $response = $rpc->go('NCFGroup\Duotou\Services\LoanMapping','getLoanNoMappingMoney',$request);
            $noMappingMoney = $response['data']['money'];
            $ret['noMappingMoney'] = $noMappingMoney;//待投本金
        }

        $vars = array(
            'pageNum' => $page,
            'pageSize' => $page_size,
            'loanId' => $loanId,
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$request);

        $count = $response['data']['totalNum'];
        $dealLoan = $response['data']['dealLoan'];
        $data = $response['data']['data'];
        $contractService = new ContractService();
        $list = array();

        foreach ($data as $item) {
            //p2p标的信息
            $p2pDealInfo =  DealModel::instance()->find($item['p2p_deal_id']);
            //拆分后主站如果读不到对应的标信息就通过接口去普惠取
            if(empty($p2pDealInfo)){
                $p2pDealInfo = NcfphDealService::getDeal($item['p2p_deal_id'],true,false);
            }
            $user = UserModel::instance()->find($p2pDealInfo['user_id']);

            //智多鑫标的层面数据
            $dtDealInfo = array();
            $dtDealInfo['name'] = $p2pDealInfo['name'];
            $dtDealInfo['money'] = $item['remain_money'];
            $dtDealInfo['p2p_deal_id'] = $item['p2p_deal_id'];
            $dtDealInfo['status'] = $dealLoan['status'];
            $dtDealInfo['repay_interest'] = $item['repay_interest']; //已到账收益
            $dtDealInfo['no_repay_interest'] = $item['no_repay_interest']; //未到账收益
            $dtDealInfo['project_id'] = $p2pDealInfo['project_id'];
            $dtDealInfo['loanUsername'] = $GLOBALS['user_info']['real_name'];
            $dtDealInfo['borrowUsername'] = $user['real_name'];

            //底层资产对应合同
            $contracts = array();
            foreach ($item['contracts'] as $contract) {
                $formatContract = array();
                $formatContract['money'] = $contract['money'];
                $formatContract['loanTime'] = date('Y-m-d H:i:s',$contract['time']);
                if($contract['redemption_user_id'] > 0) {//转让
                    $userRedeem = UserModel::instance()->find($contract['redemption_user_id']);
                    $formatContract['redeemUserName'] = $userRedeem['real_name'];
                    $formatContract['contractType'] = 1;
                    $contractType = 11;
                    $uniqueId = str_pad($contract['tableIndex'],2,0,STR_PAD_LEFT).str_pad($contract['id'],20,0,STR_PAD_LEFT);
                    $formatContract['contractNo'] = ContractService::createDtNumber($loanId,$uniqueId);
                } else {
                    $dealLoadId = $contract['p2p_load_id'];
                    $formatContract['contractType'] = 0;//借款
                    $formatContract['contractNo'] = ContractService::createDealNumber($contract['p2p_deal_id'],13,$GLOBALS['user_info']['id'],$dealLoadId);
                }
                $contracts[] = $formatContract;
            }
            $dtDealInfo['contracts'] = json_encode($contracts,JSON_UNESCAPED_UNICODE);
            $list[] = $dtDealInfo;
        }
        $ret['list'] = $list;

        $page_model = new \Page($count, $page_size); //初始化分页对象
        $pages = $page_model->show();
        $ret['page'] = preg_replace("(\/account\/FinplanP2P\?loanId=(.+?)&projectId=(.+?)&p=(.+?)')","javascript:;' onclick='showDetailPage($1,$2,$3,this)'",$pages);
        ajax_return($ret);
    }
}
