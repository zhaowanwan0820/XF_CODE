<?php
/**
 *  UserCarryService
 * @author caolong <caolong@ucfgroup.com>
 **/

namespace core\service;

use core\dao\UserCarryModel;
use core\dao\UserBankcardModel;
use core\dao\SupervisionWithdrawModel;
use core\dao\WithdrawLimitModel;
use core\dao\UserModel;
use core\dao\FinanceQueueModel;
use libs\utils\PaymentApi;
use core\service\UserService;
use core\service\MsgBoxService;
use libs\utils\Logger;
use libs\utils\Alarm;
use core\dao\DealModel;
use core\dao\DealLoanTypeModel;
use core\dao\PaymentNoticeModel;
use core\service\SupervisionAccountService;
use core\service\SupervisionBaseService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use NCFGroup\Common\Library\ApiService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;

use core\service\AccountLimitService;
use core\service\ncfph\SupervisionService as PhSupervisionService;
/**
 * UserFeedback service
 *
 * @packaged default
 * @author 温彦磊 <wenyanlei@ucfgroup.com>
 **/
class UserCarryService extends BaseService
{

    const WITHDRAW_LIMIT_INIT = 0;
    const WITHDRAW_LIMIT_INVIEWING = 1;
    const WITHDRAW_LIMIT_PASSED = 2;
    const WITHDRAW_LIMIT_REFUSED = 3;

    const WITHDRAW_LIMIT_CANCEL_NONE = 0;
    const WITHDRAW_LIMIT_CANCEL_INIT = 1;
    const WITHDRAW_LIMIT_CANCEL_PASSED = 2;
    const WITHDRAW_LIMIT_CANCEL_REFUSED = 3;

    const WITHDRAW_LIMIT_TYPE_T1 = 0;
    const WITHDRAW_LIMIT_TYPE_T2 = 1;
    const WITHDRAW_LIMIT_TYPE_T3 = 2;
    const WITHDRAW_LIMIT_TYPE_T4 = 3;

    const WITHDRAW_LIMIT_STATUS_T1 = 1;
    const WITHDRAW_LIMIT_STATUS_T2 = 2;
    const WITHDRAW_LIMIT_STATUS_T3 = 3;
    const WITHDRAW_LIMIT_STATUS_CANCEL = 4;
    const WITHDRAW_LIMIT_STATUS_FINISH = 5;

    /**
     * 提现限制状态描述
     */
    static $withdrawLimitCn = array(
        self::WITHDRAW_LIMIT_INIT => '提交申请',
        self::WITHDRAW_LIMIT_INVIEWING => '等待审核',
        self::WITHDRAW_LIMIT_PASSED => '通过申请',
        self::WITHDRAW_LIMIT_REFUSED => '拒绝申请',
    );

    /**
     * 限制提现类型
     */
    static $withdrawLimitTypeCn = array(
        self::WITHDRAW_LIMIT_TYPE_T1 => '变现通',
        self::WITHDRAW_LIMIT_TYPE_T2 => '贷后管理',
        self::WITHDRAW_LIMIT_TYPE_T3 => '法律合规',
        self::WITHDRAW_LIMIT_TYPE_T4 => '其他'
    );


    /**
     * 还款状态
     */
    static $withdrawLimitStatusCn = array(
        self::WITHDRAW_LIMIT_STATUS_T1 => '未还款',
        self::WITHDRAW_LIMIT_STATUS_T2 => '还款中',
        self::WITHDRAW_LIMIT_STATUS_T3 => '已还清',
        self::WITHDRAW_LIMIT_STATUS_CANCEL => '已取消',
        self::WITHDRAW_LIMIT_STATUS_FINISH => '已提清',

    );

    /**
     * 限制提现检查
     */
    static public $checkWithdrawLimit = true;

    /**
     * 获取相同记录
     * @param unknown $time
     * @param unknown $useId
     * @param unknown $logInfo
     */
    public function getAlikes($userId,$logInfo) {
        return UserLogModel::instance()->getLogMoneyInfo($userId, $logInfo);
    }

    /**
     * 获取对应的报警状态值
     * getWarningStat
     *
     * @param integer $userId
     * @param float withdrawAmount
     * @param string $userRealName
     * @param string $cardName
     * @access public
     * @return integer
     */
    public function getWarningStat($userId, $withdrawAmount, $userRealName = '', $cardName = '') {
        // 取消提现异常检查
        return 0;

        if (!$userId || !$withdrawAmount) {
            throw new \Exception('Invild Argument!');
        }

        $userId = intval($userId);

        $stat = 0;
        // 获取有无连续相同记录
        //$sql = 'SELECT money, create_time FROM ' . DB_PREFIX . 'user_carry WHERE user_id=' . $userId . ' ORDER BY id DESC LIMIT 2';
        //$carrys = $GLOBALS['db']->getAll($sql);
        //$count = count($carrys);
        //if ( $count > 0 && bccomp($carrys[0]['money'], $withdrawAmount, 2) === 0) {
        //    $stat ^= UserCarryModel::WARNING_SAME_CARRY;
        //}
        // 获取24小时内有无两条记录
        //$currentTime = get_gmtime();
        //if ($count === 2 && ($currentTime - $carrys[0]['create_time'] <= 3600 * 24) && ($currentTime - $carrys[1]['create_time'] <= 3600 *24)) {
        //    $stat ^= UserCarryModel::WARNING_TWO_CARRY;
        //}
        // 获取有无充值记录
        $sql = 'SELECT count(id) AS count FROM ' . DB_PREFIX . 'payment_notice WHERE user_id=' . $userId . ' AND money >0 AND is_paid = 1';
        $payment = $GLOBALS['db']->getRow($sql);
        if ($payment['count'] == 0 && bccomp($withdrawAmount, '5.00', 2) < 1) {
            $stat ^= UserCarryModel::WARNING_NO_CHARGE;
        }
        // 获取提现人姓名是否和开户名一致
        //if (!$userRealName) {
        //    $sql = 'SELECT real_name FROM ' . DB_PREFIX . 'user WHERE id = ' . $userId;
        //    $user = $GLOBALS['db']->getRow($sql);
        //    if ($user) {
        //        $userRealName = $user['real_name'];
        //    }
        //}

        //if (!$cardName) {
        //    $bankcardInfo = UserBankcardModel::instance()->getByUserId($userId);
        //    if ($bankcardInfo) {
        //        $cardName = $bankcardInfo['card_name'];
        //    }
        //}

        //TODO 我能说这个trim是因为前面没过滤好，我这边必须得加的么。。
        //if (!$userRealName || !$cardName || trim($userRealName) != trim($cardName)) {
        //    $stat ^= UserCarryModel::WARNING_NAME_INCONSISTENT;
        //}
        // 判断金额是否大于限制值
        //$moneyLimit = floatval(app_conf('PAYMENT_AUTO_AUDIT'));

        //if (bccomp($withdrawAmount, $moneyLimit, 2) === 1) {
        //    $stat ^= UserCarryModel::WARNING_MONEY_OVER_LIMIT;
        //}

        // 第一次充值后无投资，直接提现
        //$sql = 'SELECT count(id) AS count FROM ' . DB_PREFIX . 'deal_load WHERE user_id = ' . $userId . ' AND money > 0';
        //$deal = $GLOBALS['db']->getRow($sql);
        //if ($payment['count'] > 0 && $deal['count'] == 0) {
        //    $stat ^= UserCarryModel::WARNING_NO_DEAL;
        //}

        return $stat;
    }

