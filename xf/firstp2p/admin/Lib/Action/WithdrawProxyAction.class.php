<?php

/**
 * Class WithdrawProxyAction
 *
 */
use core\dao\WithdrawProxyModel;
use core\dao\WithdrawProxyCheckModel;
use core\service\WithdrawProxyService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\StandardApi as Api;
use NCFGroup\Common\Library\services\UcfpayGateway;


class WithdrawProxyAction extends CommonAction
{
    public function index()
    {
        $map = $this->_getMap();
        $this->assign("default_map", $map);
        parent::index();
    }

    /**
     * 代发失败重新代发界面
     */
    public function fail()
    {
        $_REQUEST['order_status'] = 3;
        $map = $this->_getMap();
        parent::index();
    }

    public function viewProjectProgress()
    {
        $id = trim($_REQUEST['id']);
        $withdrawProxyInfo = WithdrawProxyModel::instance()->find($id);
        if (!$withdrawProxyInfo)
        {
            return $this->show_error('代发订单不存在');
        }
        $summary = WithdrawProxyService::getSummaryByMerchantNo($withdrawProxyInfo->merchant_batch_no);
        $this->assign('info', $withdrawProxyInfo->getRow());
        $this->assign('summary', $summary);
        $this->display();
    }

    /**
     * 查看代发记录明细
     */
    public function viewDetail()
    {
        $id = intval($_REQUEST['id']);
        $withdrawProxyInfo = WithdrawProxyModel::instance()->find($id);
        if (!$withdrawProxyInfo)
        {
            return $this->show_error('代发订单不存在');
        }
        $withdrawInfo = $withdrawProxyInfo->getRow();
        $res = WithdrawProxyService::isMerchantOrderSuccess($withdrawInfo);
        $withdrawInfo['order_status_cn'] = $this->showStatus($withdrawInfo['order_status']);
        $withdrawInfo['update_time_format'] = $withdrawInfo['update_time'] ? date('Y-m-d H:i:s', $withdrawInfo['update_time']) : '-';
        $withdrawInfo['create_time_format'] = $withdrawInfo['create_time'] ? date('Y-m-d H:i:s', $withdrawInfo['create_time']) : '-';
        $withdrawInfo['next_retry_time_format'] = $withdrawInfo['next_retry_time'] ? date('Y-m-d H:i:s', $withdrawInfo['next_retry_time']) : '-';
        $withdrawInfo['next_notify_time_format'] = $withdrawInfo['next_notify_time'] ? date('Y-m-d H:i:s', $withdrawInfo['next_notify_time']) : '-';
        $withdrawInfo['total_redo_times'] = WithdrawProxyModel::countRedoTimes($withdrawInfo['merchant_no'], $withdrawInfo['merchant_no_seq']);
        $withdrawInfo['biz_type_cn'] = WithdrawProxyModel::$bizTypeDesc[$withdrawInfo['biz_type']];
        $withdrawInfo['amount_format'] = number_format(bcdiv($withdrawInfo['amount'], 100, 2), 2);
        $withdrawInfo['account_no_format'] = bankNoFormat($withdrawInfo['account_no']);
        $withdrawInfo['bank_issuer_cn'] = isset($withdrawinfo['bank_issuer']) ? $withdrawInfo['bank_issuer'] : '-';
        $withdrawInfo['user_type_cn'] = WithdrawProxyModel::$userTypeDesc[$withdrawInfo['user_type']];
        $withdrawInfo['amount_format'] = number_format(bcdiv($withdrawInfo['amount'], 100, 2), 2);
        $withdrawInfo['merchant_no_result'] = $res ? '全部成功' : '未全成功';
        $this->assign('withdrawInfo', $withdrawInfo);
        $this->display();
    }

    /**
     * 查询代发商户可用金额
     */
    public function viewMerchantMoney()
    {
        $merchantId = trim($_REQUEST['merchantId']);
        if (empty($merchantId))
        {
            $this->show_error('代发商户号为空');
            return;
        }
        $api = Api::instance(Api::UCFPAY_GATEWAY);
        $result = $api->request(UcfpayGateway::SERVICE_MER_BALANCE, ['merchantId' => $merchantId]);
        $result['available'] = number_format(bcdiv($result['available'], 100, 2),2);
        $result['freezeAmount'] = number_format(bcdiv($result['freezeAmount'], 100, 2),2);
        $result['balance'] = number_format(bcdiv($result['balance'], 100, 2),2);
        $result['merchant_id'] = $merchantId;
        $this->assign('info', $result);
        $this->display();
    }

