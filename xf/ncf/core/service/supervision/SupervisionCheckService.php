<?php
/**
 * 存管订单对账服务类
 *
 * @date 2017-02-17
 * @author weiwei12@ucfgroup.com
 */

namespace core\service\supervision;

use core\service\supervision\SupervisionBaseService AS SupervisionBase;
use core\service\supervision\SupervisionFileDownloadService;
use core\service\supervision\SupervisionFileParseService;
use core\service\email\SendEmailService;
use core\dao\supervision\SupervisionOrderModel;
use core\dao\supervision\SupervisionReturnModel;
use core\dao\supervision\SupervisionCheckErrorModel;
use core\dao\supervision\SupervisionCheckSummaryModel;
use libs\utils\Logger;
use libs\vfs\fds\FdsFTP;
use libs\utils\PaymentApi;
use core\enum\SupervisionEnum;

class SupervisionCheckService extends SupervisionBase
{

    //model对象
    private $returnModel;
    private $orderModel;
    private $errorModel;
    private $summaryModel;

    //对账日期
    private $date;

    const PAGE_SIZE = 1000;

    /**
     * 构造函数
     * @param string $date 对账日期 yyyy-mm-dd
     */
    public function __construct($date) {
        parent::__construct();
        $this->returnModel = SupervisionReturnModel::instance();
        $this->orderModel = SupervisionOrderModel::instance();
        $this->errorModel = SupervisionCheckErrorModel::instance();
        $this->summaryModel = SupervisionCheckSummaryModel::instance();
        $this->date = $date;
    }

