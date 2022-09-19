<?php
/**
 * 速贷配置服务
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 **/

namespace core\service\speedLoan;

use core\dao\ConfModel;
use libs\utils\PaymentApi;
use libs\utils\Alarm;

class ConfigService {
    static $configDelimiter = ';';

    static $configKeys = [
        'config' => [
            'SPEED_LOAN_SWITCH', // 网信信贷开关
            'SPEED_LOAN_SERVICE_FEE_UID', // 平台手续费收取账号
            'SPEED_LOAN_SERVICE_RATE', // 平台服务费(总)
            'SPEED_LOAN_SERVICE_FEE_STEP_ONE', // 平台服务费(第一段)
            'SPEED_LOAN_SERVICE_FEE_STEP_TWO', // 平台服务费(第二段)
            'SPEED_LOAN_SERVICE_FEE_STEP_TERM', // 第一段平台服务费收取时间
            'SPEED_LOAN_USE_PURPOSE', // 速贷借款用途
            'SPEED_LOAN_SERVICE_HOUR_START', // 服务时间，开始
            'SPEED_LOAN_SERVICE_HOUR_END', // 服务时间，结束
            'SPEED_LOAN_MIN_AMOUNT', // 单笔借款最小金额
            'SPEED_LOAN_MAX_AMOUNT', // 单笔借款最大金额
            'SPEED_LOAN_USER_LIMIT_AMOUNT', // 用户借款上限
            'SPEED_LOAN_DAILY_RATE', // 借款日利率
            'SPEED_LOAN_MORTGAGE_TYPE', // 抵押类型，统一质押率|分类质押率
            'SPEED_LOAN_MORTGAGE_RATE', // 统一质押率
            //抵押物管理配置
            'SPEED_LOAN_DEAL_TYPE', // 贷款类型
            'SPEED_LOAN_PRODUCT_TYPE', // 产品类别
            'SPEED_LOAN_DEAL_REPAY_TYPE', // 抵押还款方式
            'SPEED_LOAN_PAWN_HOLD_DAYS', // 抵押物持有时间
            'SPEED_LOAN_PAWN_REMAIN_DAYS_START', // 抵押物到期剩余时间，开始
            'SPEED_LOAN_PAWN_REMAIN_DAYS_END', // 抵押物到期剩余时间，结束
            //其他用途抵押物管理
            'SPEED_LOAN_OTHER_DEAL_TYPE', // 贷款类型
            'SPEED_LOAN_OTHER_PRODUCT_TYPE', // 产品类别
            'SPEED_LOAN_OTHER_DEAL_REPAY_TYPE', // 抵押还款方式
            'SPEED_LOAN_OTHER_DEAL_TAG', // 标的tag

        ],
    ];

    //可以为空
    static $canEmptyConfigKeys = [
        'config' => [
            //其他用途抵押物管理
            'SPEED_LOAN_OTHER_DEAL_TYPE', // 贷款类型
            'SPEED_LOAN_OTHER_PRODUCT_TYPE', // 产品类别
            'SPEED_LOAN_OTHER_DEAL_REPAY_TYPE', // 抵押还款方式
            'SPEED_LOAN_OTHER_DEAL_TAG', // 标的tag
        ]
    ];

    static $speedLoanDealType = [
        0 => '网贷',
        2 => '交易所',
        3 => '专享',
        5 => '小贷',
    ];


    /**
     * 根据配置节点类型查询相关的配置
     * @param string $configType 配置节点名称
     * @return array
     */
    public function getConfigByType($configType = 'config') {
        if (!in_array($configType, array_keys(self::$configKeys))) {
            return null;
        }
        $condition = " name IN ('".implode("','", self::$configKeys[$configType])."') ";
        $result = ConfModel::instance()->findAll($condition);
        $configData = [];
        foreach ($result as $confObject)
        {
            $configData[] = $confObject->getRow();
        }
        return $configData;
    }

    /**
     * 取指定配置组的以及配置值 数组
     */
    public function getConfigColums($configType = 'config') {
        $data = $this->getConfigByType($configType);
        $keyvalPairs = [];
        foreach ($data as $row)
        {
            $keyvalPairs[$row['name']] = $row['value'];
        }
        return $keyvalPairs;
    }
    /**
     * 根据配置名称查询配置项
     * @param string $configName 配置项名称
     * @return array
     */
    public function getConfig($configName) {
        $configName = addslashes(trim($configName));
        $condition = " name = '{$configName}' ";
        $configData = [];
        $result = ConfModel::instance()->findBy($condition);
        $configData = $result->getRow();
        return $configData;
    }

    /**
     * 更新配置项
     * @param array $data 要更新的配置K=>V方式
     * @return boolean true 更新成功 | false 更新失败
     */
    public function updateSetttings($data) {
        $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
        $db = \libs\db\Db::getInstance('firstp2p');
        $db->startTrans();
        try {
            foreach ($data as $configName => $value) {
                $value = addslashes($value);
                $configNameSafe = addslashes(trim($configName));
                $sql = "UPDATE firstp2p_conf SET value = '{$value}' WHERE name = '{$configNameSafe}'";
                $db->query($sql);
            }
            $db->commit();
        } catch (\Exception $e) {
            $message = "[{$adminInfo['adm_name']}]尝试更新银信通配置失败";
            PaymentApi::log($message);
            Alarm::push('speed_loan_config_fail', $message.$e->getMessage());
            $db->rollback();
            return false;
        }
        return true;
    }

}
