<?php
/**
 * 网信下单模式大额充值统计
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';

use libs\utils\PaymentApi;
use libs\utils\Script;
use core\dao\PaymentNoticeModel;

class PaymentOfflineChargeReport{
    // 开始执行时间
    private $startTime = 0;
    // 接收消息的配置键值
    private $receiveWeiXinConfig = 'PAYMENT_OFFLINE_CHARGE_MAIL';
    // 微信推送地址
    private $wxPushUrl = 'http://itil.firstp2p.com/api/weixin/sendText?to=%s&content=%s&sms=0&appId=payment';
    // 默认邮件组
    private $defaultWeiXinGroup = 'wangqunqiang@ucfgroup.com,guofeng3@ucfgroup.com';
    // 充值渠道定义
    private $platformConfig = [7 => '线下充值', 18 => '开通模式大额充值', 21 => '下单模式大额充值'];
    // 大额充值统计数组
    private $offlineReport = ['totalInfo'=>[], 'rangeInfo'=>[]];

    public function __construct() {
        // 开始时间
        $this->startTime = microtime(true);
    }

    public function run() {
        PaymentApi::log('paymentOfflineChargeReportStart. startTime:' . $this->startTime);

        // 查询指定渠道下的充值总数量、充值总额
        $payStartTime = strtotime(date('Y-m-d')) - date('Z');
        foreach ($this->platformConfig as $platformId => $tmp) {
            $sql = sprintf("SELECT COUNT(id) AS totalCnt, SUM(money) AS totalMoney FROM `firstp2p_payment_notice` WHERE `is_paid`='%d' AND `platform`='%d' AND `pay_time` >= '%d'", PaymentNoticeModel::IS_PAID_SUCCESS, $platformId, $payStartTime);
            $this->offlineReport['totalInfo'][$platformId] = $GLOBALS['db']->getRow($sql);
        }

        // 获取当天网信平台总充值金额，单位元
        //$this->offlineReport['wxTotalChargeMoney'] = PaymentNoticeModel::instance()->getPlatformPayment();

        // 查询指定渠道下的2小时内的充值总数量、充值总额
        $payStartTime = time() - date('Z') - (3600 * 2);
        foreach ($this->platformConfig as $platformId => $tmp) {
            $sql = sprintf("SELECT COUNT(id) AS totalCnt, SUM(money) AS totalMoney FROM `firstp2p_payment_notice` WHERE `is_paid`='%d' AND `platform`='%d' AND `pay_time` >= '%d'", PaymentNoticeModel::IS_PAID_SUCCESS, $platformId, $payStartTime);
            $this->offlineReport['rangeInfo'][$platformId] = $GLOBALS['db']->getRow($sql);
        }

        // 发送微信
        $this->sendWeiXin();

        // 记录日志
        PaymentApi::log('paymentOfflineChargeReportEnd. endTime:' . microtime(true) . ', cost:' . round(microtime(true) - $this->startTime, 3) . ', statisData:' . json_encode($this->offlineReport));
    }

    /**
     * 发送微信
     */
    private function sendWeiXin() {
        if (empty($this->offlineReport)) {
            PaymentApi::log('paymentOfflineChargeReportSendWeiXinEnd. statisData is empty');
            return false;
        }

        // 充值渠道定义
        $platformConfig = $this->platformConfig;
        $reportArr = $existLine = [];
        $reportArr[] = sprintf('截止到[%s]网信大额充值数据统计', date('Y年n月j日 H时i分'));
        foreach ($this->offlineReport as $key => $offlineItem) {
            if ($key == 'totalInfo') {
                if (!isset($existLine[$key])) {
                    $reportArr[] = '-------------[今天充值总量数据]-----------------';
                    $existLine[$key] = 1;
                }
                foreach ($offlineItem as $platformKey => $platformVal) {
                    $reportArr[] = sprintf('%s：总数量[%d]条，总金额[%s]元', $platformConfig[$platformKey], $platformVal['totalCnt'], number_format(floatval($platformVal['totalMoney']), 2));
                }
            }
            if ($key == 'rangeInfo') {
                if (!isset($existLine[$key])) {
                    $reportArr[] = '-------------[今天充值增量数据]-----------------';
                    $existLine[$key] = 1;
                }
                foreach ($offlineItem as $platformKey => $platformVal) {
                    $reportArr[] = sprintf('%s：增量数量[%d]条，增量金额[%s]元', $platformConfig[$platformKey], $platformVal['totalCnt'], number_format(floatval($platformVal['totalMoney']), 2));
                }
            }
        }
        $content = join("\n", $reportArr);

        $wxAddress = app_conf($this->receiveWeiXinConfig);
        empty($wxAddress) && $wxAddress = $this->defaultWeiXinGroup;
        // 微信群发
        $wxRecieverNames = str_replace(',', '|', str_replace('@ucfgroup.com', '', $wxAddress));
        $result = file_get_contents(sprintf($this->wxPushUrl, $wxRecieverNames, urlencode($content)));
        PaymentApi::log('paymentOfflineChargeReportSendWeiXinEnd. wxPushRet:' . $result);
    }
}

Script::start();
$obj = new PaymentOfflineChargeReport();
$result = $obj->run();
// 脚本结束
Script::end();