    /**
     * 对账
     */
    public function check() {
        try {
            //开始对账
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('begin check supervision order, date: %s', $this->date))));

            //清理历史数据
            $this->clearHistory($this->date);

            //初始化汇总
            $this->summaryModel->initSummary($this->date);
            $this->summaryModel->startSummary();

            //下载对账文件
            $downloadService = new SupervisionFileDownloadService($this->date);
            $downloadService->download();

            //解析对账文件
            $parseService = new SupervisionFileParseService($this->date);
            $parseService->parse();

            $offset = 0;
            while ($returnList = $this->returnModel->getReturnList($this->date, $offset, self::PAGE_SIZE)) {
                $offset += self::PAGE_SIZE;
                foreach ($returnList as $returnData) {
                    //更新回单汇总信息
                    $this->summaryModel->updateSummary(SupervisionCheckSummaryModel::RETURN_TOTAL, $returnData['amount']);

                    //无交易单
                    $orderData = $this->orderModel->getInfoByOutOrderId($returnData['out_order_id']);
                    if (empty($orderData)) {
                        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('check no trade, date: %s, outOrderId: %s', $this->date, $returnData['out_order_id']))));
                        $this->recordError([], $returnData, SupervisionCheckErrorModel::ERR_NO_TRADE);
                        continue;
                    }

                    //检查金额和状态
                    if ($this->checkErrorMoney($orderData, $returnData) || $this->checkErrorStatus($orderData, $returnData)) {
                        //错误逻辑
                    } else {
                        // 对账成功
                        $this->procSuccess($orderData, $returnData);
                    }
                }
            }
            //统一进挂账
            $this->addPending();

            //结束汇总
            $this->summaryModel->endSummary();

            //通知存管结果
            $this->notifyResult();

            //发送邮件
            $this->sendEmail();

            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('end check supervision order, date: %s', $this->date))));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('date: %s, err: %s', $this->date, $e->getMessage()))));
            $this->summaryModel->failSummary($e->getMessage());

            //发送邮件
            $this->sendEmail();
            return false;
        }
    }

    /**
     * 清理历史记录
     */
    private function clearHistory() {
        $this->errorModel->clearError($this->date);
        $this->summaryModel->clearSummary($this->date);
    }

    /**
     * 记录错误
     */
    private function recordError($orderData, $returnData, $errno) {
        $amount = $orderData ? $orderData['amount'] : $returnData['amount'];
        $outOrderId = $orderData ? $orderData['out_order_id'] : $returnData['out_order_id'];
        $params = [
            'out_order_id'  => $outOrderId,
            'date'          => $this->date,
            'errno'         => $errno,
            'return_id'     => isset($returnData['id']) ? $returnData['id'] : 0,
        ];

        //添加错误记录
        $this->errorModel->addError($params);

        //更新汇总
        $this->summaryModel->updateSummary($errno, $amount);
    }

    /**
     * 处理成功
     */
    private function procSuccess($orderData, $returnData) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('check success, date: %s, outOrderId: %s', $this->date, $orderData['out_order_id']))));
        //更新订单对账信息
        $this->orderModel->orderCheck($orderData['out_order_id'], $this->date);
        //更新汇总
        $this->summaryModel->updateSummary(SupervisionCheckSummaryModel::ORDER_SUC, $orderData['amount']);
        $this->summaryModel->updateSummary(SupervisionCheckSummaryModel::RETURN_SUC, $orderData['amount']);
        //钩消挂账
        $this->cancelPending($orderData, $returnData);
    }

    /**
     * 钩消挂账
     */
    private function cancelPending($orderData, $returnData) {
        $outOrderId = $orderData ? $orderData['out_order_id'] : $returnData['out_order_id'];
        $amount = $orderData ? $orderData['amount'] : $returnData['amount'];
        //勾销错误记录
        $result = $this->errorModel->cancelErrPending($outOrderId, ['return_id' => $returnData['id']]);
        //更新汇总
        if ($result) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('cancel pending, date: %s, outOrderId: %s', $this->date, $outOrderId))));
            $this->summaryModel->updateSummary(SupervisionCheckErrorModel::ERR_CANCEL, $amount);
        }
    }

    /**
     * 统一进挂账
     */
    private function addPending() {
        $offset = 0;
        while ($orderList = $this->orderModel->getListByDate($this->date, $offset, self::PAGE_SIZE)) {
            $offset += self::PAGE_SIZE;
            foreach ($orderList as $orderData) {
                //记挂账 去回单表反查
                if ($orderData['check_status'] == SupervisionOrderModel::CHECK_STATUS_NORMAL && !$this->returnModel->getInfoByOutOrderId($orderData['out_order_id'])) {
                    Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('check pending, date: %s, outOrderId: %s', $this->date, $orderData['out_order_id']))));
                    $this->recordError($orderData, [], SupervisionCheckErrorModel::ERR_PENDING);
                }
                //更新订单汇总信息
                $this->summaryModel->updateSummary(SupervisionCheckSummaryModel::ORDER_TOTAL, $orderData['amount']);
            }
        }
    }

    /**
     * 判断是否是错账
     */
    private function checkErrorMoney($orderData, $returnData) {
        if (bccomp($orderData['amount'], $returnData['amount']) == 0) {
            return false;
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('check error money, date: %s, outOrderId: %s, orderAmount: %s, returnAmount: %s', $this->date, $returnData['out_order_id'], $orderData['amount'], $returnData['amount']))));
        $this->recordError($orderData, $returnData, SupervisionCheckErrorModel::ERR_MONEY);
        return true;
    }

    /**
     * 判断状态
     */
    private function checkErrorStatus($orderData, $returnData) {
        if (bccomp($orderData['order_status'], $returnData['order_status']) == 0) {
            return false;
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION_CHECK', sprintf('check error status, date: %s, outOrderId: %s, orderStatus: %s, returnStatus: %s', $this->date, $returnData['out_order_id'], $orderData['order_status'], $returnData['order_status']))));
        $this->recordError($orderData, $returnData, SupervisionCheckErrorModel::ERR_STATUS);
        return true;
    }

    /**
     * 发送汇总邮件
     */
    private function sendEmail() {
        $summary = $this->summaryModel->getSummaryByDate($this->date);
        if (empty($summary)) {
            return false;
        }
        $subject = date('Y年n月j日', strtotime($summary['date'])).'存管订单对账';
        $body = '';
        $body .= "<h3>$subject</h3>";
        $body .= '<ul style="color:#1f497d;">';
        $body .= '<li>开始时间: '.date('Y-m-d H:i:s', $summary['start_time']).'</li>';
        $body .= '<li>结束时间: '.date('Y-m-d H:i:s', $summary['end_time']).'</li>';
        $body .= '<li>对账状态: '.SupervisionCheckSummaryModel::$statusDesc[$summary['status']].' </li>';
        $body .= '<li>交易日期: '.$summary['date'].' </li>';
        $body .= '<li>备注: '.$summary['comment'].' </li>';
        $body .= '<li>订单总笔数/金额: '.$summary['order_total'].' / '.bcdiv($summary['order_total_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>订单对账成功总笔数/金额: '.$summary['order_suc'].' / '.bcdiv($summary['order_suc_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>存管回单总笔数/金额: '.$summary['return_total'].' / '.bcdiv($summary['return_total_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>存管回单对账成功总笔数/金额: '.$summary['return_suc'].' / '.bcdiv($summary['return_suc_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>挂账数/金额: '.$summary['pend'].' / '.bcdiv($summary['pend_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>销账数/金额: '.$summary['cancel'].' / '.bcdiv($summary['cancel_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>状态错误总数/金额: '.$summary['err_status'].' / '.bcdiv($summary['err_status_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>错账总数/金额: '.$summary['err_money'].' / '.bcdiv($summary['err_money_amount'], 100, 2) . '元' .' </li>';
        $body .= '<li>无交易单总数/金额: '.$summary['err_no_trade'].' / '.bcdiv($summary['err_no_trade_amount'], 100, 2) . '元' .' </li>';
        $body .= '</ul>';

        $mailAddress = app_conf('SUPERVISION_ORDER_CHECK_MAIL');
        $ret = SendEmailService::sendEmail($mailAddress, 0, $body, false, $subject);

        return $ret;
    }

    /**
     * 通知存管结果
     */
    private function notifyResult() {
        $summary = $this->summaryModel->getSummaryByDate($this->date);
        if (empty($summary)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('summary empty, date: %s', $this->date))));
            return false;
        }
        $errNum = $summary['pend'] + $summary['err_status'] + $summary['err_no_trade'] + $summary['err_money']; //错误总数
        $checkResult = $errNum > 0 ? 'F' : 'S';
        $params = [
            'billDate' => $this->date,
            'bizType' => 'T', //交易对账
            'billCheckResult' => $checkResult, //对账结果
        ];
        $result = $this->api->request('checkResultNotice', $params);
        if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('notify fail, date: %s, respMsg: %s', $this->date, $result['respMsg']))));
            return false;
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('notify success, date: %s', $this->date))));
        return true;
    }
}
