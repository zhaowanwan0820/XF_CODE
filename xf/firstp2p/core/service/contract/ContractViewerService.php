<?php
/**
 * 查看合同
 */

namespace core\service\contract;

use core\service\contract\ContractRenderService;
use core\service\contract\ContractUtilsService;

use NCFGroup\Protos\Contract\RequestGetContractById;
use NCFGroup\Protos\Contract\RequestGetTplById;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

use libs\utils\Logger;

class ContractViewerService
{
    /**
     * 获取渲染过的合同模板 [集合]
     * @param int $deal_id
     * @param int $user_id
     * @param float $money 用户要投 或者 投了 多少钱
     * @param boolen $is_seen_when_bid 是否取投资时才可见的模板
     * @return array 渲染后的合同列表 [['title', 'content']]
     */
    static public function getFetchedDealContractList($deal_id, $user_id, $money, $is_seen_when_bid = true)
    {
        try {
            if(empty($deal_id) || empty($user_id)){
                throw new \Exception(sprintf('参数有误，参数：%s', implode(' | ', func_get_args())));
            }

            $deal_id = intval($deal_id);
            $user_id = intval($user_id);
            $money = floatval($money);

            // 获取合同模板 list
            $contract_list = ContractRemoterService::getContractList($deal_id, $is_seen_when_bid);
            if (empty($contract_list)) {
                throw new \Exception('合同列表为空');
            } else {
                $notice = ContractRenderService::getNoticeInfo($deal_id, $user_id, $money);
                return array_map(function ($contract) use ($notice) {
                    return array('id' => $contract['id'], 'title' => $contract['title'], 'content' => ContractUtilsService::fetchContent($notice, $contract['content']), 'contract_type' => $contract['contract_type']);
                }, $contract_list);
            }

        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同列表失败，标id：%d，用户id：%d，失败原因：%s，file：%s, line:%s', $deal_id, $user_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 获取某个渲染过的合同模板
     * @param int $contract_id 合同 id
     * @param int $service_id 服务id，类型由 service_type 决定
     * @param int $service_type 服务类型 1:标的；2:项目
     * @param array $user_info 用户信息，如果不为空，则对合同的所属人进行校验 ['id', 'user_name']
     * @return array 渲染后的合同信息 [contract_*表信息 +  合同content]
     */
    static public function getOneFetchedContract($contract_id, $service_id, $service_type = 1, $user_info = array())
    {
        try {
            if(empty($contract_id) || empty($service_id)){
                throw new \Exception(sprintf('参数有误，参数：%s', implode(' | ', func_get_args())));
            }

            $contract_id = intval($contract_id);
            $service_id = intval($service_id);
            $service_type = intval($service_type);

            switch ($service_type) {
                case ContractServiceEnum::SERVICE_TYPE_DEAL:
                    $contract_info = ContractRemoterService::getContract($service_id, $contract_id);
                    $deal_id = $service_id;
                    break;
                case ContractServiceEnum::SERVICE_TYPE_PROJECT:
                    $contract_info = ContractRemoterService::getProjectContract($service_id, $contract_id);
                    $deal_id = $contract_info['deal_info']['id'];
                    break;
                default:
                    throw new \Exception(sprintf('服务类型有误，参数：%s', $service_type));
            }

            if (empty($contract_info)) {
                throw new \Exception('合同记录为空');
            } else {
                // 防刷：校验此份合同是否属于当前用户
                if (!empty($user_info) && !ContractUtilsService::checkContractOwnership($contract_info, $user_info)) {
                    throw new \Exception('此合同不属于当前用户');
                }

                $notice = ContractRenderService::getNoticeInfo($deal_id, $contract_info['userId'], 0, $contract_info, $service_type);
                $contract_info['content'] = ContractUtilsService::fetchContent($notice, $contract_info['content']);
            }

            return $contract_info;
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同信息失败，合同id：%d，服务id：%d，服务类型：%d，失败原因：%s，file：%s, line:%s', $contract_id, $service_id, $service_type, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据模板的id 渲染某个合同
     * @param int $deal_id
     * @param int $tpl_id 合同模板 id
     * @param int $user_id 当前用户id
     * @param int $money 用户要投多少钱
     * @return array 渲染后的合同信息 [title, content]
     */
    static public function getOneFetchedContractByTplId($deal_id, $tpl_id, $user_id, $money)
    {
        try {
            if (empty($tpl_id) || empty($user_id)) {
                throw new \Exception(sprintf('参数有误，参数：%s', implode(' | ', func_get_args())));
            }

            $deal_id = intval($deal_id);
            $tpl_id = intval($tpl_id);
            $user_id = intval($user_id);

            $contract_info = ContractRemoterService::getContractTplById($tpl_id);
            if (empty($contract_info)) {
                throw new \Exception('合同模板不存在');
            } else {
                $notice = ContractRenderService::getNoticeInfo($deal_id, $user_id, $money);
                $contract_info['content'] = ContractUtilsService::fetchContent($notice, $contract_info['content']);
            }

            return array('title' => $contract_info['contractTitle'], 'content' => $contract_info['content']);
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同模板失败，合同模板id：%d，失败原因：%s，file：%s, line:%s', $tpl_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

    /**
     * 根据模板的id 渲染某个合同
     * @param int $contract_id contract_before_borrow表的id
     * @param int $tpl_id 合同模板 id
     * @return array 渲染后的合同信息 [title, content]
     */
    static public function getOneFetchedBeforeContractByTplId($contractId, $tplId)
    {
        try {
            if (empty($contractId) || empty($tplId)) {
                throw new \Exception(sprintf('参数有误，参数：%s', implode(' | ', func_get_args())));
            }

            $contractRequest = new RequestGetContractById();
            $contractRequest->setId(intval($contractId));
            $contractResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\ContractBeforeBorrow", "getContractById", $contractRequest);
            if ($contractResponse['errCode'] != 0 ) {
                throw new \Exception('合同记录不存在');
            }
            $tplRequest = new RequestGetTplById();

            $tplRequest->setId(intval($tplId));
            $tplResponse = ContractUtilsService::callRemote("\NCFGroup\Contract\Services\Tpl", "getTplById", $tplRequest);
            if ($tplResponse->errCode != 0 ) {
                throw new \Exception('合同模板不存在');
            }
            $tplData= $tplResponse->getList();
            if($tplData['data']['contractCid'] != $contractResponse['data']['categoryId']){
                throw new \Exception('合同分类不匹配');
            }
            $notice = ContractRenderService::getBeforeBorrowNotice($contractResponse['data']);

            $tpl  = array();
            $tpl['title'] = $tplData['data']['contractTitle'];
            $tpl['content'] = ContractUtilsService::fetchContent($notice, $tplData['data']['content']);
            return $tpl;
        } catch (\Exception $e) {
            Logger::error(sprintf('获取合同模板失败，合同模板id：%d，失败原因：%s，file：%s, line:%s', $tpl_id, $e->getMessage(), __FILE__, __LINE__));
            return array();
        }
    }

}
