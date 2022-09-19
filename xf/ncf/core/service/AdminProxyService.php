<?php

namespace core\service;

use core\service\user\UserService;

class AdminProxyService extends BaseService {

    public static function getCarryStatus($item, $withdrawStatus) {
        return sprintf("%s  %s", $withdrawStatus[$item['withdraw_status']], (in_array($item['withdraw_status'], [1, 2]) ? format_date($item["update_time"]) : ''));
    }


    public static function afterUserCarray($data) {
        $return = ['totalRows' => 0, 'totalPages' => 0, 'nowPage' => 0, 'dataList'  => []];
        if (empty($data['list'])) {
            return $return;
        }

        $return['totalRows']  = intval($data['totalRows']);
        $return['totalPages'] = intval($data['totalPages']);
        $return['nowPage']    = intval($data['nowPage']);

        foreach ($data['list'] as $item) {
            $return['dataList'][] = array(
                'id'           => $item['id'], //编号
                'userId'       => $item['user_id'],  //用户ID
                'userName'     => $data['userNameList'][$item['user_id']]['user_name'], // 用户名称
                'cardName'     => $data['userBankList'][$item['user_id']]['card_name'], //开户名
                'money'        => $item['amount'] / 100, //提现金额
                'carryTime'    => format_date($item["create_time"]), //申请时间
                'handleStatus' => self::getCarryStatus($item, $data['withdraw_status']), //处理状态
            );
        }

        return $return;
    }

}
