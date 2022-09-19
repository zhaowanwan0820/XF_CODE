<?php
/**
 * 标的还款计划
 *
 * @date 2018年8月9日14:46:30
 */

namespace task\apis\account;

use libs\web\Form;
use libs\utils\Page;
use libs\utils\Logger;
use core\service\deal\DealService;
use core\enum\DealEnum;
use task\lib\ApiAction;

class Refund extends ApiAction
{

    public function invoke()
    {

        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $user_id = intval($params['userId']);
        $status = intval($params['status']);
        // status为3，则是查已还清；statu为2则表示还款中
        // statua为2和3是为了在前端区分网信和普惠的。
        $page = intval($params['pageNum']) <= 0 ? 1 : intval($params['pageNum']); // 第p页
        $page_size = intval($params['pageSize']) <= 0 ? 10 : intval($params['pageSize']);
        // 检查参数
        if ($user_id <= 0) {
            $this->json_data = array();
            return;
        }

        $deal_status = 4;
        if ($status == 3) {
            $deal_status = 5;
        }

        $dealService = new DealService();
        $result = $dealService->getListByUid($user_id, $deal_status, array(($page - 1) * $page_size, $page_size), DealEnum::DEAL_TYPE_GENERAL);

        $ret = array(
            'status' => $status,
            'deal_list' => $result['list'],
            'count' => $result['count'],
        );
        $this->json_data = $ret;
    }

}
