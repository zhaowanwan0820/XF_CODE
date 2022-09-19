<?php
/**
 * 合同查看和下载
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\duotou\DuotouService;
use core\service\contract\ContractNewService;
use core\service\contract\ContractInvokerService;
use core\service\contract\ContractService;
use core\service\contract\ContractPreService;
use core\enum\contract\ContractEnum;
use core\enum\contract\ContractServiceEnum;
use libs\tcpdf\Mkpdf;

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

        $contractId = 0; // 合同id
        $dealId = 0;
        if($ctype == 1) {
            // 智多新-顾问协议
            $number = str_pad($number, ContractEnum::LENGTH_DT_CONSULT_NUMBER, 0, STR_PAD_LEFT);
            $numberInfo = ContractService::getInfoFromDtConsultNumber($number);
            $dtDealId = intval($numberInfo['dtDealId']);
            $type = intval($numberInfo['type']);
            $contractType = intval($numberInfo['contractType']);
            $userId = intval($numberInfo['userId']);
            $dtLoanId = intval($numberInfo['dtLoanId']);

            if (($type === 1) && ($contractType === 10)) {
                $request = array(
                    'id' => $dtLoanId,
                );

                // 通过 $dtLoanId  $user_info['id']  获取合同记录
                $contract = ContractService::getContractByLoadId($dtLoanId,0,0, ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT,false);
                $contractId = isset($contract[0]['id']) ? $contract[0]['id'] : 0;
                $dealId = isset($contract[0]['deal_id']) ? $contract[0]['deal_id'] : 0;
                $number = isset($contract[0]['number'])? $contract[0]['number']:$number;
                // contractId为空，则获取落库合同内容
                if(!empty($contractId)){
                    $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt',$contractId,$dtLoanId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $result = isset($contractInfo['content']) ? $contractInfo['content'] : '';
                }else{
                    $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanById', $request));
                    $money = $response['data']['money'];
                    $result = $contractPre->getDtbContractInvest($dtDealId, $user_info['id'], $money, $number,$response['data']['createTime']);
                }
            }
        }elseif($ctype == 2) {
            $p2pDealId = intval(substr($number, 0, 8));
            $dtLoanId = intval(substr($number, 22, 10));


            $requestLoan = array(
                'p2p_deal_id' => $p2pDealId,
                'dt_loan_id' => $dtLoanId,
            );

            $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\MappingCollect', 'getOneCollectMapping', $requestLoan));

            $result = $contractPre->getLoanContractPre($p2pDealId,$response['data']['userId'],$response['data']['money'],$number,$response['data']['createTime']);
        }else{
            if($type == 0){
                // 智多新-底层标的-借款合同
                $deal_load_id = intval(substr($number, -10));
                $contract = ContractInvokerService::getLoanContractByDealLoadId('remoter', $deal_load_id);
                if (empty($contract)) {
                    return;
                } else {
                    $number = $contract['number'];
                    if($tag == 'download'){
                        ContractInvokerService::downloadTsa('filer', $contract['id'], $contract['deal_id']);
                        if (empty($ret)) {
                            ContractInvokerService::download('filer', $contract['id'], $contract['deal_id']);
                        }
                        return true;
                    }else{
                        $contractInfo = ContractInvokerService::getOneFetchedContract('viewer', $contract['id'], $contract['deal_id']);
                        $result = $contractInfo['content'];
                    }
                }
            }else{
                // 智多新-债权转让协议
                $numberInfo = ContractService::getInfoFromDtNumber($number);
                $loanId = $numberInfo['loanId'];
                $duotouLoanMappingContractId = intval($numberInfo['duotouLoanMappingContractId']);

                // 通过lmcId获取唯一一条多投记录
                $dealRequest = array( 'loanId' => $loanId, 'lmcId'=> $duotouLoanMappingContractId);
                $response = DuotouService::callByObject(array('service' => 'NCFGroup\Duotou\Services\LoanMappingContract', 'method' => 'getByLoanId', 'args' => $dealRequest));

                if(is_array($response['data']) && count($response['data'])>0){
                    $money = bcdiv($response['data']['money'], 100, 2);
                    $dtRecordId = $response['data']['id'];
                    $time = $response['data']['create_time'];
                    $userId = $response['data']['redemption_user_id'];
                    $redemptionLoanId = $response['data']['redemption_loan_id'];
                    $p2pDealId = $response['data']['p2p_deal_id'];
                    $projectId = $response['data']['project_id'];

                    // 通过 dealId 和number查数据
                    $contract = ContractService::getContractByNumber($loanId,$number, ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $contractId =  isset($contract[0]['id']) ? $contract[0]['id'] : 0;
                    $dealId =  isset($contract[0]['deal_id']) ? $contract[0]['deal_id'] : 0;

                    // contractId为空，则获取落库合同内容
                    if(!empty($contractId)){
                        $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt',$contractId,$loanId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                        $result = isset($contractInfo['content']) ? $contractInfo['content'] : '';
                    }else{
                        $result = $contractPre->getDtbLoanTransfer($projectId,$user_info['id'],$userId,$p2pDealId,$money,$number,$time,$dtRecordId,$loanId);
                    }
                }
            }
        }


        if($tag == 'show') {
            echo hide_message($result);
        } else if($tag == 'download'){
            if(!empty($contractId)){
                // 下载打戳合同pdf
                ContractInvokerService::downloadTsa('dt',$contractId,$dealId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
            }else{
                $file_name = $number.".pdf";
                $file_path = APP_ROOT_PATH.'../runtime/'.$file_name;
                $mkpdf = new Mkpdf ();
                $mkpdf->mk($file_path, $result);
                header ( "Content-type: application/pdf");
                header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
                header ( "Content-Length: " . filesize($file_path));
                readfile($file_path);
                @unlink($file_path);
            }
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

