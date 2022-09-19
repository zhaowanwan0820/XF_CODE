<?php
/**
 * 存管对账文件解析服务类
 *
 * @date 2017-02-17
 * @author weiwei12@ucfgroup.com
 */

namespace core\service;

use libs\utils\Logger;
use libs\utils\Sftp;
use core\dao\SupervisionReturnModel;
use core\service\SupervisionOrderService;
use core\service\SupervisionBaseService AS SupervisionBase;

class SupervisionFileParseService extends SupervisionBase
{
    //必填字段
    private $requiredFields = ['out_order_id', 'sv_order_id', 'trade_code', 'amount', 'order_status', 'finish_time'];

    //解析配置
    private $parseConf = [
        //充值对账文件
        'recharge' => [
            'columnConf' => [
                'out_order_id'          => 0, //商户订单号
                'sv_order_id'           => 1, //存管订单号
                'trade_code'            => 2, //交易类型
                'amount'                => 3, //交易金额
                'order_status'          => 4, //交易状态
                'finish_time'           => 5, //完成时间
                'pay_code'              => 6, //支付公司代码
                'pay_user_id'           => 7, //平台用户ID
                'source'                => 8, //业务来源
                'remark'                => 9, //备注
            ],
            'total_fds' => 10, //总列数
            'skiplines' => 1, //跳过的行
            'seperator' => '|', //分割符
        ],
        //提现对账文件
        'withdraw' => [
            'columnConf' => [
                'out_order_id'          => 0, //商户订单号
                'sv_order_id'           => 1, //存管订单号
                'trade_code'            => 2, //交易类型
                'amount'                => 3, //交易金额
                'order_status'          => 4, //交易状态
                'finish_time'           => 5, //完成时间
                'pay_code'              => 6, //支付公司代码
                'pay_user_id'           => 7, //平台用户ID
                'remark'                => 8, //备注
            ],
            'total_fds' => 9, //总列数
            'skiplines' => 1, //跳过的行
            'seperator' => '|', //分割符
        ],
        //交易对账文件
        'transaction' => [
            'columnConf' => [
                'out_order_id'          => 0, //商户订单号
                'sv_order_id'           => 1, //存管订单号
                'trade_code'            => 2, //交易类型
                'amount'                => 3, //交易金额
                'order_status'          => 4, //交易状态
                'finish_time'           => 5, //完成时间
                'deal_id'               => 6, //标的号
                'pay_user_id'           => 7, //交易出款方
                'receive_user_id'       => 8, //交易入款方
                'remark'                => 9, //备注
            ],
            'total_fds' => 10, //总列数
            'skiplines' => 1, //跳过的行
            'seperator' => '|', //分割符
        ],
    ];

    //对账日期
    private $date;

    //文件类型
    private $type;

    //列配置
    private $columnConf;

    //总列数配置
    private $totalFds;

    //当前的跳过行配置
    private $skiplines;

    //当前的分隔符配置
    private $seperator;

    /**
     * 构造函数
     * @param string $date 对账日期 yyyy-mm-dd
     */
    public function __construct($date) {
        $this->date = $date;
    }

