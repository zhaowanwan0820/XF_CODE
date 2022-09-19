<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Ptp\models\Firstp2pUserCarry;

class UserCarryDAO
{
    /**
     * 根据标 id，判断标的是否已放款提现
     * 判断标准：放款状态：成功，支付状态：成功
     *
     * @params  int $deal_id
     * @return boolen
     */
    public static function isWithdrawal($deal_id)
    {
        $params = array(
            'conditions' => sprintf('dealId = %d AND status = 3 AND withdrawStatus = 1', $deal_id), // 条件为：放款状态：成功，支付状态：成功
            'columns' => 'id',
        );
        $res = Firstp2pUserCarry::findFirst($params);

        return empty($res) ? false : true;
    }
}
