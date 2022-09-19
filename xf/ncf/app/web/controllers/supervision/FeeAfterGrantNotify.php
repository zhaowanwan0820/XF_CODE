<?php
/**
 * firstp2p网站放款后收费通知接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;
use web\controllers\supervision\NotifyAction;
use core\service\deal\FeeAfterGrantService;

class FeeAfterGrantNotify extends NotifyAction
{
    //使用订单锁
    protected $orderLock = false;

    public function process($requestData)
    {
        //逻辑处理
        $fagService = new FeeAfterGrantService();
        $result = $fagService->chargeFeeAfterGrantNotify($requestData);
        if ($result == false)
        {
            return ['respCode' => '01', 'status' => 'F', 'respMsg' => '处理放款后收费通知失败,请重试' ];
        }
        return ['respCode' => '00', 'status' => 'S', 'respMsg' => '成功' ];
    }

}