    /**
     * 获取警告信息
     * getWarningInfo
     *
     * @param intger $warningStat
     * @access public
     * @return string
     */
    public function getWarningInfo($warningStat, $split = "<br>", $moneyLimit = 0) {

        if (!$warningStat) {
            return '';
        }

        $resultInfo = '';

        foreach (UserCarryModel::$warningMap as $key => $info) {
            if (($warningStat & $key) == $key) {
                $resultInfo .= $info . $split;
            }
        }

        $resultInfo = sprintf(trim($resultInfo, $split), $moneyLimit);
        return $resultInfo;
    }

    /**
     * 拒绝提现
     */
    public function doRefuse($id = 0, $amountCheck = 1) {

        $GLOBALS['db']->startTrans();
        try {
            $id = intval($id);
            $userCarryData = null;
            if ($id) {
                $userCarryData = UserCarryModel::instance()->find($id);
            }
            $tpl = 'TPL_SMS_ACCOUNT_CASHOUT_FAIL_NEW';
            // 处理失败
            $userService = new UserService();
            $user = $userService->getUser($userCarryData['user_id']);
            $realChangeMoney = bcadd($userCarryData['money'], $userCarryData['fee'], 2);
            $withdrawStatus = UserCarryModel::WITHDRAW_STATUS_FAILED;
            $withdrawMsg = '提现失败';
            $toUpdate['status'] = 2;
            $se = \es_session::get(md5(conf("AUTH_KEY")));
            $adm_name = $se['adm_name'];
            $toUpdate['desc'] = $userCarryData['desc'] . '<p>风控'.$adm_name.'拒绝提现</p>';
            $bizToken = ['orderId' => $userCarryData['id']];
            $user->changeMoney(-$realChangeMoney,'提现失败', '提现请求被拒绝，如有疑问请拨打客服热线' . $GLOBALS['sys_config']['SHOP_TEL'] . '。', 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            $content = sprintf("您于%s提交的%s提现请求被拒绝。如有疑问请拨打客服热线 %s。", to_date($userCarryData['create_time'],"Y年m月d日 H:i:s"), format_price($userCarryData['money']), $GLOBALS['sys_config']['SHOP_TEL']);
            $condition = ' id  = ' . $userCarryData['id']. ' AND status in (0,1)';
            // 风控支持拒绝自动队列中的
            if ($amountCheck == 2) {
                $condition = ' id  = ' . $userCarryData['id']. ' AND status in (0,1,3) AND withdraw_status = 0';
            }
            $GLOBALS['db']->autoExecute('firstp2p_user_carry', $toUpdate, 'UPDATE', $condition);
            if ($GLOBALS['db']->affected_rows() < 1)
            {
                throw new \Exception("数据库更新失败");
            }
            //短信通知
            //if(app_conf("SMS_ON") == 1){
            //    $params = array(
            //        'money' => format_price($userCarryData['money']),
            //    );
            //    require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
            //    $msgcenter = new \Msgcenter();
            //    $msgcenter->setMsg($user['mobile'], $user['id'], $params, 'TPL_SMS_ACCOUNT_CASHOUT_FAIL');
            //    $msgcenter->save();
            //}



            //$group_arr = array(0, $userCarryData['user_id']);
            //$group_arr[] =  6;
            //sort($group_arr);

            //$msg_data['content'] = $content;
            //$msg_data['to_user_id'] = $userCarryData['user_id'];
            //$msg_data['create_time'] = get_gmtime();
            //$msg_data['type'] = 0;
            //$msg_data['group_key'] = implode('_', $group_arr);
            //$msg_data['is_notice'] = 6;

            //$result = $GLOBALS['db']->autoExecute(DB_PREFIX . 'msg_box',$msg_data);
            $GLOBALS['db']->commit();
        }
        catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * doPass
     * 提现审批并发起支付提现申请
     * !! 前后台都调用此方法，但是前台发起提现申请不需要做余额检查 edit by qunqiang
     * @param array $userCarryData 提现数据
     * @param string $id 提现id
     * @param integer $amountCheck 是否进行支付端余额检查，默认为检查
     * @access public
     * @return boolean
     * 支付提现接口返回状态码
     * respCode    00服务调用成功|01服务调用失败
     * status    00 成功|01 失败|02 参数错误|03 查询失败|04 查询用户失败|T18提现失败|T19提现处理中
     */
    public function doPass($userCarryData = null, $id = 0, $amountCheck = 1) {
        $id = intval($id);
        if (!$userCarryData && $id) {
            $userCarryData = UserCarryModel::instance()->find($id);
        }

        if ($userCarryData && !$id) {
            $id = $userCarryData['id'];
        }

        if ((!$userCarryData || !$id) || ($userCarryData['id'] != $id)) {
            throw new \Exception('参数错误!');
        }
        //提现
        // TODO finance 后台 提现成功 |  手续费转账给平台，如果转账手续费小于等于0 ，则不转账
        $syncRemoteData = array();
        if (bccomp($userCarryData['fee'], '0.00', 2) > 0) {
            $syncRemoteData[] = array(
                'outOrderId' => 'WITHDRAW|' . $userCarryData['id'],
                'payerId' => $userCarryData['user_id'],
                'receiverId' => app_conf('DEAL_CONSULT_FEE_USER_ID'),
                'repaymentAmount' => bcmul($userCarryData['fee'], 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => FinanceQueueModel::PAYQUEUE_BIZTYPE_3,
                'batchId' => '',
            );
            FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
        }
        // 如果开启对接先锋支付启用验证
        if (app_conf('PAYMENT_ENABLE')) {
            // TODO finance 请求先锋支付提现放款接口 | 把提现金额打入账户
            $user_id = intval($userCarryData['user_id']);

            $bankCardNo = $userCarryData['bankcard'];
            $bank_id = intval($userCarryData['bank_id']);

            // 联行号
            $user_bank_info = UserBankcardModel::instance()->getNewCardByUserId($user_id, 'bank_id,bankzone,card_name,card_type,bankcard');
            $bank_zone = $user_bank_info['bankzone'];
            $bank_zone_sn = '00000000';

            // 开户人姓名
            $card_user_name = $user_bank_info['card_name'];

            // 支持企业用户提现(http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-3552)
            $userInfo = $GLOBALS['db']->get_slave()->getRow("SELECT mobile_code,mobile,user_type FROM firstp2p_user WHERE id = '{$userCarryData['user_id']}'");
            $withdrawalType = 1;
            //if ((!empty($userInfo['mobile']) && substr($userInfo['mobile'], 0, 1) == '6'
            //    && (empty($userInfo['mobile_code']) || $userInfo['mobile_code'] == '86'))
            //    || (isset($userInfo['user_type']) && $userInfo['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE))
            //{
            //    $withdrawalType = 2;
            //}
            if ($user_bank_info['card_type'] == UserBankcardModel::CARD_TYPE_BUSINESS) {
                $withdrawalType = 2;
            }

            //借款人受托支付逻辑
            $dealInfo = array();
            $projectInfo = array();
            // 提现备注
            $withdrawRemark = '';
            $dealId = intval($userCarryData['deal_id']);
            if ($dealId > 0) {
                $dealInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_deal WHERE id='{$dealId}'");
                $projectInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_deal_project WHERE id='{$dealInfo['project_id']}'");
                // 如果是放款提现并且是交易所标的,则提取交易所备案产品编号
                $withdrawRemark = !empty($dealInfo['jys_record_number']) ? trim($dealInfo['jys_record_number']) : '';
                //如果放款方式是受托支付
                if (isset($projectInfo['loan_money_type']) && $projectInfo['loan_money_type'] == 3) {
                    $card_user_name = $projectInfo['card_name'];
                    $bankCardNo = $projectInfo['bankcard'];
                    $bank_id = $projectInfo['bank_id'];
                    $bank_zone = $projectInfo['bankzone'];
                    if ($projectInfo['card_type'] == UserBankcardModel::CARD_TYPE_BUSINESS) {
                        $withdrawalType = 2;
                    }
                }
            }
            // 根据标的ID，获取支付的bizType信息
            $bizTypeInfo = self::getBizTypeByDealId($dealId, $dealInfo, $withdrawalType);

            //银行卡信息补全
            $bank_detail = $GLOBALS['db']->getRow("SELECT name,short_name FROM " . DB_PREFIX . "bank WHERE id ='{$bank_id}'");
            if (empty($bank_detail)) {
                //throw new \Exception('提现失败，提现银行不存在' . json_encode($response), 1);
                $bank_detail['name'] = '中国工商银行';
                $bank_detail['short_name'] = 'ICBC';
            }
            $bank_name = $bank_detail['name'];
            $bank_code = $bank_detail['short_name'];

            //联行号信息补全
            //jira-1363  建设银行以及非以62开头的银行卡，读取联行号信息，否则默认8个0
            //jira-3940 以62开头的银行卡联行号规则变更(按照实际的联行号传输给支付，有联行号的，直接传输，没有联行号的默认为8个0)
            if (!empty($bank_zone))
            {
                $user_bank_zone = $GLOBALS['db']->get_slave()->getOne("SELECT bank_id FROM " . DB_PREFIX . "banklist WHERE name = '{$bank_zone}'");
                if (!empty($user_bank_zone)) {
                    $bank_zone_sn = $user_bank_zone;
                }
            }

            $withdrawReq = array(
                'userId' => $userCarryData['user_id'],
                'userName' => $card_user_name,
                'amount' => bcmul($userCarryData['money'], 100),
                'curType' => 'CNY',
                'outOrderId' => $userCarryData['id'],
                'withdrawalType' => $withdrawalType, // 1:个人2:企业
                'bankCardNo' => $bankCardNo, // 银行卡号
                'bankName' => $bank_name, // 银行名称
                'bankCode' => $bank_code, // 银行编号
                'bankCardIssuer' => $bank_zone_sn, // 发卡行联行号
                'bankCardIssuerName' => $bank_zone,
                'reqBizType' => $bizTypeInfo['bizType'], // 根据标的ID，获取先锋支付的bizType
            );
            // 放款提现增加备注信息
            if (!empty($withdrawRemark))
            {
                $withdrawReq['reqReserved'] = $withdrawRemark;
            }
            $response = PaymentApi::instance()->request('towithdrawal', $withdrawReq);
            $statDesc = array(
                '00' => '[成功]',
                '01' => '[失败]',
                '02' => '[参数错误]',
                '03' => '[查询失败]',
                '04' => '[查询用户失败]',
                'T18' => '[提现失败]',
                'T19' => '[提现处理中]',
            );
            // 提现出现问题
            $now = get_gmtime();
            if (empty($response)) {
                throw new \Exception('提现接口超时', 2);
            } else if ((isset($response['respCode']) && $response['respCode'] != '00' )
                || (isset($response['status']) && ($response['status'] != '00' && $response['status'] != 'T19'))) {
                $withdraw_data = array(
                    'withdraw_status' => UserCarryModel::WITHDRAW_STATUS_FAILED,
                    'withdraw_msg' => addslashes(preg_replace(array('/\w+?/', '/，/', '/:/'), array('', '', ''), $response['respMsg'])),
                    'withdraw_time' => get_gmtime(),
                    'id' => $id,
                );
                $withdraw_rs = $GLOBALS['db']->query("UPDATE  ".DB_PREFIX."user_carry SET withdraw_status = '{$withdraw_data['withdraw_status']}', withdraw_msg = '{$withdraw_data['withdraw_msg']}', withdraw_time = '{$withdraw_data['withdraw_time']}', update_time = $now WHERE id = {$id} AND withdraw_status NOT IN (1,2)");
                $withdraw_rs = $GLOBALS['db']->affected_rows();
                if ($withdraw_rs < 1) {
                    throw new \Exception('提现状态更新失败', 2);
                }
                // 放款提现发送告警
                if ($userCarryData['deal_id'] != 0)
                {
                    $failAt = date('Y-m-d H:i:s');
                    $oldDealName = getOldDealNameWithPrefix($userCarryData['deal_id'], $projectInfo['id']);
                    $failMessage = "提现编号：{$userCarryData['id']} 放款标题：{$oldDealName} 放款金额：{$userCarryData['money']} 失败时间：{$failAt}";
                    Alarm::push('deal_withdraw_fail', '放款提现失败', $failMessage);
                }

                // 借款人的提现失败不解冻结
                if (!empty($userCarryData['deal_id'])) {
                    return true;
                }

                // 处理提现失败
                $user = UserModel::instance()->find($userCarryData['user_id']);
                $user_id = $user->id;
                if (empty($user_id)) {
                    throw new \Exception('查询提现用户信息失败');
                }
                $bizToken = ['orderId' => $userCarryData['id']];
                $_changeResult = $user->changeMoney(-($userCarryData['money']+$userCarryData['fee']), '提现失败', '银行受理失败，如有疑问请拨打客服热线 95782。', 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                if (!$_changeResult) {
                    throw new \Exception('更新用户余额失败');
                }

                // 提现失败发通知
                $content = "您于".to_date($userCarryData['create_time'], 'Y年m月d日 H:i:s') . '提交的' . format_price($userCarryData['money']) . '提现银行受理失败。如有疑问请拨打客服热线 95782。';
                $group_arr = array(0,$user_id);
                $group_arr[] =  7;
                sort($group_arr);

                $msg_data['content'] = $content;
                $msg_data['to_user_id'] = $user_id;
                $msg_data['create_time'] = get_gmtime();
                $msg_data['type'] = 0;
                $msg_data['group_key'] = implode('_', $group_arr);
                $msg_data['is_notice'] = 7;

                $msgBoxService = new MsgBoxService();
                $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'], "", $msg_data['content']);
//                $result = $GLOBALS['db']->autoExecute(DB_PREFIX . 'msg_box', $msg_data);
//                if (!$result) {
//                    throw new \Exception("提现申请{$id}消息插入失败");
//                }

                //短信通知
                if(app_conf("SMS_ON") == 1) {
                    // SMSSend  提现处理请求同步返回的提现失败短信通知
                    if ($user['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                    {
                        $_mobile = 'enterprise';
                        $accountTitle = get_company_shortname($user['user_id']); // by fanjingwen
                    } else {
                        $_mobile = $user['mobile'];
                        $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                    }

                    $params = array(
                        'account_title' => $accountTitle,
                        'money' => format_price($userCarryData['money']),
                    );
                    \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_SMS_ACCOUNT_CASHOUT_FAIL_NEW', $params, $user['user_id']);
                }

                //生产用户访问日志
                $device = UserAccessLogService::getPaymentDevice($userCarryData['platform']);
                $extraInfo = [
                    'orderId' => $userCarryData['id'],
                    'userId' => intval($userCarryData['user_id']),
                    'withdrawAmount' => (int) bcmul($userCarryData['money'], 100),
                ];
                UserAccessLogService::produceLog($userCarryData['user_id'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网信提现%s元失败', (float)$userCarryData['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_FAIL);

            }
            // 提现处理中，回写提现状态
            else if ($response['status'] == 'T19') {
                $withdraw_data = array(
                    'withdraw_status' =>  UserCarryModel::WITHDRAW_STATUS_PROCESS,
                    'withdraw_msg' => addslashes(preg_replace(array('/\w+?/', '/，/', '/:/'), array('', '', ''), $response['respMsg'])),
                    'withdraw_time' => get_gmtime(),
                    'id' => $id,
                );
                $withdraw_rs = $GLOBALS['db']->query("UPDATE " . DB_PREFIX . "user_carry SET withdraw_status = '{$withdraw_data['withdraw_status']}', withdraw_msg = '{$withdraw_data['withdraw_msg']}', withdraw_time = '{$withdraw_data['withdraw_time']}' WHERE id = {$id} AND withdraw_status = 0");
                $withdraw_rs = $GLOBALS['db']->affected_rows();
                if ($withdraw_rs < 1) {
                    throw new \Exception('提现状态更新失败', 1);
                }
            }
            else if ($response['status'] == '00') {
                // 等回调处理成功，同步不处理成功
            }
        }
        else {
            modify_account(array('money' => -($userCarryData['money']+$userCarryData['fee']), 'lock_money' => -($userCarryData['money']+$userCarryData['fee'])), $userCarryData['user_id'], '提现成功', true);
            $content = '您于' . to_date($userCarryData['create_time'], 'Y年m月d日 H:i:s') . '提交的'
                        . format_price($userCarryData['money']) . '提现申请汇款成功，请查看您的资金记录。';
            $group_arr = array(0, $user_id);
            $group_arr[] =  6;
            sort($group_arr);

            $msg_data['content'] = $content;
            $msg_data['to_user_id'] = $user_id;
            $msg_data['create_time'] = get_gmtime();
            $msg_data['type'] = 0;
            $msg_data['group_key'] = implode('_', $group_arr);
            $msg_data['is_notice'] = 6;

            $msgBoxService = new MsgBoxService();
            $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'], "", $msg_data['content']);
//            $result = $GLOBALS['db']->autoExecute(DB_PREFIX . 'msg_box',$msg_data);
//            if (!$result) {
//                throw new \Exception("提现申请{$id}消息插入失败");
//            }
        }
        return true;
    }

    /**
     * 提现加急处理
     */
    public function accelerate($withdrawId) {
        $GLOBALS['db']->startTrans();
        try {
            // 如果已经发送给支付，则禁止提现加急处理 Add By guofeng At 20160630 16:50
            $userCarryInfo = UserCarryModel::instance()->findViaSlave($withdrawId, 'withdraw_status');
            if (!empty($userCarryInfo['withdraw_status']) && $userCarryInfo['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_PROCESS)
            {
                throw new \Exception('支付处理中，提现加急失败');
            }

            $_toUpdate['status'] = 1;
            $GLOBALS['db']->autoExecute(DB_PREFIX.'user_carry', $_toUpdate, 'UPDATE', " id = '{$withdrawId}' AND withdraw_status = 0");
            $affRows = $GLOBALS['db']->affected_rows();
            if ($affRows == 1) {
                save_log('加急 编号为'.$withdrawId.'的提现申请'.L('UPDATE_SUCCESS'), 1);
            }
            else {
                throw new \Exception('数据库更新失败');
            }
            $GLOBALS['db']->commit();
            return array('ret' => true, 'message' => '更新成功');
        }
        catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            save_log(' 加急 编号为'.$withdrawId.'的提现申请'.L('UPDATE_FAILED'), 0);
            $errorMsg = $e->getMessage();
        }
        return array('ret' => false, 'message' => $errorMsg);
    }

    public function addWithdrawLimitRecord($record) {
        return $this->addWithdrawLimit($record['userId'], $record['username'], $record['amount'], $record['limit_type'], $record['memo'], $record['platform'], $record['account_type'], $record['remain_money']);
    }

    /**
     * 保存提现申请限制记录
     */
    public function addWithdrawLimit($uid, $uname, $amount, $type, $memo = '', $platform = '', $account_type = '', $remain_amount = '') {
        //读取操作人员名称
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $_toInsert = array(
            'user_id' => $uid,
            'user_name' => $uname,
            'amount' => $amount,
            'memo' => $memo,
            'create_time' => get_gmtime(),
            'state' => self::WITHDRAW_LIMIT_INIT,
            'adm_name' => $adm_name,
            'adm_id' => $adm_session['adm_id'],
            'type' => $type,
            'platform' => $platform,
            'account_type' => $account_type,
            'remain_money' => $remain_amount,
        );
        try {
            $GLOBALS['db']->autoExecute(DB_PREFIX.'withdraw_limit', $_toInsert, 'INSERT');
        }
        catch(\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate') !== false) {
                $msg = '该用户提交的记录已经存在,请直接到审核列表审核';
                if ($_toInsert['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $_toInsert['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
                {
                    $msg = '提交失败,用户仍有可提现额度,不能重复申请,请到“可提现额度列表”页中查看';
                }
                throw new \Exception($msg);
            }
        }
        $affRows = $GLOBALS['db']->affected_rows();
        if ($affRows == 1) {
            return true;
        }
        return false;
    }

    public function editLimit($id, $amount, $type) {
        //读取操作人员名称
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $_toUpdate= array(
            'modify_amount' => $amount,
            'state' => 0,
            'adm_name' => $adm_name,
            'adm_id' => $adm_session['adm_id'],
            'type' => $type,
        );
        try {
            $GLOBALS['db']->autoExecute(DB_PREFIX.'withdraw_limit', $_toUpdate, 'UPDATE', " id = '{$id} '");
        }
        catch(\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate') !== false) {
                throw new \Exception('该用户提交的记录已经存在，请直接到审核列表审核');
            }
        }
        $affRows = $GLOBALS['db']->affected_rows();
        if ($affRows == 1) {
            return true;
        }
        return false;
    }

    /**
     * 根据id读取提现限制记录
     */
    public function findLimitById($id, $adm_id = 0) {
        $sql = "SELECT * FROM firstp2p_withdraw_limit WHERE id ='{$id}'";
        if (!empty($adm_id)) {
            $sql .= " AND adm_id != '{$adm_id}'";
        }
        $data = $GLOBALS['db']->getRow($sql);
        return $data;
    }

    /**
     * 根据id读取提现限制记录
     */
    public function findLimitByUserId($user_id, $adm_id = 0) {
        $sql = "SELECT * FROM firstp2p_withdraw_limit WHERE user_id ='{$user_id}'";
        if (!empty($adm_id)) {
            $sql .= " AND adm_id != '{$adm_id}'";
        }
        $data = $GLOBALS['db']->getRow($sql);
        return $data;
    }


    /**
     * 审核用提现限制
     * @param $id integer 记录id
     * @param $status integer 审核状态
     * @param $adm_name string 管理员名称
     * @param $adm_id integer 管理员id
     * @return boolean
     */
    public function doAudit($id, $status, $adm_name, $adm_id, $newAmount = 0) {
        $_toUpdate = array(
            'state' => $status,
            'audit_adm_name' => $adm_name,
            'audit_adm_id' => $adm_id,
            'audit_time' => get_gmtime(),
            'update_time' => get_gmtime(),
        );
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        try {
            $db->startTrans();
            if ($status == self::WITHDRAW_LIMIT_PASSED) {
                if (bccomp($newAmount, '0.00', 2) > 0) {
                    $_toUpdate['amount'] = $newAmount;
                    $_toUpdate['modify_amount'] = '0.00';
                }
                $withdrawLimitRecord = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_withdraw_limit WHERE id = '{$id}'");
                $userInfo = \libs\db\Db::getInstance('firstp2p','master')->getRow("SELECT * FROM firstp2p_user WHERE id = '{$withdrawLimitRecord['user_id']}'");
            }
            if ($status == self::WITHDRAW_LIMIT_REFUSED) {
                if (bccomp($newAmount, '0.00', 2) > 0) {
                    //还原状态到可用
                    $_toUpdate['state'] = self::WITHDRAW_LIMIT_PASSED;
                    $_toUpdate['modify_amount'] = 0.00;
                } else {
                    $sql = "DELETE FROM firstp2p_withdraw_limit WHERE id = '{$id}'";
                    $GLOBALS['db']->query($sql);
                    $affRows = $GLOBALS['db']->affected_rows();
                    $this->deleteWithdrawLimitRecordByWlid($id);
                    $db->commit();
                    return $affRows == 1;
                }
            }
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit', $_toUpdate, 'UPDATE', " id = '{$id}'");
            $affRows = $GLOBALS['db']->affected_rows();
            //投资、提现限制审核通过之后，记录申请记录
            if($status == self::WITHDRAW_LIMIT_PASSED && $affRows == 1)
            {
                $this->saveWithdrawLimitRecord($id,$status, $adm_name, $adm_id);
            }
            if($affRows != 1)
            {
                throw new \Exception('审核失败');
            }
            $db->commit();
            return true;
        } catch(\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 提交限制提现取消申请
     * @param $id integer
     * @param $cancel_state integer
     * @param $adm_name 操作人员用户名
     * @param $adm_id 操作人员id
     */
    public function doCancelAudit($id, $status, $adm_name, $adm_id) {
        $_toUpdate = array(
            'audit_adm_name' => $adm_name,
            'audit_adm_id' => $adm_id,
            'audit_time' => get_gmtime(),
            'update_time' => get_gmtime(),
        );
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $withdrawLimitRecord = WithdrawLimitModel::instance()->find($id);
        $userInfo = \libs\db\Db::getInstance('firstp2p','master')->getRow("SELECT * FROM firstp2p_user WHERE id = '{$withdrawLimitRecord['user_id']}'");
        try {
            $db->startTrans();
            if ($status == self::WITHDRAW_LIMIT_CANCEL_PASSED) {
                $sql = "DELETE FROM firstp2p_withdraw_limit WHERE id = '{$id}'";
                $GLOBALS['db']->query($sql);
                $affRows = $GLOBALS['db']->affected_rows();
                if ($withdrawLimitRecord['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $withdrawLimitRecord['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
                {
                    $insertCancelRecord = [
                        'wl_id'         => $id,
                        'audit_time'    => get_gmtime(),
                        'status'        => self::WITHDRAW_LIMIT_STATUS_CANCEL,
                        'memo'          => $withdrawLimitRecord['memo'],
                        'update_time'   => get_gmtime(),
                    ];

                    $db->autoExecute('firstp2p_withdraw_limit_record', $insertCancelRecord, 'UADTE', "wl_id = $id");
                    $db->commit();
                    return true;
                } else {
                    // 保持黑名单删除记录的逻辑
                    $this->deleteWithdrawLimitRecordByWlid($id);
                    $db->commit();
                    return true;
                }

            }
            else if ($status == self::WITHDRAW_LIMIT_CANCEL_REFUSED) {
                // reset cancel_state
                $_toUpdate['cancel_state'] = self::WITHDRAW_LIMIT_CANCEL_NONE;
                $_toUpdate['adm_id'] = $adm_id;
                $_toUpdate['adm_name'] = $adm_name;
                $_toUpdate['update_time'] = get_gmtime();

            }
            else if ($status == self::WITHDRAW_LIMIT_CANCEL_INIT) {
                $_toUpdate['cancel_state'] = self::WITHDRAW_LIMIT_CANCEL_INIT;
                $_toUpdate['adm_id'] = $adm_id;
                $_toUpdate['adm_name'] = $adm_name;
                $_toUpdate['update_time'] = get_gmtime();
                unset($_toUpdate['audit_adm_id']);
                unset($_toUpdate['audit_time']);
                unset($_toUpdate['audit_adm_name']);
            }
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit', $_toUpdate, 'UPDATE', " id = '{$id}'");
            $affRows = $GLOBALS['db']->affected_rows();
            if ($affRows != 1)
            {
                throw new \Exception('审核失败');
            }
            $db->commit();
            return true;
        } catch(\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 用户是否在受限列表
     * @param $user_id integer
     * @return boolean
     */
    public function isInLimit($user_id) {
        $sql = "SELECT COUNT(*) FROM firstp2p_withdraw_limit WHERE user_id = '{$user_id}' AND state = ".self::WITHDRAW_LIMIT_PASSED;
        $userCount = $GLOBALS['db']->get_slave()->getOne($sql);
        return $userCount == 1;
    }


    /**
     * 判断用户是否存在限制提现
     * @param integer $userId 用户id
     * @param string $withdrawAmount 出金金额,单位元
     * @param boolean $isSupervision  是否是存管账户
     * @param boolean $useBonus 是否使用红包
     *
     * @return boolean
     */
    public function canWithdrawAmount($userId, $withdrawAmount, $isSupervision = false, $useBonus = true)
    {
        //绕过限制提现检查
        if (self::$checkWithdrawLimit === false) {
            return true;
        }

        $accountList = (new AccountService())->getAccountList($userId);
        if (empty($accountList)) {
            return true;
        }

        // 需要转换用户输入金额单位
        $withdrawAmount = $withdrawAmount * 100;
        $platform = $accountType = '';

        if (!$isSupervision && isset($accountList[UserAccountEnum::PLATFORM_WANGXIN]))
        {
            $platform = UserAccountEnum::PLATFORM_WANGXIN;
            $accountType = $accountList[UserAccountEnum::PLATFORM_WANGXIN][0]['accountType'];
            return (new AccountLimitService())->canWithdrawAmount($userId, $withdrawAmount, $platform, $accountType, $useBonus);
        } else if ($isSupervision && isset($accountList[UserAccountEnum::PLATFORM_SUPERVISION])) {
            $platform = UserAccountEnum::PLATFORM_SUPERVISION;
            $accountType = $accountList[UserAccountEnum::PLATFORM_WANGXIN][0]['accountType'];
            return ApiService::rpc('ncfph', 'account/canWithdrawAmount', ['userId' => $userId, 'amount' =>$withdrawAmount, 'platform' => $platform, 'accountType' => $accountType, 'useBonus' => $useBonus]);
        }
    }


    /**
     * 用户是否在受限列表
     * @param $user_id integer
     * @return boolean
     */
    public function _canWithdrawAmount($user_id, $withdrawAmount, $caculateSupervisionMoney = false) {
        $sql = "SELECT amount FROM firstp2p_withdraw_limit WHERE user_id = '{$user_id}' AND state = ".self::WITHDRAW_LIMIT_PASSED;
        $amount = $GLOBALS['db']->get_slave()->getOne($sql);

        if(empty($amount) || bccomp($amount, '0.00', 2) <= 0)//限制金额小于0或者没有限制记录，直接返回true
        {
            return true;
        }
        // 计算用户总资产
        $sql = "SELECT money FROM firstp2p_user WHERE id = '{$user_id}'";
        $userTotalMoney = $GLOBALS['db']->getOne($sql);
        if ($caculateSupervisionMoney) {
            $svService = new SupervisionAccountService();
            if ($svService->isSupervisionUser($user_id)) {
                $svUserInfo = $svService->balanceSearch($user_id);
                if ($svUserInfo['status'] == SupervisionBaseService::RESPONSE_SUCCESS) {
                    $userTotalMoney = bcadd($userTotalMoney, bcdiv($svUserInfo['data']['availableBalance'], 100, 2), 2);
                }
            }
        }
        // 如果用户存在限制提现金额
        if(bccomp($amount, '0.00', 2) > 0) {
            $remainMoney = bcsub($userTotalMoney, $amount, 2);
            if (bccomp($remainMoney, '0.00', 2) <= 0) {
                return false;
            }
            // 如果用户限制提现金额大于剩余金额，则不通过
            if (bccomp($withdrawAmount, $remainMoney, 2) > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断是否可以进行余额划转指定金额，考虑限制提现
     */
    public function canTransferAmount($userId, $transferAmount) {
        return $this->canWithdrawAmount($userId, $transferAmount);
    }


    /**
     * 获取用户限制提现金额， 单位元
     * @param integer $userId 用户id
     * @return float amount
     */
    public function getLimitAmountByUserId($userId) {
        $sql = "SELECT amount FROM firstp2p_withdraw_limit WHERE user_id = '{$userId}' AND state = ".self::WITHDRAW_LIMIT_PASSED. ' AND platform = '.UserAccountEnum::PLATFORM_WANGXIN;
        $amount = $GLOBALS['db']->get_slave()->getOne($sql);
        return bcadd($amount, '0.00', 2);
    }


    /**
     * 判断用户是否上海银行，其他银行单笔提现不能超过500w，上海银行单笔提现不能超过20w
     * @param integer $userId 用户ID
     * @param string $withdrawAmount 提现金额
     * @return array
     */
    public function canWithdraw($userId, $withdrawAmount)
    {
        $sql = "SELECT bank_id FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'";
        $bankId = $GLOBALS['db']->get_slave()->getOne($sql);
        $ret = array();
        $ret['result'] = true;
        $ret['specialBank'] = false;
        $ret['reason'] = '';
        if (!empty($bankId))
        {
            $sqlShortName = "SELECT short_name FROM firstp2p_bank WHERE id = '{$bankId}'";
            $shortName = $GLOBALS['db']->get_slave()->getOne($sqlShortName);
            if (in_array($shortName, array('BOS')))
            {
                $ret['specialBank'] = true;
            }
        }

        if ($ret['specialBank'])
        {
            if (bccomp($withdrawAmount, '200000.00', 2) > 0)
            {
                $ret['reason'] = '您的银行卡仅支持单笔20万元交易，请分多次提现。';
                $ret['result'] = false;
            }
        }
        else
        {
            if (bccomp($withdrawAmount, '5000000.00', 2) > 0)
            {
                $ret['reason'] = '您的银行卡仅支持单笔500万元交易，请分多次提现。';
                $ret['result'] = false;
            }
        }
        return $ret;
    }

    /**
     * 通过限制投资id删除投资限制记录
     * @param int $wl_id
     * @return boolean
     */
    function deleteWithdrawLimitRecordByWlid($wl_id)
    {
       $sql = "DELETE FROM firstp2p_withdraw_limit_record WHERE wl_id = '".intval($wl_id)."'";
       $GLOBALS['db']->query($sql);
       $affRows = $GLOBALS['db']->affected_rows();
       return $affRows == 1;
    }

    function saveWithdrawLimitRecord($wl_id,$status = self::WITHDRAW_LIMIT_STATUS_T1, $adm_name = '', $adm_id = 0)
    {
        $wl_id = intval($wl_id);
        if(empty($wl_id))
        {
            return false;
        }

        //获取限制用户的记录
        $withdrawLimitInfo = $this->findLimitById($wl_id);
        $_toUpdate= [
            'wl_id'         => $wl_id,
            'user_id'       => $withdrawLimitInfo['user_id'],
            'type'          => $withdrawLimitInfo['type'],
            'status'        => self::WITHDRAW_LIMIT_STATUS_T1,
        ];

        // 区分提现记录类型
        $isWhitelist = 0;
        if ($withdrawLimitInfo['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $withdrawLimitInfo['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
        {
            $isWhitelist = 1;
        }
        //获取限制用户账号可用余额
        $sql = "SELECT money,user_name FROM firstp2p_user WHERE id = '{$_toUpdate['user_id']}'";
        $userInfo = $GLOBALS['db']->getRow($sql);
        $_toUpdate['audit_adm_name']= $withdrawLimitInfo['audit_adm_name'];
        $_toUpdate['is_whitelist']  = $isWhitelist;
        $_toUpdate['adm_id']        = $withdrawLimitInfo['adm_id'];
        $_toUpdate['adm_name']      = $withdrawLimitInfo['adm_name'];
        $_toUpdate['audit_adm_id']  = $withdrawLimitInfo['audit_adm_id'];
        $_toUpdate['status']        = $status;
        $_toUpdate['money']         = $userInfo['money'];
        $_toUpdate['user_name']     = $userInfo['user_name'];
        if($status == self::WITHDRAW_LIMIT_STATUS_T1)//新申请的投资体现限制初始化限制金额，还款中 和还款后限制金额不变
        {
            $_toUpdate['amount'] = $withdrawLimitInfo['amount'];
        }
        $sql = "SELECT COUNT(*) FROM firstp2p_withdraw_limit_record WHERE wl_id = ".$wl_id;
        $userCount = $GLOBALS['db']->get_slave()->getOne($sql);
        if(!empty($userCount))
        {
            $_toUpdate['update_time'] = get_gmtime();
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit_record', $_toUpdate, 'UPDATE', " wl_id = ".$wl_id);
            $affRows = $GLOBALS['db']->affected_rows();
        } else {
            $_toUpdate['memo']          = $withdrawLimitInfo['memo'];
            $_toUpdate['remain_money']  = $withdrawLimitInfo['remain_money'];
            $_toUpdate['create_time']   = $withdrawLimitInfo['create_time'];
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit_record', $_toUpdate, 'INSERT');
            $affRows = $GLOBALS['db']->affected_rows();
        }
        return $affRows == 1;
    }

    /**
     * 还款之后更新限制记录
     * @param int $user_id
     * @param float $repay_money
     * @return boolean
     */
    function updateWithdrawLimitAfterRepalyMoney($user_id,$repay_money)
    {
        //获取限制用户的记录
        $withdrawLimitInfo = $this->findLimitByUserId($user_id);
        if(empty($withdrawLimitInfo))
        {
            return true;
        }
        $remainMoney = bcsub($withdrawLimitInfo['amount'] , $repay_money , 2);
        if (bccomp($remainMoney, '0.00', 2) <= 0){
           // 如果是白名单的限制提现规则，则不删除记录
           if ($withdrawLimitInfo['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $withdrawLimitInfo['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
           {
              return true;
           }
           $status = self::WITHDRAW_LIMIT_STATUS_T3;
           $this->saveWithdrawLimitRecord($withdrawLimitInfo['id'],$status);
           $sql = "DELETE FROM firstp2p_withdraw_limit WHERE id = ".$withdrawLimitInfo['id'];
           $GLOBALS['db']->query($sql);
           $affRows = $GLOBALS['db']->affected_rows();
        }
        else{
            $status = self::WITHDRAW_LIMIT_STATUS_T2;
            $_toUpdate = array('amount'=>$remainMoney);
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit', $_toUpdate, 'UPDATE', " id = ".$withdrawLimitInfo['id']);
            $affRows = $GLOBALS['db']->affected_rows();
            $this->saveWithdrawLimitRecord($withdrawLimitInfo['id'],$status);
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"status:".$status,"user_id:".$user_id,"repay_money:".$repay_money,"result:". ($affRows == 1?"成功":"失败"))));
        return $affRows == 1;
    }

    /**
     * 获取某用户的提现记录（近7天的）
     */
    public function getWithdrawListByUserId($userId,$offset,$count){
        $list = UserCarryModel::instance()->getListByUid($userId,$offset,$count);
        if(is_array($list) && !empty($list)){
            foreach($list as &$one){
                if($one['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_SUCCESS){
                    $one['status_str'] = '提现成功';
                }elseif($one['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_FAILED){
                    $one['status_str'] = '提现失败';
                }else{
                    $one['status_str'] = '提现处理中';
                }
                $one['create_time'] = $one['create_time']+28800;
                unset($one['id']);
                unset($one['withdraw_status']);
            }
            return $list;
        }
        return array();
    }

    /**
     * 根据标的ID，获取先锋支付的bizType
     * @param int $dealId 标的id
     * @param array $dealInfo 标的详情
     * @return array
     */
    public static function getBizTypeByDealId($dealId = 0, $dealInfo = array(), &$withdrawalType = 1)
    {
        $bizTypeMap = PaymentApi::instance()->getGateway()->getConfig('towithdrawal', 'bizTypeMap');
        isset($bizTypeMap['default']) || $bizTypeMap['default'] = 'q007tx';
        $result = array('bizType'=>$bizTypeMap['default'], 'isInBizMap'=>false);
        // 普通放款
        if (!is_numeric($dealId) || $dealId <= 0)
        {
            return $result;
        }
        // 标的ID大于0时，则默认选择[放款提现]
        $result['bizType'] = isset($bizTypeMap['FKTX']) ? $bizTypeMap['FKTX'] : $bizTypeMap['default'];
        // 按消费类型放款
        if (!isset($bizTypeMap['dealLoanType']) || empty($bizTypeMap['dealLoanType']))
        {
            return $result;
        }
        // 根据标的ID，获取标的类型
        $dealInfo || $dealInfo = DealModel::instance()->findByViaSlave('id = :id', 'type_id', array(':id' => $dealId));
        if (!isset($dealInfo['type_id']) || $dealInfo['type_id'] <= 0)
        {
            return $result;
        }
        // 根据标的类型，获取标的tag
        $dealLoanTypeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($dealInfo['type_id']);
        if (empty($dealLoanTypeTag))
        {
            return $result;
        }
        // 检查标的tag，是否在配置的bizMap里面
        if (isset($bizTypeMap['dealLoanType'][$dealLoanTypeTag]) && !empty($bizTypeMap['dealLoanType'][$dealLoanTypeTag]))
        {
            $result['bizType'] = $bizTypeMap['dealLoanType'][$dealLoanTypeTag];
            $result['isInBizMap'] = true;
            // 在该配置里的类型，必须是对私的提现
            //$withdrawalType = 1;
        }
        return $result;
    }

    /**
     * 根据标的id自动批准提现申请
     * @param int $deal_id
     * @return bool
     */
    public function doPassByDealId ($deal_id) {
        $uc = UserCarryModel::instance()->getByDealId($deal_id);
        if (!$uc) {
            return false;
        }

        $uc->status = 3;
        $uc->update_time = $uc->update_time_step1 = $uc->update_time_step2 = get_gmtime();
        return $uc->save();
    }

    /**
     * 根据标的id获取提醒状态
     * @param int $deal_id
     * @return object
     */
    public function getByDealIdStatus($deal_id){
        $deal_id = intval($deal_id);
        if (empty($deal_id)){
            return false;
        }

        $user_carray_model = new UserCarryModel();

        return $user_carray_model->getByDealIdStatus($deal_id);
    }

    /**
     * 根据标的ID获取最新的借款人提现申请记录
     */
    public function getLatestByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if (empty($deal_id)) {
            return false;
        }
        $condition = "deal_id = '{$deal_id}' order by id desc limit 1";
        $item = UserCarryModel::instance()->findBy($condition);
        return $item;
    }

    /**
     * 判断是否可以重新发起提现
     */
    public function canRedoWithdraw($user_carry) {
        if (!empty($user_carry['deal_id']) && $user_carry['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_FAILED) {
            $latest = $this->getLatestByDealId($user_carry['deal_id']);
            if (!empty($latest) && $latest['id'] == $user_carry['id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断标的放款状态 和 提现状态 均为成功
     */
    public function isDealSuccessfulForLoansAndWithdraw($deal_id)
    {
        $user_carry_obj = $this->getByDealIdStatus($deal_id);
        if (empty($user_carry_obj)) {
            return false;
        } else {
            // 放款状态：会计通过，提现状态：成功
            return (UserCarryModel::STATUS_ACCOUNTANT_PASS == $user_carry_obj->status && UserCarryModel::WITHDRAW_STATUS_SUCCESS == $user_carry_obj->withdraw_status);
        }
    }

    /**
     * 获取用户最后一次提现
     * @param integer $userId
     * @return mixed
     */
    public function getLastWithdrawLog($userId) {
        if (empty($userId)) {
            return false;
        }
        $lastWithdrawLog = UserCarryModel::instance()->db->getRow("SELECT * FROM firstp2p_user_carry WHERE user_id = '{$userId}' AND withdraw_status = ".UserCarryModel::WITHDRAW_STATUS_SUCCESS.' ORDER BY id DESC LIMIT 1');
        //$lastP2pWithdarwLog = SupervisionWithdrawModel::instance()->db->getRow("SELECT * FROM firstp2p_supervision_withdraw WHERE user_id = '{$userId}' AND withdraw_status= ".SupervisionWithdrawModel::WITHDRAW_STATUS_SUCCESS.' ORDER BY id DESC limit 1');
        $lastP2pWithdrawLog = PhSupervisionService::GetUserLastWithdraw($userId);
        $withdrawLog = [];
        if(!empty($lastWithdrawLog) && !empty($lastP2pWithdrawLog) && ($lastWithdrawLog['pay_time'] + 28800 >= $lastP2pWithdrawLog['update_time'])) {
            // 订单号
            $withdrawLog['order_id'] = $lastWithdrawLog['id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastWithdrawLog['withdraw_time'] + 28800;
            // 支付时间格式化
            $withdrawLog['withdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = bcmul($lastWithdrawLog['money'], 100);
        }
        else if(!empty($lastWithdrawLog) && !empty($lastP2pWithdrawLog) && ($lastWithdrawLog['pay_time'] + 28800 < $lastP2pWithdrawLog['update_time'])) {
            // 订单号
            $withdrawLog['order_id'] = $lastP2pWithdrawLog['out_order_id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastP2pWithdrawLog['update_time'];
            // 支付时间格式化
            $withdrawLog['withdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = $lastP2pWithdrawLog['amount'];
        } else if (!empty($lastWithdrawLog)) {
            // 订单号
            $withdrawLog['order_id'] = $lastWithdrawLog['id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastWithdrawLog['withdraw_time'] + 28800;
            // 支付时间格式化
            $withdrawLog['wthdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = bcmul($lastWithdrawLog['money'], 100);
        } else if (!empty($lastP2pWithdrawLog)) {
            // 订单号
            $withdrawLog['order_id'] = $lastP2pWithdrawLog['out_order_id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastP2pWithdrawLog['update_time'];
            // 支付时间格式化
            $withdrawLog['withdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = $lastP2pWithdrawLog['amount'];
        }
        return $withdrawLog;
    }

    /**
     * 提现申请
     * @param int $userId 用户Id
     * @param float $money 提现金额
     * @return boolean
     */
    public function withdrawApply($userId, $money, $platform = PaymentNoticeModel:: PLATFORM_ADMIN) {
        if (bccomp($money, 0, 2) <= 0) {
            Logger::error(implode('|', [__CLASS__, __FUNCTION__, $userId, '提现金额错误']));
            return false;
        }
        $fee = 0; //手续费
        $db = \libs\db\Db::getInstance('firstp2p');
        try {
            $db->startTrans();
            //检查余额是否足够
            $withdrawAmount = bcadd($money, $fee, 2);
            $user = UserModel::instance()->find($userId);
            if (bccomp($user['money'], $withdrawAmount, 2) < 0) {
                throw new \Exception($GLOBALS['lang']['CARRY_MONEY_NOT_ENOUGHT']);
            }

            // 是否启用资金托管
            if (app_conf('PAYMENT_ENABLE') && app_conf('ENABLE_PAY_AMOUNT_CHECK'))
            {
                $params = array(
                    'source' => 1,
                    'userId' => $userId,
                );
                $result = \libs\utils\PaymentApi::instance()->request('searchuserbalance', $params);
                if (bccomp($result['availableBalance']['amount'], $withdrawAmount, 2) < 0 ) {
                    throw new \Exception('财务正在对账中，请两小时后再试', -1);
                }
            }

            //获取用户银行卡信息
            $bankcard_info = UserBankcardModel::instance()->getByUserId($userId);
            // 如果用户没有银行卡信息或者信息没有确认保存过
            if (!$bankcard_info || $bankcard_info['status'] != 1) {
                throw new \Exception('用户未绑卡');
            }

            // 提现时，检查用户是否符合风控延迟提现规则-JIRA4937
            $isWithdrawDelayUser = (new \core\service\PaymentService())->isWithdrawLimitedByUserId($userId, $withdrawAmount);

            // 取消财务审批，用户发起的提现均自动处理, 见UserCarryService::getWarningStat
            $data = [];
            $data['user_id'] = $userId;
            $data['money'] = $money;
            $data['fee'] = $fee;
            $data['money_limit'] = floatval(app_conf('PAYMENT_AUTO_AUDIT'));
            $data['bank_id'] = $bankcard_info['bank_id'];
            $data['real_name'] = $bankcard_info['card_name'];
            $data['region_lv1'] = $bankcard_info['region_lv1'];
            $data['region_lv2'] = $bankcard_info['region_lv2'];
            $data['region_lv3'] = $bankcard_info['region_lv3'];
            $data['region_lv4'] = $bankcard_info['region_lv4'];
            $data['bankcard'] = $bankcard_info['bankcard'];
            $data['bankzone'] = $bankcard_info['bankzone'];
            $data['create_time'] = get_gmtime();
            $data['platform'] = $platform;
            $data['warning_stat'] = $isWithdrawDelayUser ? UserCarryModel::WITHDRAW_IS_DELAY : UserCarryModel::WITHDRAW_IS_NORMAL; // 是否被风控延迟提现
            //自动审批
            $data['status'] = 3;
            $data['desc'] = '自动处理提现<p>运营：自动审批</p><p>财务：自动审批</p>';
            $_ts = $db->insert('firstp2p_user_carry', $data);
            if (!$_ts) {
                throw new \Exception('保存用户提现记录失败');
            }

            //提现申请
            $bizToken = [
                'orderId' => $db->insert_id(),
            ];
            $res = $user->changeMoney($withdrawAmount, '提现申请', '网信账户提现申请', 0, 0, 1, 0, $bizToken);
            if (!$res) {
                throw new \Exception('修改用户账户金额失败');
            }
            $db->commit();
            PaymentApi::log(sprintf('WithdrawApply Success. userId: %d, money: %f', $userId, $money));
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            \libs\utils\Monitor::add('WITHDRAW_APPLY_FAILED');
            PaymentApi::log(sprintf('WithdrawApply Fail. userId: %d, money: %f, err: %s', $userId, $money, $e->getMessage()));
            Logger::error(implode('|', [__CLASS__, __FUNCTION__, $userId, $e->getMessage()]));
            return false;
        }
    }

    /**
     * 提现余额
     */
    public function withdrawBalance($userId) {
        $user = UserModel::instance()->find($userId);
        if (empty($user)) {
            Logger::error('withdrawBalance. 用户不存在, userId: ' . $userId);
            return false;
        }
        if (bccomp($user['money'], '0', 2) <= 0) {
            Logger::info('withdrawBalance. 用户无需提现, 余额为0, userId: ' . $userId);
            return true;
        }
        PaymentApi::log('withdrawBalance. userId: ' . $userId);
        return $this->withdrawApply($userId, $user['money']);
    }

    /**
     * 自动提现重试
     */
    public function autoWithdrawRetry($userId) {
        $autoWithdrawUserIds = app_conf('AUTO_WITHDRAW_USER_IDS');
        $autoWithdrawUserIdArr = explode(',', $autoWithdrawUserIds);
        $userCarryModel = UserCarryModel::instance();
        //是否是自动提现用户
        if (!in_array($userId, $autoWithdrawUserIdArr)) {
            return true;
        }

        PaymentApi::log('autoWithdrawRetry. userId: ' . $userId);
        $startTime = mktime(-8, 0, 0, date('m'), date('d'), date('Y'));
        $endTime = mktime(-8, 0, -1, date('m'), date('d') + 1, date('Y'));
        $list = $userCarryModel->getListByRange($userId, UserCarryModel::WITHDRAW_STATUS_FAILED, $startTime, $endTime);
        //当日超过3次失败，发邮件告警
        if (count($list) >= 3) {
            $user = UserModel::instance()->find($userId);
            $subject = '网信账户自动提现失败';
            $body = '';
            $body .= "<h3>$subject</h3>";
            $body .= '<ul style="color:#1f497d;">';
            $body .= '<li>用户ID：' . $userId . '，姓名：' . $user['real_name'] . '，该用户网信账户进行自动提现失败，请及时查看</li>';
            $body .= '</ul>';

            $mailAddress = app_conf('AUTO_WITHDRAW_EMAILS');
            $msgcenter = new \Msgcenter();
            $msgcenter->setMsg($mailAddress, 0, $body, false, $subject);
            $ret = $msgcenter->save();
            return true;
        } else {
            //重新发起提现
            return $this->withdrawBalance($userId);
        }
    }
}
// END class UserCarryService
