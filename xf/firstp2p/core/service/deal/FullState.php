<?php
namespace core\service\deal;


use core\dao\JobsModel;
use core\service\CouponLogService;
use core\dao\DealContractModel;
use core\service\DealProjectService;
use core\service\partner\PartnerService;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\SendContractEvent;
use core\event\DealFullCheckerEvent;
use core\event\SendDealMsgEvent;

/**
 * FullState 
 * 满标状态进行的操作
 * 
 * @package 
 * @version $id$
 */
class FullState extends State{

    function work($sm) {
        $this->deal = $sm->getDeal();
        $deal_model = $sm->getDealModel();
        $this->deal['deal_status'] = 2;
        $deal_id = $this->deal['id'];

        //开始发邮件和短信等通知
        /*
        //send_full_failed_deal_message($deal,"full");
        $function = '\core\service\DealService::sendDealMessage';
        $param = array('deal_id' => $deal_id, 'type' => 'full');
        $res = JobsModel::instance()->addJob($function, $param);
         */

        $obj = new GTaskService();
        $event = new SendDealMsgEvent($deal_id, 'full');
        $res = $obj->doBackground($event, 3);
        if (!$res) {
            \logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id)));
        }

        //向p2p发送相关数据
        $deal_model->put_p2p_data($deal_id);
        //满标触发首尾标附加返利,由于邀请记录改为异步无法获取结果，且这个一直未启用过
        //$coupon_log_service = new CouponLogService();
        //$coupon_log_service->handleCouponExtraForDeal($deal_id);

        //满标向工单系统发送项目id和状态
        PartnerService::projectStatusChangedNotify($deal_id, 2);

        return $this->deal;
    }

}
?>
