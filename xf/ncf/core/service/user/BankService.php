<?php

namespace core\service\user;

use core\service\BaseService;

class BankService extends BaseService {
    /**
     * 函数列表
     */
    private static $funcMap = array(
        'getBankInfoByBankId' => array('bankId', 'fields'), // 根据银行卡联行号查询银行基本信息
        'getBankInfoByName' => array('name'),               // 获取根据银行名字银行卡信息
        'getBankInfoByCode' => array('code'),               // 通过code获取银行卡信息
        'getBankIssueByName' => array('bankzone'),          // 根据支行名称查询联行号
        'getNewCardByUserId' => array('userId', 'fields'),  // 获取用户最新的绑卡信息
        'getBranchInfoByBranchNo' => array('branchNo', 'fields'), // 根据银行卡联行号查询银行支行信息
        // 根据状态获取银行列表，排序顺序为推荐度、排序位置和ID
        'getAllByStatusOrderByRecSortId' => array('status'),
        'getBankListByUserIds' => array('userIds'), // 批量获取用户银行卡信息
        'getUserBankInfo' => array('userId'), // 获取用户银行卡数据
        'getUserBankLogoInfo' => array('userId'), // 获取用户银行名称/logo等信息
        'getBankUserByPaymentMethod' => array(), // 获取推荐的银行列表

        'canBankcardBind' => array('cardNo', 'userId'), //判断指定银行卡可否被该uid绑定，是否没被其他用户占用
        'insertUserBankCard' => array('data', 'ucfpayData'), //添加用户绑卡记录
        'searchCardBin' => array('cardNo'), //根据银行卡号，获取卡信息
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        // 用户中心的api接口
        $userCenterApiArr = array('getBankInfoByBankId', 'getBankInfoByName', 'getBankIssueByName',
            'getNewCardByUserId', 'getUserBankInfo', 'searchCardBin');
        if (in_array($name, $userCenterApiArr)) {
            return self::rpc('user', 'bank/'.$name, $args);
        }

        return self::rpc('ncfwx', 'bank/'.$name, $args);
    }
}
