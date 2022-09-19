<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26773657
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018/11/8
 * Time: 11:48
 */
namespace core\service\ifapush;

use core\enum\IfaEnum;
use core\dao\ifapush\IfaDealModel;
use core\dao\ifapush\IfaDealRepayModel;
use core\dao\ifapush\IfaDealLoadModel;
use core\dao\ifapush\IfaDealStatusModel;
use core\dao\ifapush\IfaUserLogModel;
use core\dao\ifapush\IfaUserLogZdxModel;
use core\dao\ifapush\IfaUserModel;
use core\dao\ifapush\IfaReceiveModel;
use core\dao\ifapush\IfaTransferModel;
use core\dao\ifapush\IfaTransferStatusModel;
use core\dao\ifapush\IfaProductModel;
use core\dao\ifapush\IfaLoanModel;
use core\dao\ifapush\IfaLenderLogModel;
use libs\utils\Curl;
use libs\utils\Logger;
use libs\utils\Monitor;
use NCFGroup\Common\Library\Idworker;

class PushToIfa
{
    public $env;

    public $handle;

    public $pushType;

    public $pushData;

    /** 不推送的数据字段 */
    public $excludeFilelds = [
        'id', 'order_id', 'status', 'create_time', 'update_time', 'succ_time', 'repayStartTime'
    ];

