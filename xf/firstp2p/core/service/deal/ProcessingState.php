<?php
namespace core\service\deal;

use core\service\DealTagService;
use core\service\DealParamsConfService;
use core\service\DealProjectService;

use core\dao\DealExtModel;
use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\UserCarryModel;

use core\event\ProcessingDealEvent;
use core\service\partner\PartnerService;
use NCFGroup\Task\Services\TaskService AS GTaskService;

use libs\utils\Logger;

/**
 * ProcessingState 
 * 进行中状态的操作
 * 
 */
class ProcessingState extends State{

    function work($sm) {
        $this->deal = $sm->getDeal();
        $deal_model = $sm->getDealModel();
        $this->deal['deal_status'] = 1;
        $deal_id = $this->deal['id'];

        // 由于的运营的人比较懒，3个月以上的标的加一个TAG
        $dealTagService = new DealTagService();
        $res = $dealTagService->autoAddTags($deal_id, $this->deal);
        if (!$res) {
            \logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $res)));
            throw new \Exception('自动打标Tag失败');
        }

        // apply deal_params_conf to deal
        $params_conf_service = new DealParamsConfService();
        if (false === $params_conf_service->applyDealParamsConfByDealId($sm->getDealParamsConfId(), $deal_id)) {
            throw new \Exception('应用上标队列参数失败');
        }
        \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $res)));

        // 如果是专享1.75 就在上标的时候 进行项目业务状态变更
        $deal_pro_service = new DealProjectService();
        if ($deal_pro_service->isProjectEntrustZX($this->deal['project_id'])) {
            // 变更项目业务状态为募集中
            if (false === DealProjectModel::instance()->changeProjectStatus($this->deal['project_id'], DealProjectModel::$PROJECT_BUSINESS_STATUS['process'])) {
                throw new \Exception('项目业务状态变更失败');
            }
            \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, '成功：项目业务状态变更为募集中')));
        }

        // 如果是 收费后放款，就给借款人发短息
        $deal_ext_info = DealExtModel::instance()->getDealExtByDealId($deal_id);
        if ($deal_ext_info->loan_type == UserCarryModel::LOAN_AFTER_CHARGE) {
            $g_obj = new GTaskService();
            $event = new ProcessingDealEvent($deal_id);
            $g_obj->doBackground($event, 1);
            Logger::info(sprintf('add fee-msg-event success,deal_id:%d [%s:%s]', $deal_id, __FILE__, __LINE__));
        }

        //首标状态为进行中时向工单系统发送项目id和状态
        PartnerService::projectStatusChangedNotify($deal_id, 1);

        return $res;
    }
}
?>
