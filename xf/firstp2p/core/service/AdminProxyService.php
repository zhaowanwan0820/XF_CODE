<?php

namespace core\service;

class AdminProxyService extends BaseService {

    public static function getUcfpayStatus($status, $memo = '') {
        return ($status == 2 && empty($memo)) ? '提现还款' : \get_withdraw_status($status);
    }

    public static function showWithdrawTime($status, $time) {
        return (($status == 1 || $status == 2) && $time) ? to_date($time) : '';
    }

    public static function getCarryHandleTime($item) {
        $return = '';

        if (intval($item['update_time_step1'])) {
            $return .= sprintf("<p>运营:%s</p>", to_date($item["update_time_step1"]));
        }

        if (intval($item['update_time_step2'])) {
            $return .= sprintf("<p>财务:%s</p>", to_date($item["update_time_step2"]));
        }

        return $return;
    }

    public static function getCarryStatus($item) {
        return sprintf("%s  %s", self::getUcfpayStatus($item["withdraw_status"], $item['withdraw_msg']), self::showWithdrawTime($item["withdraw_status"], $item["withdraw_time"]));
    }


    public static function afterUserCarray($data) {
        $return = ['totalRows' => 0, 'totalPages' => 0, 'nowPage' => 0, 'dataList'  => []];
        if (empty($data['list'])) {
            return $return;
        }

        $return['totalRows']  = intval($data['totalRows']);
        $return['totalPages'] = intval($data['totalPages']);
        $return['nowPage']    = intval($data['nowPage']);

       $userInfo = (new UserService())->getUserinfoByUsername($data['user_name']);
       if(empty($userInfo)){
           return $return;
       }

        $userInfo = $userInfo->getRow();
        $userMoney = $userInfo['is_delete'] ? '' : $userInfo['money'];
        $cardName  = \get_user_bank_info($userInfo['id'], 'card_name');
        foreach ($data['list'] as $item) {
            $return['dataList'][] = array(
                'id'           => $item['id'], //编号
                'userId'       => $item['user_id'],  //用户ID
                'userName'     => $userInfo['user_name'], //用户名称
                'cardName'     => $cardName, //开户名
                'money'        => $item['money'], //提现金额
                'userMoney'    => $userMoney, //会员余额
                'carryTime'    => to_date($item["create_time"]), //申请时间
                'handleDesc'   => (string) $item['desc'], //备注
                'handleTime'   => self::getCarryHandleTime($item), //处理时间
                'handleStatus' => self::getCarryStatus($item), //处理状态
                'carryDelay'   => $item['warning_stat'] == 1 ? '是' : '否',
            );
        }

        return $return;
    }

    public static function afterChangeBankcard($data) {
        $return = ['totalPage' => 0, 'nowPage' => 0, 'dataList'  => []];
        if (empty($data['list'])) {
            return $return;
        }

        $return['totalPage'] = $data['totalPages'];
        $return['nowPage']   = $data['nowPage'];

        foreach ($data['list'] as $item) {
            if ($item['fastpay_cert_status'] == 1) {
                $fastpayCertStatus = '验证通过';
            } else {
                $fastpayCertStatus = '验证失败';
            }

            switch ($item['status']) {
                case '1':
                    $status = '未审核';
                    break;

                case '2':
                    $status = '拒绝';
                    break;

                case '3':
                    $status = '批准';
                    break;

                default:
                    $status = '';
                    break;
            }

            $return['dataList'][] = array(
                'id'                    => $item['id'],                     // 申请id，用于查询详细信息
                'realName'              => $item['real_name'],              // 真实姓名
                'mobile'                => $item['mobile'],                 // 手机号
                'createTime'            => to_date($item['create_time']),   // 申请时间
                'failReason'            => $item['fail_reason'],            // 审核失败原因
                'fastpayCertStatus'     => $fastpayCertStatus,              // 是否通过4要素验证
                'auditTime'             => to_date($item['audit_time']),    // 处理时间
                'status'                => $status,                         // 状态(1:未审核 2:拒绝 3:批准)
            );
        }

        return $return;
    }

    public static function afterChangeBankcardDetail($data) {
        if (empty($data['data'])) {
            return [];
        }
        $data = $data['data'];
        return array(
            'realName'  => $data['name'],
            'idno'      => $data['user']['idno'],
            'bankname'  => $data['bank']['name'],
            'bankAdd'   => $data['city'],
            'bankzone'  => $data['data']['bankzone'],
            'bankNo'    => $data['data']['bankcard'],
            'photo'     => "<img src = '{$data['stream']}' width='400'>",
            'copPhoto'  => '',
        );
    }
}
