<?php
/**
 * 智多新合同服务
 * @date 2018年10月18日18:14:04
 */

namespace core\service\contract;

use core\dao\ContractFilesWithNumModel;
use core\dao\UserReservationModel;

use core\service\ContractService;
use core\service\ContractPreService;
use core\service\contract\ContractTplIdentifierService;
use core\service\contract\ContractUtilsService;

use libs\utils\Logger;
use core\dao\FastDfsModel;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;


/**
 * 智多新合同服务
 */
class ContractDtService
{
    public $deal_type = ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER; // 103
    public $is_deal_no_cfca = true; // 为true是为了使用蓝章

    /**
     * 获取某个渲染过的智多新合同模板(调用之前必须在合同落库)
     * @param  int $contract_id 合同 id
     * @param  int $service_id 服务id(在智多新中应该是$user_id加入智多新的投资记录id)，类型由 service_type 决定
     * @param  int $service_type 服务类型 103:随心约-尊享
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
            if ($service_type != ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER) {
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
            if($service_type == ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER){
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
     * @param  int $service_type 服务类型 103:随心约-尊享
     * @return void | false
     */
    public function downloadTsa($contract_id, $service_id, $service_type)
    {
        $contract_info = self::getOneFetchedDtContract($contract_id, $service_id, $service_type);
        Logger::info(implode(' | ', array(__FILE__, __FUNCTION__, __LINE__, json_encode(func_get_args()), json_encode($contract_info['status']), '下载打戳合同')));
        if (empty($contract_info)) {
            return false;
        }
        if ($service_type != ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER) {
            return false;
        }
        // 为0代表 合同记录生成成功，但是还没打戳完成
        if ($contract_info['status'] == 0) {
            $file_name = $contract_info['number'] . ".pdf";
            $file_path = ROOT_PATH . 'runtime/' . $file_name;
            if (!file_exists($file_path)) {
                \FP::import("libs.tcpdf.tcpdf");
                \FP::import("libs.tcpdf.mkpdf");
                set_time_limit(300);
                $mkpdf = new \Mkpdf ();
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
            $dfs = new FastDfsModel();
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

}
