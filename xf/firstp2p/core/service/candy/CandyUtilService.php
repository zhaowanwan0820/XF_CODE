<?php

namespace core\service\candy;

use core\service\candy\CandyAccountService;
use libs\db\Db;
use core\dao\DealModel;
use libs\utils\Logger;
use libs\utils\Rpc;
use core\dao\DealLoadModel;

class CandyUtilService
{
    // 限制年化
    const LIMIT_DEAL_AMOUNT_ANNUALIZED = 2;

    //虚拟用户ID前缀
    const PREFIX_ROBOT = 10000000;

    //信宝夺宝虚拟用户信息
    private static $robotUserInfo = array(
        1000000001 => array('id' => 1000000001, 'real_name' => '王', 'sex' => 1, 'mobile' => '139****4364'),
        1000000002 => array('id' => 1000000002, 'real_name' => '李', 'sex' => 0, 'mobile' => '139****5732'),
        1000000003 => array('id' => 1000000003, 'real_name' => '张', 'sex' => 1, 'mobile' => '183****5364'),
        1000000004 => array('id' => 1000000004, 'real_name' => '刘', 'sex' => 0, 'mobile' => '187****2363'),
        1000000005 => array('id' => 1000000005, 'real_name' => '陈', 'sex' => 1, 'mobile' => '151****2367'),
        1000000006 => array('id' => 1000000006, 'real_name' => '杨', 'sex' => 0, 'mobile' => '181****9647'),
        1000000007 => array('id' => 1000000007, 'real_name' => '黄', 'sex' => 1, 'mobile' => '188****5487'),
        1000000008 => array('id' => 1000000008, 'real_name' => '赵', 'sex' => 0, 'mobile' => '152****3853'),
        1000000009 => array('id' => 1000000009, 'real_name' => '周', 'sex' => 1, 'mobile' => '151****8754'),
        1000000010 => array('id' => 1000000010, 'real_name' => '吴', 'sex' => 0, 'mobile' => '186****3896'),
        1000000011 => array('id' => 1000000011, 'real_name' => '徐', 'sex' => 1, 'mobile' => '187****2345'),
        1000000012 => array('id' => 1000000012, 'real_name' => '孙', 'sex' => 0, 'mobile' => '189****6543'),
        1000000013 => array('id' => 1000000013, 'real_name' => '马', 'sex' => 1, 'mobile' => '182****8953'),
        1000000014 => array('id' => 1000000014, 'real_name' => '胡', 'sex' => 0, 'mobile' => '152****5095'),
        1000000015 => array('id' => 1000000015, 'real_name' => '朱', 'sex' => 1, 'mobile' => '151****1496'),
        1000000016 => array('id' => 1000000016, 'real_name' => '郭', 'sex' => 0, 'mobile' => '139****3689'),
        1000000017 => array('id' => 1000000017, 'real_name' => '何', 'sex' => 1, 'mobile' => '152****4456'),
        1000000018 => array('id' => 1000000018, 'real_name' => '罗', 'sex' => 0, 'mobile' => '187****1999'),
        1000000019 => array('id' => 1000000019, 'real_name' => '高', 'sex' => 1, 'mobile' => '185****5611'),
        1000000020 => array('id' => 1000000020, 'real_name' => '林', 'sex' => 0, 'mobile' => '183****4799'),
        1000000021 => array('id' => 1000000021, 'real_name' => '郑', 'sex' => 1, 'mobile' => '188****2322'),
        1000000022 => array('id' => 1000000022, 'real_name' => '梁', 'sex' => 0, 'mobile' => '182****3211'),
        1000000023 => array('id' => 1000000023, 'real_name' => '谢', 'sex' => 1, 'mobile' => '183****6477'),
        1000000024 => array('id' => 1000000024, 'real_name' => '唐', 'sex' => 0, 'mobile' => '139****4795'),
        1000000025 => array('id' => 1000000025, 'real_name' => '许', 'sex' => 1, 'mobile' => '152****2478'),
        1000000026 => array('id' => 1000000026, 'real_name' => '冯', 'sex' => 0, 'mobile' => '139****5968'),
        1000000027 => array('id' => 1000000027, 'real_name' => '宋', 'sex' => 1, 'mobile' => '139****1255'),
        1000000028 => array('id' => 1000000028, 'real_name' => '韩', 'sex' => 0, 'mobile' => '151****4724'),
        1000000029 => array('id' => 1000000029, 'real_name' => '邓', 'sex' => 1, 'mobile' => '187****8064'),
        1000000030 => array('id' => 1000000030, 'real_name' => '彭', 'sex' => 0, 'mobile' => '183****6090'),
        1000000031 => array('id' => 1000000031, 'real_name' => '曹', 'sex' => 1, 'mobile' => '186****1207'),
        1000000032 => array('id' => 1000000032, 'real_name' => '曾', 'sex' => 0, 'mobile' => '187****6085'),
        1000000033 => array('id' => 1000000033, 'real_name' => '田', 'sex' => 1, 'mobile' => '182****4906'),
        1000000034 => array('id' => 1000000034, 'real_name' => '于', 'sex' => 0, 'mobile' => '152****7009'),
        1000000035 => array('id' => 1000000035, 'real_name' => '萧', 'sex' => 1, 'mobile' => '152****2560'),
        1000000036 => array('id' => 1000000036, 'real_name' => '潘', 'sex' => 0, 'mobile' => '151****6390'),
        1000000037 => array('id' => 1000000037, 'real_name' => '袁', 'sex' => 1, 'mobile' => '151****4705'),
        1000000038 => array('id' => 1000000038, 'real_name' => '董', 'sex' => 0, 'mobile' => '185****3508'),
        1000000039 => array('id' => 1000000039, 'real_name' => '叶', 'sex' => 1, 'mobile' => '173****5580'),
        1000000040 => array('id' => 1000000040, 'real_name' => '杜', 'sex' => 0, 'mobile' => '181****6835'),
        1000000041 => array('id' => 1000000041, 'real_name' => '丁', 'sex' => 1, 'mobile' => '173****1153'),
        1000000042 => array('id' => 1000000042, 'real_name' => '蒋', 'sex' => 0, 'mobile' => '152****6806'),
        1000000043 => array('id' => 1000000043, 'real_name' => '程', 'sex' => 1, 'mobile' => '188****5387'),
        1000000044 => array('id' => 1000000044, 'real_name' => '余', 'sex' => 0, 'mobile' => '186****4086'),
        1000000045 => array('id' => 1000000045, 'real_name' => '吕', 'sex' => 1, 'mobile' => '183****7090'),
        1000000046 => array('id' => 1000000046, 'real_name' => '魏', 'sex' => 0, 'mobile' => '185****3689'),
        1000000047 => array('id' => 1000000047, 'real_name' => '蔡', 'sex' => 1, 'mobile' => '182****2487'),
        1000000048 => array('id' => 1000000048, 'real_name' => '苏', 'sex' => 0, 'mobile' => '183****7523'),
        1000000049 => array('id' => 1000000049, 'real_name' => '任', 'sex' => 1, 'mobile' => '139****2236'),
        1000000050 => array('id' => 1000000050, 'real_name' => '卢', 'sex' => 0, 'mobile' => '151****1417'),
    );