    /**
     * 按照时间导出代发记录
     */
    public function export()
    {
        $applyTimeStart = $applyTimeEnd = 0;
        $exportCondition = '';
        if (!empty($_REQUEST['apply_time_start']))
        {
            $applyTimeStart = strtotime($_REQUEST['apply_time_start']);
            $exportCondition = ' AND create_time >= '.$applyTimeStart;
        }
        if (!empty($_REQUEST['apply_time_end']))
        {
            $applyTimeEnd = strtotime($_REQUEST['apply_time_end']);
            $exportCondition .= ' AND create_time < '.$applyTimeEnd;
        }
        if (!empty($_REQUEST['project_id']))
        {
            $exportCondition = ' AND project_id = '.intval($_REQUEST['project_id']);
        }


        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        $cntRows = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT count(*) FROM firstp2p_withdraw_proxy WHERE 1 ".$exportCondition);
        if ($cntRows > 10000)
        {
            $this->show_error('当前时间范围内代发记录数超过 10000行,请合理调整导出时间,缩小代发记录数');
            return;
        }
        $res = \libs\db\Db::getInstance('firstp2p', 'slave')->query("SELECT * FROM firstp2p_withdraw_proxy WHERE 1 ".$exportCondition);

        $file_name = '代发明细-'.date('YmdHis', $applyTimeStart).'~'.date('YmdHis', $applyTimeEnd);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($file_name) . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        $title = array(
            "项目编号", "代发类型", "代发时间", "代发金额", "项目标题", "用户编号", "代发记录编号", "收款账户类型", "收款账户姓名", "收款账户银行卡号", "收款账户银行编码", "失败原因","代发商户号","代发结果","操作人"
        );
        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));
        while ($log = $GLOBALS['db']->fetchRow($res)) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $log['order_status_cn'] = $this->showStatus($log['order_status']);
            $log['update_time_format'] = $log['update_time'] ? date('Y-m-d H:i:s', $log['update_time']) : '-';
            $log['create_time_format'] = $log['create_time'] ? date('Y-m-d H:i:s', $log['create_time']) : '-';
            $log['next_retry_time_format'] = $log['next_retry_time'] ? date('Y-m-d H:i:s', $log['next_retry_time']) : '-';
            $log['next_notify_time_format'] = $log['next_notify_time'] ? date('Y-m-d H:i:s', $log['next_notify_time']) : '-';
            $log['biz_type_cn'] = WithdrawProxyModel::$bizTypeDesc[$log['biz_type']];
            $log['amount_format'] = number_format(bcdiv($log['amount'], 100, 2), 2);
            $log['account_no_format'] = bankNoFormat($log['account_no']);
            $log['bank_issuer_cn'] = isset($log['bank_issuer']) ? $log['bank_issuer'] : '-';
            $log['user_type_cn'] = WithdrawProxyModel::$userTypeDesc[$log['user_type']];
            $log['amount_format'] = number_format(bcdiv($log['amount'], 100, 2), 2);
            $log['merchant_no_result'] = $res ? '全部成功' : '未全成功';

            $row = sprintf("
                %s||%s||%s||%s||%s||%s||\t%s||%s||%s||%s||%s||%s||%s||%s||%s
                ",
                $log['project_id'], $log['biz_type_cn'], date('Y-m-d H:i:s', $log['create_time']), format_price($log['amount']/100), $log['project_name'], $log['user_id'],$log['request_no'], $log['user_type_cn'], $log['account_name'], $log['account_no_format'], $log['bank_no'], $log['fail_reason'], $log['merchant_id'], $log['order_status_cn'], $log['retry_admin_name']
            );
            fputcsv($fp, explode('||', iconv("utf-8", "gbk", $row)));
        }
        exit;
    }

    /**
     * 拼接查询条件
     */
    private function _getMap()
    {
        // map 处理
        $_REQUEST['listRows'] = isset($_REQUEST['listRows']) ? intval($_REQUEST['listRows']) : 200;
        // 代发时间
        $applyTimeStart = $applyTimeEnd = 0;
        if (!empty($_REQUEST['apply_time_start']))
        {
            $applyTimeStart = strtotime($_REQUEST['apply_time_start']);
            $map['create_time'] = array('egt', $apply_time);
        }
        if (!empty($_REQUEST['apply_time_end']))
        {
            $applyTimeEnd = strtotime($_REQUEST['apply_time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $applyTimeStart, $applyTimeEnd));
        }
        // 项目id
        $projectId = trim($_REQUEST['project_id']);
        if($projectId)
        {
            $map['project_id'] = $projectId;
        }

        // 用户id
        $userId = trim($_REQUEST['user_id']);
        if($userId)
        {
            $map['user_id'] = $userId;
        }

        // 代发单号
        $requestNo = trim($_REQUEST['request_no']);
        if($requestNo)
        {
            $map['request_no'] = $requestNo;
        }

        // 状态
        $orderStatus = trim($_REQUEST['order_status']);
        if ($orderStatus != null && $orderStatus != '99')
        {
            $map['order_status'] = $orderStatus;
            $_REQUEST['show_all'] = $orderStatus;
        }
        if ($orderStatus == '99')
        {
            unset($_REQUEST['order_status']);
            $_REQUEST['show_all'] = 99;
        }
        // 默认全部
        if (!isset($_REQUEST['order_status']))
        {
            $_REQUEST['show_all'] = 99;
        }

        // 查看某个失败代发记录一共的重试代发记录信息
        $watchRetryLogs = trim($_REQUEST['watch_retry']);
        if ($watchRetryLogs)
        {
            $withdrawProxyInfo = WithdrawProxyModel::instance()->find(intval($_REQUEST['recId']));
            $map['merchant_no_seq'] = $withdrawProxyInfo->merchant_no_seq;
            $map['merchant_no'] = $withdrawProxyInfo->merchant_no;
        }

        return $map;
    }

    /**
     * 失败重新代发
     */
    public function redoWithdrawProxy()
    {
        $response = [
            'errCode'   => 0,
            'errMsg'    => '操作成功',
        ];
        try {
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if (empty($id))
            {
                throw new \Exception('代发记录ID 不能为空');
            }
            WithdrawProxyService::redoWithdrawProxy($id);
        } catch (\Exception $e) {
            $response['errCode']    = 1;
            $response['errMsg']     = $e->getMessage();
        }
        echo json_encode($response);
    }

    /**
     * 重置通知业务方计数器
     */
    public function resetNotifyCounter()
    {
        $response = [
            'errCode'   => 0,
            'errMsg'    => '操作成功',
        ];
        try {
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if (empty($id))
            {
                throw new \Exception('代发记录ID 不能为空');
            }
            WithdrawProxyService::resetNotifyCounter($id);
        } catch (\Exception $e) {
            $response['errCode']    = 1;
            $response['errMsg']     = $e->getMessage();
        }
        echo json_encode($response);

    }


    /**
     * 展示日对账清单
     */
    public function showReport()
    {
        $date = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : '';
        $list = WithdrawProxyCheckModel::instance()->getRecentCheckList($date);
        foreach ($list as $k => $info)
        {
            $statistics = $info['statistics'];
            $statisticsShow = [];
            foreach (WithdrawProxyCheckModel::$checkStatusCn as $status => $cn)
            {
                $data = [];
                $data['amount'] = 0;
                $data['count'] = 0;
                if (isset($statistics[$status]))
                {
                    $data = $statistics[$status];
                }
                $list[$k]['check_status_'.$status] = $data['count'].'笔/'.format_price($data['amount']/100) . ($data['count'] > 0 ? "&nbsp;&nbsp;<a href=\"javascript:viewDiff('{$k}',$status);\">查看</a>" : '');
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function viewDiff()
    {
        $date = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : '';
        $checkStatus = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : 0;
        if (empty($date) or empty($checkStatus))
        {
            $this->error("对账日期或者对账状态为空");
        }
        $list = WithdrawProxyCheckModel::getDiffList($date, $checkStatus);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 显示代发状态
     */
    private function showStatus($status)
    {
        return WithdrawProxyModel::$orderStatusDesc[$status];
    }
}