    /**
     * 解析文件
     * @return boolean
     */
    public function parse() {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('begin parse supervision file, date: %s', $this->date))));

        $files = $GLOBALS['sys_config']['SUPERVISION']['check']['files'];
        //清理回单
        $returnModel = SupervisionReturnModel::instance();
        $returnModel->clearReturn($this->date);
        $date = date('Ymd', strtotime($this->date));
        foreach ($this->parseConf as $type => $conf) {

            //初始化配置
            $this->initConf($type, $conf);

            //读取文件
            $filename = sprintf($files[$type]['local'], $date);
            if (!file_exists($filename)) {
                throw new \Exception(sprintf('parse failed, file not exist, filename: %s', $filename));
            }
            $result = array();
            $handle = fopen($filename, "r");
            if (!$handle) {
                throw new \Exception(sprintf('parse failed, file not readable, filename: %s', $filename));
            }

            //解析数据
            $rowNo = 0;
            while (($row = fgets($handle)) !== false) {
                $rowNo ++;
                //跳过行
                if ($rowNo <= $this->skiplines) {
                    continue;
                }

                //解析行
                $rowInfo = $this->parseRow($row, $rowNo);
                $rowInfo['date'] = $this->date;
                $rowInfo['type'] = isset(SupervisionReturnModel::$typeMap[$type]) ? SupervisionReturnModel::$typeMap[$type] : 0; //类型

                //忽略行
                if ($this->isIgnore($rowInfo)) {
                    continue;
                }

                //检查订单
                if ($returnModel->getInfoByOutOrderId($rowInfo['out_order_id'])) {
                    //记录错误日志
                    Logger::error('supervision parse error. repeat out_order_id: ' . $rowInfo['out_order_id']);
                    continue;
                }
                $returnModel->addReturn($rowInfo);
            }
            fclose($handle);
            unlink($filename);
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('end parse supervision file, date: %s', $this->date))));
        return true;
    }

    /**
     * 初始化配置
     */
    private function initConf($type, $parseConf) {
        $this->type = $type;
        $this->columnConf = $parseConf['columnConf'];
        $this->totalFds = $parseConf['total_fds'];
        $this->skiplines = $parseConf['skiplines'];
        $this->seperator = $parseConf['seperator'];

        $this->checkConf();
    }

    /**
     * 检查配置
     */
    private function checkConf()
    {
        foreach ($this->requiredFields as $field) {
            if (!isset($this->columnConf[$field])) {
                throw new \Exception(sprintf('parse failed, type: %s, miss required field %s in parse config', $this->type, $field));
            }
        }
    }

    /**
     * 解析行
     */
    private function parseRow($row, $rowNo) {
        $row = trim($row);
        $info = explode($this->seperator, $row);
        /*
        去掉列数校验，提现对账单新增字段
        if (count($info) != $this->totalFds) {
            throw new \Exception(sprintf('parse failed, file format error, row: %s, rowNo: %s, type: %s count: %s, total_fds: %s', $row, $rowNo, $this->type, count($info), $this->totalFds));
        }
        */
        $info['row'] = $row;
        $info['rowNo'] = $rowNo;

        $rowInfo = [];
        $this->parseFields($info, $rowInfo);
        return $rowInfo;
    }

    /**
     * 解析字段
     */
    private function parseFields($info, &$rowInfo)
    {
        $this->parseOutOrderId($info, $rowInfo);
        $this->parseSvOrderId($info, $rowInfo);
        $this->parseAmount($info, $rowInfo);
        $this->parseTradeCode($info, $rowInfo);
        $this->parseOrderStatus($info, $rowInfo);
        $this->parseFinishTime($info, $rowInfo);
        $this->parsePayCode($info, $rowInfo);
        $this->parseRemark($info, $rowInfo);
        $this->parsePayUserId($info, $rowInfo);
        $this->parseReceiveUserId($info, $rowInfo);
        $this->parseSource($info, $rowInfo);
        $this->parseDealId($info, $rowInfo);
    }

    /**
     * 解析商户订单号
     */
    private function parseOutOrderId($info, &$rowInfo) {
        if (empty($info[$this->columnConf['out_order_id']])) {
            throw new \Exception(sprintf('parse failed, out_order_id error, row: %s, rowNo:%s, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $rowInfo['out_order_id'] = $info[$this->columnConf['out_order_id']];
    }

    /**
     * 解析存管订单号
     */
    private function parseSvOrderId($info, &$rowInfo) {
        if (empty($info[$this->columnConf['sv_order_id']])) {
            throw new \Exception(sprintf('parse failed, sv_order_id error, row: %s, rowNo:%s, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $rowInfo['sv_order_id'] = $info[$this->columnConf['sv_order_id']];
    }

    /**
     * 解析金额
     */
    private function parseAmount($info, &$rowInfo) {
        if (empty($info[$this->columnConf['amount']])) {
            throw new \Exception(sprintf('parse failed, amount error, row: %s, rowNo:%s, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $amount = $info[$this->columnConf['amount']];
        $rowInfo['amount'] = bcmul($amount, 100);//转换成分
    }

    /**
     * 解析状态
     */
    private function parseOrderStatus($info, &$rowInfo) {
        if (empty($info[$this->columnConf['order_status']])) {
            throw new \Exception(sprintf('parse failed, order_status error, row: %s, rowNo:%s, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $orderStatus = $info[$this->columnConf['order_status']];
        //取消投资业务修改订单状态C
        if (!empty($rowInfo['trade_code']) && in_array($rowInfo['trade_code'], ['3003', '3004']) && $orderStatus == self::NOTICE_SUCCESS) {
            $orderStatus = self::NOTICE_CANCEL;
        }
        if (!isset(SupervisionOrderService::$statusMap[$orderStatus])) {
            throw new \Exception(sprintf('parse failed, Invalid orderStatus，row: %s, rowNo, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $rowInfo['order_status'] = SupervisionOrderService::$statusMap[$orderStatus];
    }

    /**
     * 解析交易类型
     */
    private function parseTradeCode($info, &$rowInfo) {
        if (empty($info[$this->columnConf['trade_code']])) {
            throw new \Exception(sprintf('parse failed, trade_code error, row: %s, rowNo:%s, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $rowInfo['trade_code'] = $info[$this->columnConf['trade_code']];
    }

    /**
     * 解析完成时间
     */
    private function parseFinishTime($info, &$rowInfo) {
        if (empty($info[$this->columnConf['finish_time']])) {
            throw new \Exception(sprintf('parse failed, finish_time error, row: %s, rowNo:%s, type: %s', $info['row'], $info['rowNo'], $this->type));
        }
        $rowInfo['finish_time'] = strtotime($info[$this->columnConf['finish_time']]);
    }

    /**
     * 解析支付公司代码
     */
    private function parsePayCode($info, &$rowInfo) {
        if (!isset($this->columnConf['pay_code'])) {
            return;
        }
        $rowInfo['pay_code'] = isset($info[$this->columnConf['pay_code']]) ? $info[$this->columnConf['pay_code']] : '';
    }


    /**
     * 交易备注
     */
    private function parseRemark($info, &$rowInfo) {
        if (!isset($this->columnConf['remark'])) {
            return;
        }
        $rowInfo['remark'] = isset($info[$this->columnConf['remark']]) ? $info[$this->columnConf['remark']] : '';
    }

    /**
     * 解析交易出款方
     */
    private function parsePayUserId($info, &$rowInfo) {
        if (!isset($this->columnConf['pay_user_id'])) {
            return;
        }
        $rowInfo['pay_user_id'] = isset($info[$this->columnConf['pay_user_id']]) ? (int) $info[$this->columnConf['pay_user_id']] : 0;
    }

    /**
     * 解析交易出款方
     */
    private function parseReceiveUserId($info, &$rowInfo) {
        if (!isset($this->columnConf['receive_user_id'])) {
            return;
        }
        $rowInfo['receive_user_id'] = isset($info[$this->columnConf['receive_user_id']]) ? (int)$info[$this->columnConf['receive_user_id']] : 0;
    }

    /**
     * 解析来源
     */
    private function parseSource($info, &$rowInfo) {
        if (!isset($this->columnConf['source'])) {
            return;
        }
        $rowInfo['source'] = isset($info[$this->columnConf['source']]) ? (int) $info[$this->columnConf['source']] : 1;
    }

    /**
     * 解析交易出款方
     */
    private function parseDealId($info, &$rowInfo) {
        if (!isset($this->columnConf['deal_id'])) {
            return;
        }
        $rowInfo['deal_id'] = isset($info[$this->columnConf['deal_id']]) ? (int) $info[$this->columnConf['deal_id']] : 0;
    }

    /**
     * 是否忽略
     */
    private function isIgnore($rowInfo) {
        //过滤流标单据
        if (isset($rowInfo['trade_code']) && $rowInfo['trade_code'] == '3005') {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('ignore line, rowInfo: %s', json_encode($rowInfo)))));
            return true;
        }
        return false;
    }
}
