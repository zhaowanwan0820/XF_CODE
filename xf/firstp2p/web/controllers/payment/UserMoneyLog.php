<?php
/**
 * 供先锋支付查询用户资金记录接口
 * @author guofeng3@ucfgroup.com
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\UserLogService;

class UserMoneyLog extends BaseAction
{
    //MD5 'xfjr'
    CONST PARTNER_ID = '6e199e0893798f90db7c016ad96462f7';


    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("UserMoneyLog Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("UserMoneyLog redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['partnerId'] = isset($_POST['partnerId']) ? trim($_POST['partnerId']) : '';
        $params['uid'] = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $params['count'] = isset($_POST['count']) ? max(1, intval($_POST['count'])) : 20;
        $params['logType'] = isset($_POST['logType']) ? trim($_POST['logType']) : 'ALL';

        //必填参数验证
        foreach ($params as $key => $value)
        {
            if ($value === '' || $value === 0)
            {
                echo PaymentApi::instance()->getGateway()->response(array(
                    'respCode' => '01',
                    'respMsg' => "Param $key is invalid",
                ));
                PaymentApi::log("UserMoneyLog ParamCheck. param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'UserMoneyLog', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        $params['start'] = isset($_POST['startDate']) ? to_timespan(trim($_POST['startDate'])) : '';
        $params['end'] = isset($_POST['endDate']) ? to_timespan(trim($_POST['endDate'])) : '';
        // 隐藏20160520之前的数据
        if (!empty($params['start'])) {
            $compareDate = to_timespan('2016-05-20');
            if ($params['start'] < $compareDate) {
                $params['start'] = $compareDate;
            }
        }
        if (isset(UserLogService::$logInfoMap[$params['logType']])) {
            $params['logType'] = UserLogService::$logInfoMap[$params['logType']];
        }
        //PartnerId校验
        if ($params['partnerId'] !== self::PARTNER_ID)
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => 'PartnerId is invalid',
            ));
            PaymentApi::log("UserMoneyLog PartnerId error. partnerId:{$params['partnerId']}", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'UserMoneyLog', "PartnerId error. partnerId:{$params['partnerId']}, params:".json_encode($params));
            return;
        }

        //签名验证
        $signature = isset($_POST['sign']) ? trim($_POST['sign']) : 0;
        unset($_POST['sign']);
        $signatureLocal = PaymentApi::instance()->getGateway()->getSignature($_POST);
        if ($signature !== $signatureLocal)
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => 'SIGNATURE_ERROR',
            ));
            PaymentApi::log("UserMoneyLog Signature failed. get:$signature, local:$signatureLocal", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'UserMoneyLog', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        $params['offset'] = isset($_POST['offset']) ? max(0, intval($_POST['offset'])) : 0;
        //逻辑处理
        $userMoneyLog = $this->rpc->local('UserLogService\get_user_log', array(array($params['offset'], $params['count']), $params['uid'], 'money', true, $params['logType'], $params['start'], $params['end'], true, true));
        $userMoneyLogList = isset($userMoneyLog['list']) ? $userMoneyLog['list'] : array();
        if (!empty($userMoneyLogList))
        {
            $resultList = [];
            foreach ($userMoneyLogList as $key => $item)
            {
                // 把日志时间转成当前时间
                $row['log_time'] = $item['log_time'] + (intval(app_conf('TIME_ZONE')) * 3600);
                // sv 日志生成
                $row['sv_log_info'] = $this->_replaceLogInfo($item['log_info']);
                $row['sv_log_time'] = to_date($item['log_time']);
                $row['sv_note'] = msubstr(htmlspecialchars($item['note']), 0, 28);
                $row['sv_money'] = '0.00';
                if ($item['showmoney'] == 0) {
                    $row['sv_money'] = '0.00';
                }
                else if ($item['label'] == UserLogService::LOG_INFO_SHOU) {
                    $row['sv_money'] = '+' .format_price($item['showmoney'], 0, 0);
                } else if ($item['label'] == UserLogService::LOG_INFO_ZHI) {
                    $row['sv_money'] = format_price($item['showmoney'], 0, 0);
                }
                $row['showmoney'] = $row['sv_money'];
                $row['sv_remaining_money'] = $item['remaining_total_money'];
                $resultList[] = $row;
            }
        }
        $result = array(
            'respCode' => '00',
            'respMsg' => '',
            'count' => isset($userMoneyLog['count']) ? $userMoneyLog['count'] : 0,
            'list' => $resultList,
        );

        //返回
        echo PaymentApi::instance()->getGateway()->response($result);
        return;
    }

    /**
     * 替换loginfo 规则
     * @param string $logInfo 当前文案结果
     * @param boolean $cond 替换规则判断结果 比如说log_time > xxxxx
     * @return string
     */
    private function _replaceLogInfo($logInfo, $cond = false) {
        // 替换后的文案 => 替换前的文案
        //  例如:充值还款 => 代充值还款
        $rules = [
        ];
        if ($cond === true) {
            $replace = array_search($logInfo, $rules);
            if(!empty($replace)) {
                return $replace;
            }
        }
        return $logInfo;
    }
}
