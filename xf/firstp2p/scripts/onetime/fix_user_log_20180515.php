<?php

/*
 * 存管调账
 * 借款人资金错误
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';

use core\dao\DealModel;
use core\dao\UserModel;


$money = '3044.28';
$db = \libs\db\Db::getInstance('firstp2p', 'master');
try {
    // 张建 调账
    $userZhangId    = '11413972';
    // 卓李劲 调账
    $userZhuoId     = '11343408';
    // 担保账户 调账
    $guarantyUserId = '9466422';

    $db->startTrans();

    $user = UserModel::instance()->find($userZhangId);
    if (!$user)
    {
        throw new \Exception('用户11413972不存在');
    }
    $user->changeMoney($money, '冲正', '账务错误，冲正', 0, 0, UserModel::TYPE_MONEY);
    $user->changeMoney($money, '转账申请', '您的账户向会员jg_dszg的账户转入金额3044.28元 账务错误', 0, 0, UserModel::TYPE_LOCK_MONEY);
    $user->changeMoney($money, '转出资金', '您的账户向会员jg_dszg的账户转入金额3044.28元 账务错误', 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);

    $userGuranty = UserModel::instance()->find($guarantyUserId);
    if (!$userGuranty)
    {
        throw new \Exception('用户'.$guarantyUserId.'不存在');
    }
    $userGuranty->changeMoney($money, '转入资金', '会员m23409116482的账户向您的账户转入金额3044.28元 账务错误', 0, 0, UserModel::TYPE_MONEY);
    $userGuranty->changeMoney($money, '转账申请', '您的账户向会员m42496912558的账户转入金额3044.28元 手续费、利息划转', 0, 0, UserModel::TYPE_LOCK_MONEY);
    $userGuranty->changeMoney($money, '转出资金', '您的账户向会员m42496912558的账户转入金额3044.28元 手续费、利息划转', 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);


    $userZhuo = UserModel::instance()->find($userZhuoId);
    if (!$userZhuo)
    {
        throw new \Exception('用户11343408不存在');
    }
    $userZhuo->changeMoney($money, '转入资金', '会员jg_dszg的账户向您的账户转入金额3044.28元 手续费、利息划转', 0, 0, UserModel::TYPE_MONEY);
    $userZhuo->changeMoney($money, '余额划转申请', '网信理财账户余额划转到网贷P2P账户余额', 0, 0, UserModel::TYPE_LOCK_MONEY);
    $userZhuo->changeMoney($money, '余额划转成功', '网信理财账户余额划转到网贷P2P账户余额', 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);

    $db->commit();
    echo 'success';
} catch (\Exception $e) {
    $db->rollback();
    echo $e->getMessage();
}