    /**
     * 获得虚拟用户信息
     */
    public static function getRobotUserInfo(array $userIds)
    {
        $userInfo = array();
        foreach ($userIds as $value) {
            if (self::isRobotUser($value)) {
                $userInfo[] = self::$robotUserInfo[$value];
            }
        }
        return $userInfo;
    }

    /**
     * 随机获得用户信息
     */
    public static function getRandRobotUserInfo()
    {
        return self::$robotUserInfo[array_rand(self::$robotUserInfo)];
    }

    public static function isRobotUser($id)
    {
        return isset(self::$robotUserInfo[$id]);
    }

    /**
     * 用户当日投资金额
     */
    public static function getUserInvestAmountToday($userId, $amountType)
    {
        return self::getUserInvestAmount($userId, $amountType, strtotime(date("Ymd")));
    }

    /**
     * 虚拟币兑信宝数量
     * $coinRate 信宝兑虚拟币的汇率
     * $coinAmount 兑换的虚拟币数量
     */
    public static function calcAmountByCoin($coinRate, $coinAmount, $coinDecimals)
    {
        // 信宝取整所需数字
        $candyToInt = pow(10, CandyAccountService::AMOUNT_DECIMALS);
        // 汇率计算最大保留精度
        $precision = $coinDecimals - CandyAccountService::AMOUNT_DECIMALS + ceil(log10($coinRate));
        // 计算信宝消费金额，最小为1
        $amount = ceil(bcdiv($coinAmount * $candyToInt, $coinRate, $precision));
        // 返回信宝真是数量 最小0.001
        return bcdiv($amount, $candyToInt, CandyAccountService::AMOUNT_DECIMALS);
    }

    /**
     * 用户投资统计
     */
    public static function getUserInvestAmount($userId, $amountType, $startTime)
    {
        $createTime = $startTime - date('Z');
        $userDealLoads = Db::getInstance('firstp2p')->getAll("SELECT deal_id, money FROM firstp2p_deal_load WHERE create_time >= '{$createTime}' AND user_id = '{$userId}'");

        $userDealMoney = 0;
        foreach ($userDealLoads as $dealLoad) {
            $deal = DealModel::instance()->find($dealLoad['deal_id'], 'loantype,repay_time');
            if ($deal['loantype']  != 5) {
                $deal['repay_time'] = $deal['repay_time'] * 30;
            }

            // 如果是年化，转化成年化金额
            if ($amountType == self::LIMIT_DEAL_AMOUNT_ANNUALIZED) {
                $dealLoad['money'] = $dealLoad['money'] * $deal['repay_time'] / 360;
            }
            $userDealMoney += $dealLoad['money'];
        }

        //加上普惠投资数据
        $phLoads = \core\service\ncfph\DealLoadService::getUserLoadMoneyStat($userId, $startTime);
        if ($amountType == self::LIMIT_DEAL_AMOUNT_ANNUALIZED) {
            $phMoney = $phLoads['moneyRate'];
        } else {
            $phMoney = $phLoads['money'];
        }

        Logger::info("getUserInvestAmount. user_id: {$userId}, wx: {$userDealMoney}, ph: {$phMoney}");
        return $userDealMoney + $phMoney;
    }

    /**
     * 判断用户是否投过资（不包含智多鑫）
     */
    public static function hasLoan($userId)
    {
        // 普通标的
        $userFirstDeal = DealLoadModel::instance()->getFirstDealByUser($userId);
        if (!empty($userFirstDeal)) {
            return true;
        }

        // 普惠是否投资
        $ncfPhFirstDeal = \core\service\ncfph\DealLoadService::getFirstDealByUser($userId);
        if (!empty($ncfPhFirstDeal)) {
            return true;
        }

        Logger::info("candy user is limited. userId:{$userId}");
        return false;
    }

    /**
     * 自动赠送夺宝机会（周六～周日，每天赠送1个夺宝机会）
     */
    public static function presentSnatchCodePerWeek()
    {
        if (date("N") >= 6 && date("N") <= 7) {
            return 1;
        }

        return 0;
    }

}
