<?php

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\dao\UserBankcardModel;
use libs\web\Form;
use core\dao\UserCarryModel;
use libs\utils\Logger;
use core\service\UserCarryService;
use core\dao\PaymentNoticeModel;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\sms\SmsServer;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 * 个人中心提现
 * @author caolong<caolong@ucfgroup.com>
 * @userLock
 */
class Savecarry extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            "amount" => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // 验证表单令牌
       if (!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR']);
        }
        $data['user_id'] = intval($GLOBALS['user_info']['id']);
        $data['money'] = floatval($data['amount']);

       if (bccomp($data['money'], 0, 2) <= 0) {
            return $this->show_error($GLOBALS['lang']['CARRY_MONEY_NOT_TRUE']);
        }
        RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH)->check($GLOBALS['user_info'],Risk::ASYNC,$data);
        $userCarryService = new UserCarryService();
        // 超级账户提现不计算红包余额
        $canWithdraw = $userCarryService->canWithdrawAmount($GLOBALS['user_info']['id'], $data['money'], false, false);
        if (!$canWithdraw) {
            $this->show_error($GLOBALS['lang']['CARRY_LIMIT_ERR']);
            return false;
        }
        $canWithdraw = $userCarryService->canWithdraw($GLOBALS['user_info']['id'], $data['money']);
        if (!$canWithdraw['result']) {
            $this->show_error($canWithdraw['reason']);
            return false;
        }
        // 检查提现金额小数点不能超过2位
        $r = explode('.', $data['money']);
        if (isset($r[1])) {
            if (strlen($r[1]) > 2)
                return $this->show_error($GLOBALS['lang']['CARRY_MONEY_NOT_TRUE']);
        }

        $fee = 0; //不收手续费
        $destination = APP_ROOT_PATH . "log/logger/save_carry_error-" . date('y_m_d') . ".log";
        $content = '';
        $contMoney = '0.00';
        $insertId = 0;

        $GLOBALS['db']->startTrans();
        try {
            $withdrawAmount = $data['money'] + $fee;
            if (bccomp($withdrawAmount, '0.00', 2) <= 0) {
                throw new \Exception('提现失败');
            }
            $sql = "SELECT id, real_name FROM firstp2p_user WHERE id = {$data['user_id']} AND money >= {$withdrawAmount}";
            $record = $GLOBALS['db']->getRow($sql);
            if (empty($record['id'])) {
                throw new \Exception($GLOBALS['lang']['CARRY_MONEY_NOT_ENOUGHT'], -1);
            }

            // 是否启用资金托管
            if (app_conf('PAYMENT_ENABLE') && app_conf('ENABLE_PAY_AMOUNT_CHECK'))
            {
                $params = array(
                    'source' => 1,
                    'userId' => $data['user_id'],
                );
                $result = \libs\utils\PaymentApi::instance()->request('searchuserbalance', $params);
                if (bccomp($result['availableBalance']['amount'], $withdrawAmount, 2) < 0 ) {
                    throw new \Exception('财务正在对账中，请两小时后再试', -1);
                }
            }
            $data['fee'] = $fee;
            //获取用户银行卡信息
            $bankcard_info = UserBankcardModel::instance()->getByUserId($GLOBALS['user_info']['id']);
            // 如果用户没有银行卡信息或者信息没有确认保存过
            if (!$bankcard_info || $bankcard_info['status'] != 1)
                return $this->show_error('请先填写银行卡信息', '', 0, url('shop', 'uc_money#bank'), 0);

            // 提现时，检查用户是否符合风控延迟提现规则-JIRA4937
            $isWithdrawDelayUser = (new \core\service\PaymentService())->isWithdrawLimitedByUserId($data['user_id'], $withdrawAmount);

            // 取消财务审批，用户发起的提现均自动处理, 见UserCarryService::getWarningStat
            //$data['warning_stat'] = 0;
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
            $data['platform'] = PaymentNoticeModel:: PLATFORM_WEB;
            $data['warning_stat'] = $isWithdrawDelayUser ? UserCarryModel::WITHDRAW_IS_DELAY : UserCarryModel::WITHDRAW_IS_NORMAL; // 是否被风控延迟提现
            $user_carry_dao = new UserCarryModel();
            foreach ($data as $key => $val) {
                $user_carry_dao->$key = $val;
            }
            $_ts = $user_carry_dao->save();
            if (!$_ts) {
                throw new \Exception('保存用户提现记录失败');
            }
            $insertId = $user_carry_dao->id;
            //更新会员账户信息
            \FP::import("libs.libs.user");
            //TODO finance 提现申请
            $res = modify_account(array('money' => 0, 'lock_money' => $data['money'] + $fee), $data['user_id'], "提现申请", true, "网信账户提现申请", false);
            if (!$res) {
                throw new \Exception('修改用户账户金额失败');
            }

            // 获取提现时效配置
            $apiConfObj = new \core\service\ApiConfService();
            $withdrawTimeConf = $apiConfObj->getWithdrawTime();

            $content = '您于' . to_date($data['create_time'], 'Y年m月d日 H:i:s') . '提交的' . format_price($data['money']) . '提现申请我们正在处理，如您填写的账户信息正确无误，您的资金将会于' . $withdrawTimeConf . '个工作日内到达您的银行账户.';
            $contMoney = format_price($data['money']);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $code = $e->getCode();
            $msg = '提现申请失败，如有疑问请拨打客服热线 95782。';
            if ($code == -1) {
                $msg = $e->getMessage();
            }
            \libs\utils\Monitor::add('WITHDRAW_APPLY_FAILED');
            Logger::wLog($msg . "\t" . $insertId, Logger::INFO, Logger::FILE, $destination);
            return $this->show_error($msg);
        }

        //业务日志参数
        $this->businessLog['busi_name'] = '提现';
        $this->businessLog['busi_id'] = $insertId;
        $this->businessLog['money'] = $data['money'];

        RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH)->notify();
        $auditData = array();
        $auditData['update_time_step1'] = $auditData['update_time_step2'] = $auditData['update_time'] = get_gmtime();
        try {
            $userCarryData = UserCarryModel::instance()->find($insertId);

            $GLOBALS['db']->startTrans();
            try {
                $auditData['status'] = 3;
                $auditData['desc'] = $info['desc'] . '延时处理提现<p>运营：自动审批</p><p>财务：自动审批</p>';
                // 更新数据，考虑并发，增加乐观锁
                $upResult = $GLOBALS['db']->autoExecute('firstp2p_user_carry', $auditData, 'UPDATE', ' id  = ' . $insertId . ' AND status = 0');
                if ($upResult === false) {
                    throw new \Exception('自动提现更新失败');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $auditData['status'] = 1;
                $auditData['desc'] = '自动提现异常，转财务复核';
                unset($auditData['update_time_step2']);
                $upResult = $GLOBALS['db']->autoExecute('firstp2p_user_carry', $auditData, 'UPDATE', ' id  = ' . $insertId . ' AND status = 0');
                if ($upResult === false) {
                    throw new \Exception('<p>' . $e->getMessage() . '</p><p>自动审批错误后，提现更新失败</p>');
                }
                throw $e;
            }
        } catch (\Exception $e) {
            Logger::wLog($e->getMessage() . "\t" . $insertId, Logger::INFO, Logger::FILE, $destination);
            // send email warning
            \libs\utils\Alarm::push('payment', "paymentAutoAudit", $e->getMessage());
        }
        Logger::wLog("success\t" . $insertId, Logger::INFO, Logger::FILE, $destination);

        //生产访问日志
        $device = DeviceEnum::DEVICE_WEB;
        $extraInfo = [
            'orderId' => $insertId,
            'withdrawAmount' => (int) bcmul($data['money'], 100),
        ];
        UserAccessLogService::produceLog($data['user_id'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网信提现申请%s元', (float)$data['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_INIT);

        // 发送站内信好短信
        try
        {
            // 站内信
            send_user_msg("", $content, 0, $data['user_id'], get_gmtime(), 0, true, 5);
            // 短信通知
            if(app_conf("SMS_WITHDRAW_ON") == 1){
                // SMSSend 用户提现申请
                if ($GLOBALS['user_info']['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($GLOBALS['user_info']['id']); // by fanjingwen
                } else {
                    $_mobile = $GLOBALS['user_info']['mobile'];
                    $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                }
                $params = array(
                    'account_title' => $accountTitle,
                    'time' => to_date($data['create_time'], 'm-d H:i'),
                    'money' => $contMoney,
                );

                SmsServer::instance()->send($_mobile, 'TPL_SMS_ACCOUNT_CASHOUT_NEW', $params, $GLOBALS['user_info']['id']);
            }
        }
        catch (\Exception $e)
        {
            // keep silent
            \libs\utils\PaymentApi::log('Savecarry.php cashOut#'.$insertId.' send sms failed, msg:'.json_encode($this->form->data).' exception msg:'.$e->getMessage());
        }

        return $this->show_success($GLOBALS['lang']['CARRY_SUBMIT_SUCCESS']);
    }
}