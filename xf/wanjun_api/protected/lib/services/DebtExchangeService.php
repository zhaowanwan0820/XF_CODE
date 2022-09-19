<?php

class DebtExchangeService extends ItzInstanceService
{
    public function makeDebtBuyer($exchangeInfo)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];

        $debt_buyers = ItzArray::array_column($exchangeInfo, 'buyer_uid');
        if (empty($debt_buyers)) {
            $returnData['code'] = 2030;
            $returnData['info'] = '债权认购方为空';
            return $returnData;
        }
        $sql = 'SELECT * FROM ag_wx_assignee_info WHERE user_id in ('.implode(',', $debt_buyers).') ';
        $assignee = Yii::app()->db->createCommand($sql)->queryAll();
        $assignee = ItzArray::array_column($assignee, null, 'user_id');

        foreach ($exchangeInfo as &$item) {
            if ($item['buyer_uid'] > 0) {
                $_assigneeInfo = $assignee[$item['buyer_uid']];
                if ($_assigneeInfo && 2 == $_assigneeInfo['status'] && $_assigneeInfo['buyer_type']==2) {
                    continue;
                }
            }
            $item['buyer_uid'] = 0;
        }
        $returnData['data'] = $exchangeInfo;
        return  $returnData;
    }
}
