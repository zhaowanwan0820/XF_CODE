<?php
/**
 * 合同查看和下载
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Rpc;
use NCFGroup\Protos\Contract\RequestGetTplByName;

use core\service\ContractNewService;
use core\service\ContractPreService;
use core\service\ncfph\AccountService;


class DtContShow extends BaseAction {

    const DTB_CONT = 'TPL_DTB_INVEST_PROTOCAL';

    public function init() {

        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
                'tag' => array('filter' => 'string'),
                'ajax' => array('filter' => 'int'),
                'number' => array('filter' => 'string'),
                'ctype' => array('filter' => 'int'),
                'type' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }


    public function invoke() {
        $data = $this->form->data;

        $tag = trim($data['tag']);
        $number = trim($data['number']);
        $ajax = intval($data['ajax']);
        $ctype = isset($data['ctype'])?intval($data['ctype']):0;
        $user_info = $GLOBALS['user_info'];
        $type = (isset($data['type']) && $data['type'] == 0) ?$data['type'] : 1;

        if($number == '' || !in_array($tag, array('show','download'))){
            return self::download_return($ajax);
        }
        $contractPre = new ContractPreService();
        //解析合同信息

        if($type <> 0){
            $number = str_pad($number, 32, 0, STR_PAD_LEFT);
        }

        if($ctype == 1) {
            //1003 或 1004
            $dtDealId = intval(substr($number, 0, 8));
            $type = intval(substr($number, 8, 2));
            $contractType = intval(substr($number, 10, 2));
            $userId = intval(substr($number, 12, 10));
            $dtLoanId = intval(substr($number, 22, 10));//对应 多投库 duotou_deal_loan 的 id
            //这就是多投的咨询协议
            if (($type === 1) && ($contractType === 10)) {
                $rpc = new Rpc('duotouRpc');
                $request = new \NCFGroup\Protos\Duotou\RequestCommon();
                $vars = array(
                    'id' => $dtLoanId,
                );
                $request->setVars($vars);
                $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanById', $request);
                $money = $response['data']['money'];
                $result = $contractPre->getDtbContractInvest($dtDealId, $user_info['id'], $money, $number,$response['data']['createTime']);
            }
        }elseif($ctype == 2) {
            $p2pDealId = intval(substr($number, 0, 8));
            $dtLoanId = intval(substr($number, 22, 10));

            $rpc = new Rpc('duotouRpc');
            $request = new \NCFGroup\Protos\Duotou\RequestCommon();

            $varsLoan = array(
                'p2p_deal_id' => $p2pDealId,
                'dt_loan_id' => $dtLoanId,
            );

            $request->setVars($varsLoan);

            $response = $rpc->go('NCFGroup\Duotou\Services\MappingCollect', 'getOneCollectMapping', $request);

            $result = $contractPre->getLoanContractPre($p2pDealId,$response['data']['userId'],$response['data']['money'],$number,$response['data']['createTime']);
        }else{
            if($type == 0){
                $deal_load_id = intval(substr($number, -10));
                $accountService = new AccountService();
                $contract = $accountService->getContractByDealLoadId($deal_load_id);
                if (empty($contract)) {
                    return;
                } else {
                    $number = $contract['number'];
                    if($tag == 'download'){
                        $accountService->downContract($contract['id'],$contract['deal_id'],$user_info,true);
                        return true;
                    }else{
                        $contractInfo = $accountService->getContractContent($contract['id'],$contract['deal_id'],$user_info);
                        $result = $contractInfo['content'];
                    }
                }

/*  拆分之前的代码
                 $contract = $this->rpc->local('ContractInvokerService\getLoanContractByDealLoadId',array('remoter', $deal_load_id));
                if (empty($contract)) {
                    return;
                } else {
                    $number = $contract['number'];
                    if($tag == 'download'){
                        $this->rpc->local('ContractInvokerService\downloadTsa',array('filer', $contract['id'], $contract['deal_id']));
                        if (empty($ret)) {
                            $this->rpc->local('ContractInvokerService\download',array('filer', $contract['id'], $contract['deal_id']));
                        }
                        return true;
                    }else{
                        $contractInfo = $this->rpc->local('ContractInvokerService\getOneFetchedContract',array('viewer', $contract['id'], $contract['deal_id']));
                        $result = $contractInfo['content'];
                    }
                }
 */
            }else{
                $rpc = new Rpc('duotouRpc');
                $request = new \NCFGroup\Protos\Duotou\RequestCommon();
                $redemptionLoanId = intval(substr($number,15,8));
                $vars = array(
                    'id' => $redemptionLoanId,
                );
                $request->setVars($vars);
                $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanDetail',$request);

                $userId = $response['data']['dealLoan']['userId'];
                $contractType = intval(substr($number,0,2));
                $dtDealId = intval(substr($number,2,6));
                $p2pDealId = intval(substr($number,8,7));
                $rpc = new Rpc('duotouRpc');
                $request = new \NCFGroup\Protos\Duotou\RequestCommon();
                $dtLoanId = intval(substr($number,23,10));
                $vars = array(
                    'loanId' => $dtLoanId,
                );
                $request->setVars($vars);
                $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$request);

                if(is_array($response['data']['data']) && count($response['data']['data'])>0){
                    foreach($response['data']['data'] as $mapping){
                        foreach($mapping['contracts'] as $contract) {
                            if(($contract['redemption_user_id'] == $userId)&&($contract['p2p_deal_id'] == $p2pDealId)&&($contract['redemption_loan_id']==$redemptionLoanId)){
                                $money = $contract['money'];
                                $dtRecordId = $contract['id'];
                                $dtLoanId = $dtLoanId;
                                $time = $contract['create_time'];
                            }
                        }
                    }

                    if($contractType == 11){
                        $result = $contractPre->getDtbLoanTransfer($dtDealId,$user_info['id'],$userId,$p2pDealId,$money,$number,$time,$dtRecordId,$dtLoanId);
                    }
                }
            }
        }


        if($tag == 'show') {
            echo hide_message($result);
        } else if($tag == 'download'){
                $file_name = $number.".pdf";
                $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
                \FP::import("libs.tcpdf.tcpdf");
                \FP::import("libs.tcpdf.mkpdf");
                $mkpdf = new \Mkpdf ();
                $mkpdf->mk($file_path, $result);
                header ( "Content-type: application/pdf");
                header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
                header ( "Content-Length: " . filesize($file_path));
                echo readfile($file_path);
                @unlink($file_path);
        }
    }

    public static function download_return($ajax = 0){
        if($ajax == 0){
            echo '<script>window.parent.location.href="/404.html"</script>';
        }else{
            return app_redirect ('/404.html');
        }
        return false;
    }
}

