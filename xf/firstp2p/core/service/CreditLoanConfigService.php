<?php
/**
 * 信分期配置服务
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 **/

namespace core\service;

use core\dao\ConfModel;
use libs\utils\PaymentApi;
use libs\utils\Alarm;

class CreditLoanConfigService {
    static $configDelimiter = ';';

    static $configKeys = [
        'config' => [
            'CREDIT_LOAN_SWITCH', // 银信通开关
            'CREDIT_LOAN_SERVICE_FEE_UID', // 平台手续费收取账号
            'CREDIT_LOAN_SERVICE_RATE', // 平台服务费
            'CREDIT_LOAN_EXTRA_DAY', // 额外计息天数
            'CREDIT_LOAN_PROPORTION_LOAN_RATE', // 质押率
            'CREDIT_LOAN_MIN_BORROW_AMOUNT', // 单笔起借金额
            'CREDIT_LOAN_SUMMARY', // 持有资产总额
            'CREDIT_LOAN_HOLD_TERM_GE_3', // 标的持有时间对于大于等于三个月的标的
            'CREDIT_LOAN_HOLD_TERM_LT_3', // 标的持有时间对于小于三个月的标的
            'CREDIT_LOAN_REMAINNING_DAYS', // 在投标的剩余时间
            'CREDIT_LOAN_DEAL_REPAY_TYPE', // 标的还款方式包含 分号分隔
            'CREDIT_LOAN_DEAL_TYPE', // 标的类型 分号分隔
            'CREDIT_LOAN_BORROW_RATE', // 银信通借款利率 分号分隔
        ],
        'blacklist' => [
            'CREDIT_LOAN_BLACKLIST_SWITCH', // 黑名单开关
            'CREDIT_LOAN_BLACKLIST', // 黑名单列表;分隔
        ]
    ];

    static $creditLoanDealType = [
        'P2P',
        '通知贷',
        '大金所',
        '专享',
    ];


    static $creditLoanRate = [
        '1-3个月（含）',
        '3-6个月（含）',
        '6-12个月（含）',
        '12-24个月（含）',
        '24-36个月（含）',
    ];

    /**
     * ban 掉的标的还款方式
     */
    static $banDealRepayTypes = [
        1, // 按季等额本息还款
        2, // 按月等额本息还款
        7, // 公益资助
        8, // 等额本息固定日还款
        9, // 按月等额本金
        10, // 按季等额本金
    ];


    /**
     * ban 掉的标的类型
     */
    static $banDealTypes = [
        1, // 通知贷
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
            Alarm::push('credit_loan_config_fail', $message.$e->getMessage());
            $db->rollback();
            return false;
        }
        return true;
    }


    /**
     * 读取借款利率
     * @param integer $loanRateType 借款利率期限类型
     * @return floatval 借款利率
     */
    public function getLoanRate($loanRateType = 0) {
        $loanRate = app_conf('CREDIT_LOAN_BORROW_RATE');
        $loanRateArry = explode(self::$configDelimiter, $loanRate);
        return $loanRateArry[$loanRateType];
    }

    /**
     * 读取黑名单数据
     * @return array 黑名单数组
     */
    public function getBlackList() {
        $blackList = app_conf('CREDIT_LOAN_BLACKLIST');
        $blackListArry = explode(self::$configDelimiter, $blackList);
        return $blackListArry;
    }

}
