<?php
/**
 * 智多新合同服务
 * @date 2018年10月18日18:14:04
 */

namespace core\service\contract;

use core\dao\deal\DealSiteModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealExtModel;
use core\dao\project\DealProjectModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealAgencyModel;
use core\dao\contract\ContractFilesWithNumModel;
use core\dao\reserve\UserReservationModel;

use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\deal\DealAgencyService;
use core\service\deal\EarningService;
use core\service\contract\ContractService;
use core\service\contract\ContractTplIdentifierService;
use core\service\duotou\DuotouService;

use libs\utils\XDateTime;
use libs\utils\Finance;
use libs\utils\Logger;
use libs\fastdfs\FastDfsService;
use libs\tcpdf\Mkpdf;

use core\enum\DealLoanTypeEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractTplIdentifierEnum;


/**
 * 智多新合同服务
 */
class ContractDtService
{
    public $deal_type = 0;

    /**
     * 获取某个渲染过的智多新合同模板(调用之前必须在合同落库)
     * @param  int $contract_id 合同 id
     * @param  int $service_id 服务id(在智多新中应该是$user_id加入智多新的投资记录id)，类型由 service_type 决定
     * @param  int $service_type 服务类型 101:智多新 102:随心约
     * @return array 渲染后的合同信息 [contract_*表信息 +  合同content]
     */
    public static function getOneFetchedDtContract($contract_id, $service_id,$service_type)
    {
        $logParams = "contract_id:{$contract_id}, service_id:{$service_id}";
        try {
            $contract_id = intval($contract_id);
            $service_id = intval($service_id);
            if (empty($contract_id) || empty($service_id)) {
                throw new \Exception('参数有误');
            }
            if (!in_array($service_type, [ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT, ContractServiceEnum::SOURCE_TYPE_RESERVATION])) {
                throw new \Exception('服务类型有误');
            }
            $contract_info = ContractService::getContractByCid($service_id, $contract_id, $service_type);
            $contract_info = isset($contract_info[0]) ? $contract_info[0] : array();
            if (empty($contract_info)) {
                throw new \Exception('合同记录为空');
            }
            // tpl_indentifier_info
            $contract_info['tpl_indentifier_info'] = ContractTplIdentifierService::getTplIdentifierInfoById($contract_info['tpl_identifier_id']);
            $tplIdentifierName = trim($contract_info['tpl_indentifier_info']['name']);
            if (empty($tplIdentifierName)) {
                throw new \Exception('合同标识为空');
            }
            if($service_type == ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT){
                $contractParams = self::getDtContractParams($contract_info['borrow_user_id'], $contract_info['deal_id'], $contract_info['user_id'], $contract_info['deal_load_id'], $contract_info['number']);
                $preService = new ContractPreService();
                switch ($tplIdentifierName) {
                    case ContractTplIdentifierEnum::DTB_CONT :
                        // 智多新 投资顾问协议
                        $contract_info['content'] = $preService->getDtbContractInvest($contractParams['deal_id'], $contractParams['user_id'],
                            $contractParams['money'], $contractParams['num'], $contractParams['create_time']);
                        break;
                    case ContractTplIdentifierEnum::DTB_TRANSFER :
                        // 智多新 债权转让协议
                        $contract_info['content'] = $preService->getDtbLoanTransfer($contractParams['deal_id'], $contractParams['user_id'],
                            $contractParams['transfer_uid'], $contractParams['p2p_deal_id'], $contractParams['money'], $contractParams['num'],
                            $contractParams['create_time'], $contractParams['dtRecordId'], $contractParams['dtLoanId']);
                        break;
                    default:
                        throw new \Exception('服务类型有误');
                }
            } else {
                $contractParams = self::getReservationContractParams($contract_info['deal_id']);
                Logger::info(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,$logParams,json_encode($contractParams),'随心约')));
                $contract_info['content'] = ContractPreService::getReservationContract($contractParams['user_id'], $contractParams['money'],
                    $contractParams['invest_deadline'], $contractParams['invest_deadline_unit'], $contractParams['invest_rate'],
                    $contractParams['start_time'],$contract_info['number']);
            }
            return $contract_info;
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，参数：%s，失败原因：%s，file：%s, line:%s', json_encode($logParams), $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取某个渲染过的智多新合同
     * @param integer $user_id 智多新用户 受托用户
     * @param integer $deal_id 实际上存的是智多新用户投资记录id loanId 受托用户
     * @param integer $borrow_user_id 智多新用户 转让用户
     * @param integer $deal_load_id 智多新用户投资记录id redemptionLoanId 转让用户
     * @param string $number 合同编号
     * @return strig 渲染后的合同信息
     */
    public static function getOneFetchedDtContractContent($user_id, $deal_id, $borrow_user_id, $deal_load_id, $number, $ctype, $type)
    {
        $logParams = "contract_id:{$user_id}, service_id:{$deal_id} borrow_user_id:{$borrow_user_id} deal_load_id:{$deal_load_id} number:{$number}";
        try {
            $contractContent = '';
            $contractParams = self::getDtContractParams($user_id, $deal_id, $borrow_user_id, $deal_load_id, $number);
            $preService = new ContractPreService();
            if ($ctype == 1) {
                // 智多新-顾问协议
                $contractContent = $preService->getDtbContractInvest($contractParams['deal_id'], $contractParams['user_id'],
                    $contractParams['money'], $contractParams['num'], $contractParams['create_time']);
            } else {
                if ($type == 0) {
                    // 智多新-底层标的-借款合同
                    $contract = ContractInvokerService::getLoanContractByDealLoadId('remoter', $deal_load_id);
                    $contractInfo = ContractInvokerService::getOneFetchedContract('viewer', $contract['id'], $contract['deal_id']);
                    $contractContent = $contractInfo['content'];
                } elseif ($type == 1) {
                    // 智多新-债权转让协议
                    $contractContent = $preService->getDtbLoanTransfer($contractParams['deal_id'], $contractParams['user_id'],
                        $contractParams['transfer_uid'], $contractParams['p2p_deal_id'], $contractParams['money'], $contractParams['num'],
                        $contractParams['create_time'], $contractParams['dtRecordId'], $contractParams['dtLoanId']);
                } else {
                    throw new \Exception('服务类型有误 参数：' . $logParams);

                }
            }
            return $contractContent;
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，参数：%s，失败原因：%s，file：%s, line:%s', json_encode($logParams), $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }


    /**
     * 获取智多新合同预览参数
     *
     * @param integer $user_id 智多新用户 受托用户
     * @param integer $deal_id 实际上存的是智多新用户投资记录id loanId 受托用户
     * @param integer $borrow_user_id 智多新用户 转让用户
     * @param integer $deal_load_id 智多新用户投资记录id redemptionLoanId 转让用户
     * @param string $number 合同编号
     * @return array 合同参数
     *      int $deal_id 智多鑫项目id 1004 用于获取合同模板
     *      int $user_id 受让方用户id
     *      int $transfer_uid 债转方用户id
     *      int $p2p_deal_id 底层p2p标的id
     *      int $money 债转金额
     *      string $num 合同编号
     *      int $create_time 签署时间
     *      int $loanId 加入智多鑫 投资记录id ($dtRecordId和$dtLoanId可以知道 firstp2p_deal_load的id)
     *      int $redemptionLoanId   债转人  投资记录id
     */
    public static function getDtContractParams($user_id, $deal_id, $borrow_user_id, $deal_load_id, $number)
    {
        Logger::info(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,json_encode(func_get_args()),'test--1234')));
        //顾问协议
        $p2p_deal_id = 0;
        $dtRecordId = 0;
        $create_time = 0;
        if ($deal_load_id == 0) {

            $dealRequest = array('id' => $deal_id);
            $response = DuotouService::callByObject(array('service' => 'NCFGroup\Duotou\Services\DealLoan', 'method' => 'getDealLoanById', 'args' => $dealRequest));
            \libs\utils\Logger::info(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,' test--1234 ',json_encode(func_get_args()),json_encode($response))));
            if (!$response) {
                throw new \Exception("调用多投服务失败");
            }
            if (empty($response['data'])) {
                throw new \Exception("投资记录不存在");
            }
            $create_time = $response['data']['createTime'];
            $money = $response['data']['money'];
            $projectId = $response['data']['projectId'];
        } else {//债转协议
            $numberInfo = ContractService::getInfoFromDtNumber($number);
            if(empty($numberInfo['duotouLoanMappingContractId'])){
                throw new \Exception("duotouLoanMappingContractI为空");
            }
            // 通过$numberInfo['duotouLoanMappingContractId']获取多投记录
            $dealRequest = array('redemptionLoanId' => $deal_load_id, 'loanId' => $deal_id, 'lmcId'=>intval($numberInfo['duotouLoanMappingContractId']));
            $response = DuotouService::callByObject(array('service' => 'NCFGroup\Duotou\Services\LoanMappingContract', 'method' => 'getByLoanId', 'args' => $dealRequest));
            if (!$response) {
                throw new \Exception("调用多投服务失败");
            }
            if (empty($response['data'])) {
                throw new \Exception("投资记录不存在");
            }

            $redemption_user_id = $response['data']['redemption_user_id'];
            if ($redemption_user_id != $borrow_user_id) {
                throw new \Exception("出让方用户id不匹配");
            }
            $money = bcdiv($response['data']['money'], 100, 2);
            $create_time = $response['data']['create_time'];
            $p2p_deal_id = $response['data']['p2p_deal_id'];
            $dtRecordId = $response['data']['id'];
            $projectId = $response['data']['project_id'];
        }
        \libs\utils\Logger::info(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,' test--1234 ',json_encode(func_get_args()),json_encode($response))));
        return array(
            'deal_id' => $projectId,
            'user_id' => $user_id,
            'transfer_uid' => $borrow_user_id,
            'p2p_deal_id' => $p2p_deal_id,
            'money' => $money,
            'num' => $number,
            'create_time' => $create_time,
            'dtRecordId' => $dtRecordId,
            'dtLoanId' => $deal_id,
        );
    }

    /**
     * 根据随心约预约id获取getReservationContract方法所需要的输入参数
     * @param int $reservationId
     * @return array $result 参数数组
     */
    public static function getReservationContractParams($reservationId)
    {
        $result = array(
            'user_id' => 0,
            'money' => 0,
            'invest_deadline' => 0,
            'invest_deadline_unit' => 0,
            'invest_rate' => 0,
            'start_time' => 0,
        );
        try {
            $reservationId = intval($reservationId);
            if (empty($reservationId)) {
                throw new \Exception('reservationId为空');
            }
            $reservation = UserReservationModel::instance()->getReservationById($reservationId);
            if (empty($reservation)) {
                throw new \Exception('找不到预约记录');
            }
            $result['user_id'] = $reservation['user_id'];
            $result['money'] = bcdiv($reservation['reserve_amount'], 100, 2);
            $result['invest_deadline'] = $reservation['invest_deadline'];
            $result['invest_deadline_unit'] = $reservation['invest_deadline_unit'];
            $result['invest_rate'] = $reservation['invest_rate'];
            $result['start_time'] = $reservation['start_time'];
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,'预约id:'.$reservationId,$e->getMessage())));
        }
        return $result;
    }

    /**
     * 下载 盖戳之后的合同
     * @param int $contract_id
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param  int $service_type 服务类型 101:智多新 102:随心约
     * @return void | false
     */
    public function downloadTsa($contract_id, $service_id, $service_type)
    {
        $contract_info = self::getOneFetchedDtContract($contract_id, $service_id, $service_type);
        Logger::info(implode(' | ', array(__FILE__, __FUNCTION__, __LINE__, json_encode(func_get_args()), json_encode($contract_info['status']), '下载打戳合同')));
        if (empty($contract_info)) {
            return false;
        }
        if (!in_array($service_type, [ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT, ContractServiceEnum::SOURCE_TYPE_RESERVATION])) {
            return false;
        }
        // 为0代表 合同记录生成成功，但是还没打戳完成
        if ($contract_info['status'] == 0) {
            $file_name = $contract_info['number'] . ".pdf";
            $file_path = ROOT_PATH . 'runtime/' . $file_name;
            if (!file_exists($file_path)) {
                set_time_limit(300);
                $mkpdf = new Mkpdf ();
                $mkpdf->mk($file_path, $contract_info['content']);
            }
            header("Content-type: application/pdf");
            header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
            header("Content-Length: " . filesize($file_path));
            readfile($file_path);
            @unlink($file_path);
            exit;
        }
        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contract_info['number'], $service_type);
        Logger::info(implode(' | ', array(__FILE__, __FUNCTION__, __LINE__, json_encode(func_get_args()), json_encode($ret['0']), '下载打戳合同')));
        if (!empty($ret) && !empty($ret[0])) {
            $fileInfo = $ret[0];
            $dfs = new FastDfsService();
            $fileContent = $dfs->readTobuff($fileInfo['group_id'], $fileInfo['path']);
            if (!empty($fileContent)) {
                header("Content-type: application/octet-stream");
                header('Content-Disposition: attachment; filename="' . $contract_info['number'] . '.pdf"');
                echo $fileContent;
                exit;
            } else {
                ContractUtilsService::writeSignLog(sprintf('signed contract file is lost [contractId:%d]', $contract_id));
                return false;
            }
        } else {
            // 如果记录表中没有信息则
            ContractUtilsService::writeSignLog(sprintf('contract file is signing [contractId:%d]', $contract_id));
            return false;
        }
    }

    /**
     * 下载 老版智多新 未盖戳合同
     * @param int $contract_id
     * @param int $service_id 服务id
     * @return void | false
     */
    public function download($user_id, $deal_id, $borrow_user_id, $deal_load_id, $number, $ctype, $type)
    {
        $contractContent = self::getOneFetchedDtContractContent($user_id, $deal_id, $borrow_user_id, $deal_load_id, $number, $ctype, $type);

        if (empty($contract_info)) {
            return false;
        }
        $file_name = $number . ".pdf";
        $file_path = ROOT_PATH . 'runtime/' . $file_name;
        if (!file_exists($file_path)) {
            set_time_limit(300);
            $mkpdf = new Mkpdf ();
            $mkpdf->mk($file_path, $contractContent);
        }
        header("Content-type: application/pdf");
        header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
        header("Content-Length: " . filesize($file_path));
        readfile($file_path);
        @unlink($file_path);
        exit;

    }
}
