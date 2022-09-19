<?php
/**
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018-06-14
 * Time: 19:05
 */
namespace core\service\dealqueue;

use core\dao\deal\DealModel;
use core\service\BaseService;
use core\dao\dealqueue\DealQueueModel;

class DealQueueService extends BaseService {

    /**
     * 将队列首标推到线上
     */
    function startFirstDeal() {
        // 每个有效队列中的队首标的
        $arr_deal_queue = DealQueueModel::instance()->getEffectQueues();
        foreach ($arr_deal_queue as $deal_queue) {
            $deal_id = $deal_queue['first_deal_id'];
            $deal = DealModel::instance()->find($deal_id);
            // 队列首标如果没有开始，要置为进行中
            if($deal['deal_status'] !=0 || !$deal_id){
                continue;
            }

            try{
                $deal_queue->startOneDeal($deal_id);
            }catch(\Exception $e){
                Logger::error("syn_dealing DealQueueModel startOneDeal fail. deal_queue_id: {$deal_queue['id']} deal_id: {$deal_id}  error:" . $e->getMessage());
                continue;
            }

        }
    }
}