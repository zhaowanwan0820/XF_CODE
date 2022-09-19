<?php
/**
 * sort_num 数据同步脚本
 * 由于之前，队列内标的依据为 deal_queue 表中的 deal_id_queue 字段；
 * 现在，队列内标的的依据为 deal_queue_info 表，且以此表中的 sort_num 为排序依据；
 * 所以，需要将原 deal_id_queue 中的标的顺序，同步到对应队列的 sort_num 字段。
 */

require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\DealQueueModel;
use core\dao\DealQueueInfoModel;
use libs\utils\Logger;

/**
 * 执行入口
 * @params array $queue_id_arr 要同步的队列 id，若没有，则同步所有队列
 */
function main($queue_id_arr = array())
{
    if (empty($queue_id_arr) || !is_array($queue_id_arr)) {
        $cond = '';
    } else {
        $cond = sprintf('`id` in (%s)', implode(',', $queue_id_arr));
    }
    $queue_arr = DealQueueModel::instance()->findAll($cond);

    foreach ($queue_arr as $queue) {
        synSortNumOfOneQueue($queue);
    }
}

/**
 * 对某个队列进行同步
 * @params obj | array $queue 要同步的队列
 * @return boolen
 */
function synSortNumOfOneQueue($queue)
{
    try {
        $queue_id = $queue['id'];
        $deal_id_arr = empty($queue['deal_id_queue']) ? array() : explode(',', $queue['deal_id_queue']);

        $GLOBALS['db']->startTrans();
        $sort_num = 0;
        foreach ($deal_id_arr as $deal_id) {
            $cond = sprintf('`queue_id` = %d AND `deal_id` = %d', $queue_id, $deal_id);
            $deal_in_queue = DealQueueInfoModel::instance()->findBy($cond);
            if (empty($deal_in_queue)) {
                $deal_in_queue = new DealQueueInfoModel();
                $deal_in_queue->queue_id = $queue_id;
                $deal_in_queue->deal_id = $deal_id;
                $deal_in_queue->sort_num = $sort_num++;
            } else {
                $deal_in_queue->sort_num = $sort_num++;
            }
            if (false === $deal_in_queue->save()) {
                throw new \Exception('同步失败， queue_id:' . $queue_id . '; deal_id:' . $deal_id);
            }
        }
        $GLOBALS['db']->commit();
        Logger::info(sprintf('[clean_deal_queue.php] queue_id: %d; status: success', $queue_id));
        return true;
    } catch (\Exception $e) {
        $GLOBALS['db']->rollback();
        Logger::info(sprintf('[clean_deal_queue.php] queue_id: %d; deal_id: %d; status: fail', $queue_id, $deal_id));
        return false;
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

unset($argv[0]);
main($argv);
Logger::info('[clean_deal_queue.php] end');