    public $pushConfig = [
        /** 开户成功推送 */
        'register_user' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_userInfo',
                'test' => 'http://10.20.69.216:8881/yjzx_userInfo'
            ],
            'exclude_fields' => ['userId', 'userPay', 'userPayAccount', 'userBank', 'userBankAccount', 'batchOrderId'], // 不推送的字段
            'db_time_column' => 'userCreateTime', // 数据库记录业务时间的字段
        ],
        /** 报备成功推送 */
        'add_deal' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_scatterInvest',
                'test' => 'http://10.20.69.216:8881/yjzx_scatterInvest'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'productStartTime', // 数据库记录业务时间的字段
        ],
        /** 状态变更推送 */
        'deal_status' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_status',
                'test' => 'http://10.20.69.216:8881/yjzx_status'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'productDate', // 数据库记录业务时间的字段
        ],
        /** 放款成功完成还款计划后推送 */
        'deal_repay' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_repayPlan',
                'test' => 'http://10.20.69.216:8881/yjzx_repayPlan'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'repayStartTime', // 数据库记录业务时间的字段
        ],
        /** 放款成功后完成债权信息(投资记录)的推送 */
        'deal_load' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_creditor',
                'test' => 'http://10.20.69.216:8881/yjzx_creditor'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'invTime', // 数据库记录业务时间的字段
        ],

        /** 债权受让 */
        'dt_receive' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_underTake',
                'test' => 'http://10.20.69.216:8881/yjzx_underTake'
            ],
            'exclude_fields' => ['userId', 'batchOrderId'],
            'db_time_column' => 'takeTime', // 数据库记录业务时间的字段
        ],

        /** 债转转让 */
        'dt_transfer' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_transferProject',
                'test' => 'http://10.20.69.216:8881/yjzx_transferProject'
            ],
            'exclude_fields' => ['userId', 'batchOrderId'],
            'db_time_column' => 'transferDate', // 数据库记录业务时间的字段
        ],

        /** 债转转让状态 */
        'dt_transfer_status' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_transferStatus',
                'test' => 'http://10.20.69.216:8881/yjzx_transferStatus'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'productDate', // 数据库记录业务时间的字段
        ],
        'user_log' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_transact',
                'test' => 'http://10.20.69.216:8881/yjzx_transact'
            ],
            'exclude_fields' => ['batchOrderId','isSupervisionUserLog'], // 不推送的字段
            'db_time_column' => 'transDate', // 数据库记录业务时间的字段
        ],
        'user_log_zdx' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_transact',
                'test' => 'http://10.20.69.216:8881/yjzx_transact'
            ],
            'exclude_fields' => ['batchOrderId','isSupervisionUserLog'], // 不推送的字段
            'db_time_column' => 'transDate', // 数据库记录业务时间的字段
        ],
        'product' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_lendProduct',
                'test' => 'http://10.20.69.216:8881/yjzx_lendProduct'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'financingStartTime', // 数据库记录业务时间的字段
        ],
        'loan' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_lendProductConfig',
                'test' => 'http://10.20.69.216:8881/yjzx_lendProductConfig'
            ],
            'exclude_fields' => ['batchOrderId','transferId','startTime'], // 不推送的字段
            'db_time_column' => 'startTime', // 数据库记录业务时间的字段         //有问题
        ],
        'lender_log' => [
            'host' => [
                'online' => 'http://xhreatime.corp.ncfgroup.com/yjzx_lendParticulars',
                'test' => 'http://10.20.69.216:8881/yjzx_lendParticulars'
            ],
            'exclude_fields' => ['batchOrderId'], // 不推送的字段
            'db_time_column' => 'transTime', // 数据库记录业务时间的字段
        ],

    ];

    public function __construct($pushType)
    {
        $this->env = app_conf('ENV_FLAG') == 'online' ? 'online' : 'test';
        $this->pushType = $pushType;
        $this->getModelHandle();
    }

    public function getModelHandle()
    {
        switch ($this->pushType) {
            case 'register_user':
                $this->handle = new IfaUserModel();
                break;
            case 'add_deal':
                $this->handle = new IfaDealModel();
                break;
            case 'deal_status':
                $this->handle = new IfaDealStatusModel();
                break;
            case 'deal_repay' :
                $this->handle = new IfaDealRepayModel();
                break;
            case 'deal_load' :
                $this->handle = new IfaDealLoadModel();
                break;
            case 'dt_receive' :
                $this->handle = new IfaReceiveModel();
                break;
            case 'dt_transfer' :
                $this->handle = new IfaTransferModel();
                break;
            case 'dt_transfer_status' :
                $this->handle = new IfaTransferStatusModel();
                break;
            case 'user_log' :
                $this->handle = new IfaUserLogModel();
                break;
            case 'user_log_zdx' :
                $this->handle = new IfaUserLogZdxModel();
                break;
            case 'product' :
                $this->handle = new IfaProductModel();
                break;
            case 'loan' :
                $this->handle = new IfaLoanModel();
                break;
            case 'lender_log' :
                $this->handle = new IfaLenderLogModel();
                break;

        }
        return $this;
    }

    public function getPushData($limit = 10, $orderId = false)
    {
        $pushSwitch = app_conf('IFA_PUSH_DATA_SWITCH');
        if ($pushSwitch == 0) {
            throw new \Exception('推送数据总开关关闭');
        }
        $conf = array();
        $config = app_conf('IFA_PUSH_DATA_CONFIG');
        $config = explode(";", $config);

        foreach ($config as $k => $v) {
            $v = trim($v);
            if (empty($v)) continue;
            $tmp = explode(':', $v);
            $conf[$tmp[0]]['switch'] = $tmp[1];
            $conf[$tmp[0]]['interval'] = $tmp[2];
        }

        if (!isset($conf[$this->pushType])) {
            throw new \Exception('未找到数据配置');
        }

        if (isset($conf[$this->pushType]) && $conf[$this->pushType]['switch'] == 0) {
            throw new \Exception($this->pushType . ' 推送数据开关关闭');
        }

        $this->pushData = $this->handle->getPushData($conf[$this->pushType]['interval'] * 60, $limit, $orderId);
        return $this;
    }

    /**
     * 获取批量数据，用于更新batchOrderId
     */
    public function getBatchPushDataForOrderId($limit = 10)
    {
        $pushSwitch = app_conf('IFA_PUSH_DATA_SWITCH');
        if ($pushSwitch == 0) {
            throw new \Exception('推送数据总开关关闭');
        }
        $conf = array();
        $config = app_conf('IFA_PUSH_DATA_CONFIG');
        $config = explode(";", $config);

        foreach ($config as $k => $v) {
            $v = trim($v);
            if (empty($v)) continue;
            $tmp = explode(':', $v);
            $conf[$tmp[0]]['switch'] = $tmp[1];
            $conf[$tmp[0]]['interval'] = $tmp[2];
        }

        if (!isset($conf[$this->pushType])) {
            throw new \Exception('未找到数据配置');
        }

        if (isset($conf[$this->pushType]) && $conf[$this->pushType]['switch'] == 0) {
            throw new \Exception($this->pushType . ' 推送数据开关关闭');
        }

        $this->pushData = $this->handle->getBatchPushDataForOrderId($conf[$this->pushType]['interval'] * 60, $limit, false, $this->pushConfig[$this->pushType]['db_time_column'],$this->pushType);
        return $this;
    }

    /**
     * 获取某个batchOrderId的批量数据，用于上传协会
     */
    public function getBatchPushData()
    {
        $pushSwitch = app_conf('IFA_PUSH_DATA_SWITCH');
        if ($pushSwitch == 0) {
            throw new \Exception('推送数据总开关关闭');
        }
        $conf = array();
        $config = app_conf('IFA_PUSH_DATA_CONFIG');
        $config = explode(";", $config);
        foreach ($config as $k => $v) {
            $v = trim($v);
            if (empty($v)) continue;
            $tmp = explode(':', $v);
            $conf[$tmp[0]]['switch'] = $tmp[1];
            $conf[$tmp[0]]['interval'] = $tmp[2];
        }

        if (!isset($conf[$this->pushType])) {
            throw new \Exception('未找到数据配置');
        }

        if (isset($conf[$this->pushType]) && $conf[$this->pushType]['switch'] == 0) {
            throw new \Exception($this->pushType . ' 推送数据开关关闭');
        }
        $this->pushData = $this->handle->getBatchPushData();
        return $this;
    }

    private function excludeFilelds($data)
    {
        $confiExclude = isset($this->pushConfig[$this->pushType]['exclude_fields']) ? $this->pushConfig[$this->pushType]['exclude_fields'] : array();
        $excludeFields = array_merge($this->excludeFilelds, $confiExclude);
        foreach ($data as $key => $val) {
            if (in_array($key, $excludeFields)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    public function push()
    {
        $pushUrl = $this->pushConfig[$this->pushType]['host'][$this->env];
        if (empty($this->pushData)) {
            throw new \Exception('没有需要推送的数据');
        }

        foreach ($this->pushData as $item) {
            if (!is_object($item)) continue;
            $data = $item->getRow();

            $orderId = $data['order_id'];
            $data = $this->excludeFilelds($data);
            $postData = array(
                'orderId' => $orderId,
                'data' => $data,
            );
            $res = Curl::post($pushUrl, json_encode($postData), array(), 10, true);

            $logStr = "cost:" . Curl::$cost . ",err:" . Curl::$error . ",httpCode:" . Curl::$httpCode . " ,data:" . json_encode($postData) . ", return:" . ($res);

            if (!$res) {
                Logger::error(__CLASS__ . "," . __FUNCTION__ . ",push data fail:" . $logStr);
                continue;
            } else {
                $res = json_decode($res, true);
                if ($res['msgCode'] == '0000' || $res['msgCode'] == '1512') {
                    Logger::info(__CLASS__ . "," . __FUNCTION__ . ",push data succ:" . $logStr);
                    // 不能使用item->save()方法，因为某些脱敏字段没有重新加密
                    $item->updateStatus($item->id, 1);
                } else {
                    \libs\utils\Monitor::add('IFA_PUSH_DATA');
                    Logger::info(__CLASS__ . "," . __FUNCTION__ . ",push data fail:" . $logStr);
                }
            }
        }
    }

    /**
     * 生成并且更新batchOrderId
     */
    public function updateBatchOrderId()
    {
        if (empty($this->pushData)) {
            throw  new \Exception('没有需要推送的数据');
        }
        $date = $this->pushData[0][$this->pushConfig[$this->pushType]['db_time_column']];
        if (empty($date)) {
            throw  new \Exception('获取的日期数据不正确');
        }
        // 1天的数据
        $shortDate = date('Ymd', strtotime($date)) . '1';
        $orderId = $shortDate . '_' . strval(Idworker::instance()->getId());
        $batchOrderId = ($this->pushType == 'user_log_zdx') ? $orderId . '_zdx' : $orderId;
        $this->_batchUpdateBatchOrderId($this->pushData, $batchOrderId);
    }

    /**
     * 更新batchOrderId
     */
    private function _batchUpdateBatchOrderId($pushData, $orderId)
    {
        try {
            $GLOBALS['db']->startTrans();
            $idArray = array();
            foreach ($pushData as $item) {
                $idArray[] = $item->id;
            }
            $res = $item->updateBatchOrderId($idArray, $orderId);
            if (!$res) {
                throw new \Exception($item->tableName() . '更新状态失败 ids:' . json_encode($idArray));
            }
            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            throw $ex;
        }
    }

    /**
     * 用于上传协会
     */
    public function batchPush()
    {
        $pushUrl = $this->pushConfig[$this->pushType]['host'][$this->env];
        if (empty($pushUrl)) {
            throw new \Exception('没有配置批量推送URL');
        }
        if (empty($this->pushData)) {
            throw new \Exception('没有需要推送的数据');
        }
        $batchOrderId = $this->pushData[0]['batchOrderId'];
        if (empty($batchOrderId)) {
            throw new \Exception('batchOrderId为空');
        }
        // 批量过滤某些字段
        $batchPushData = $this->batchPushExcludeFilelds($this->pushData); //数组
        $postData = array(
            'orderId' => $batchOrderId,
            'data' => $batchPushData,
        );
        $res = Curl::post($pushUrl, json_encode($postData), array(), 10, true);
        $logStr = "cost:" . Curl::$cost . ",err:" . Curl::$error . ",httpCode:" . Curl::$httpCode . ' ,数据类型:' . $this->pushType . " ,data:" . json_encode($postData) . ", return:" . ($res);
        $res = json_decode($res, true);
        if (!$res) {
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",batch-push-data fail: " . $logStr);
        } else {
            // 1512代表 该批次已经上传成功，请勿重复上报
            if ($res['msgCode'] == '0000' || $res['msgCode'] == '1512') {
                // 批量更新状态为成功
                $this->_batchUpdateStatus($this->pushData);
                Logger::info(__CLASS__ . "," . __FUNCTION__ . ",batch-push-data succ: " . $logStr);
            } else {
                Monitor::add('IFA_PUSH_DATA');
                Logger::error(__CLASS__ . "," . __FUNCTION__ . ",batch-push-data fail: " . $logStr);
            }
        }
    }

    /**
     * 批量推送之前需要过滤下不需要的数据
     */
    private function batchPushExcludeFilelds($pushData)
    {
        foreach ($pushData as $k => $item) {
            if (!is_object($item)) continue;
            $data = $item->getRow();
            $pushData[$k] = $this->excludeFilelds($data);
            // 临时处理，所有历史资金记录的中的新增的三个字段都默认为-1
            // todo 新增的三个字段，新增数据逻辑以后再写
            if(in_array($this->pushType,['user_log','user_log_zdx'])){
                $pushData[$k]['finClaimId'] = '-1';
                $pushData[$k]['transferId'] = '-1';
                $pushData[$k]['replanId'] = '-1';
            }

        }
        return $pushData;
    }

    /**
     * 批量推送成功后，更新状态为3（已通知，等待回调）
     */
    private function _batchUpdateStatus($pushData)
    {
        try {
            $GLOBALS['db']->startTrans();
            $idArray = array();
            foreach ($pushData as $item) {
                $idArray[] = $item->id;
            }
            $res = $item->updateStatus($idArray, IfaEnum::STATUS_DOING);
            if (!$res) {
                throw new \Exception($item->tableName() . '更新状态失败 ids:' . json_encode($idArray));
            }
            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            throw $ex;
        }
    }


}
