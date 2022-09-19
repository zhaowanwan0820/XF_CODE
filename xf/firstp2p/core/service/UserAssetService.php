<?php
/**
 * 用户个人资产服务
 * User: jinhaidong
 * Date: 2015/9/15 19:38
 */

namespace core\service;

use core\service\BonusService;
use core\service\DealCompoundService;

class UserAssetService extends BaseService {
    private $uid;
    private $userInfo;

    public function __construct($userInfo) {
        if(!is_array($userInfo) || empty($userInfo)) {
            throw new \Exception("params must be an user array");
        }

        $this->uid = $userInfo['id'];
        $this->userInfo = $userInfo;
    }

    /**
     * 个人资产统计
     */
    public function getUserAsset() {
        $data['money'] = $this->getAccountCash();               // 现金余额
        $data['lock_money'] = $this->getAccountLock();          // 冻结金额
        $data['bonus_money'] = $this->getAccountBonus();        // 红包金额
        $data['balance_money'] = $this->getAccountBalance();    // 账户可用余额
        $data['load_money'] = $this->getLoadMoney();            // 总投资额
        $data['principal'] = $this->getUncollectedPrincipal();  // 待收本金/已投资产
        $data['interest'] = $this->getUncollectedInterest();    // 待收利息
        $data['earning_interest'] = $this->getEarningInterest();// 已赚利息
        $data['earning_all'] = $this->getEarningAll();          // 已赚取得总收益
        $data['total_money'] = $this->getUserTotalMoney();      // 个人资产总额

        $compoundMoney = $this->getCompoundMoney();
        $data['compound_money'] = $compoundMoney['compound_money'];     // 通知贷代收本金
        $data['compound_repay_money'] = $compoundMoney['repay_money'];  // 通知贷可赎回金额
        $data['compound_interest'] = $compoundMoney['interest'];        // 通知贷待收利息
        return $data;
    }

    /**
     * 账户余额
     * 现金金额+红包金额
     */
    public function getAccountBalance() {
        return $this->getAccountCash() + $this->getAccountBonus();
    }

    /**
     * 用户现金余额
     */
    public function getAccountCash() {
        return $this->userInfo['money'];
    }
    
    /**
     * 用户冻结金额
     */
    public function getAccountLock() {
        return $this->userInfo['lock_money'];
    }

    /**
     * 用户可用红包金额
     */
    public function getAccountBonus() {
        $bs = new BonusService();
        $bonus = $bs->get_useable_money($this->uid);
        return $bonus['money'] ? $bonus['money'] : 0;
    }

    /**
     * 用户投资总额(不包含流标)
     */
    public function getLoadMoney() {
        $sql = "SELECT SUM(d_l.money) AS load_money FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status in (4,5) AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = {$this->uid}";
        $u_load = $GLOBALS['db']->get_slave()->getRow($sql);
        return $u_load['load_money'];
    }

    /**
     * 用户已赚取总收益
     * 总收益(不含待收收益) 提前还款违约金+逾期罚息+ 已赚利息
     * type: 2-利息,4-提前还款补偿金,5-逾期罚息,7-提前还款利息,9-利滚利赎回利息
     */
    public function getEarningAll() {
        $sql = "SELECT sum(money) as money FROM ".DB_PREFIX."deal_loan_repay  WHERE loan_user_id={$this->uid} AND status = 1 AND type IN (2,4,5,7,9)";
        return $GLOBALS['db']->get_slave()->getOne($sql);
    }

    /**
     * 用户已赚取利息
     */
    public function getEarningInterest() {
        $sql = "SELECT sum(money) as money FROM ".DB_PREFIX."deal_loan_repay  WHERE loan_user_id={$this->uid} AND status = 1 AND type IN (2,7,9)";
        return $GLOBALS['db']->get_slave()->getOne($sql);
    }

    /**
     * 用户待收利息
     */
    public function getNormalUncollected() {
        $sql = "SELECT sum(money) as money FROM ".DB_PREFIX."deal_loan_repay WHERE loan_user_id={$this->uid} AND status = 0 AND type IN (2,9)";
        return $GLOBALS['db']->get_slave()->getOne($sql);
    }

    /**
     * 用户未赎回的通知贷相关money
     */
    public function getCompoundMoney() {
        $dcs = new DealCompoundService;
        $dcsMoney = $dcs->getUserCompoundMoney($this->uid, get_gmtime());
        return $dcsMoney;
    }

    /**
     * 用户待收利息
     * 待收利息+利滚利标未赎回待收利息
     */
    public function getUncollectedInterest() {
        $compoundMoney = $this->getCompoundMoney();
        return $this->getNormalUncollected() + $compoundMoney['interest'];
    }

    /**
     * 用户待收本金
     */
    public function getUncollectedPrincipal() {
        $sql = "SELECT sum(money) as money FROM ".DB_PREFIX."deal_loan_repay WHERE loan_user_id={$this->uid} AND status = 0 AND type IN (1,8)";
        return $GLOBALS['db']->get_slave()->getOne($sql);
    }

    /**
     * 用户个人资产总额
     * 资产总额(不含红包和待收收益):
     * userInfo['money']+userinfo['lock_money']+ principa(待收本金)
     */
    public function getUserTotalMoney() {
        return $this->getAccountCash() + $this->getAccountLock() + $this->getUncollectedPrincipal();
    }
}