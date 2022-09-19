<?php
/**
 * 支付相关测试接口
 * (免验证，只在测试环境有效)
 */
class PaymentToolAction extends BaseAction
{
    
    public function __construct()
    {
        parent::__construct();

        //屏蔽线上环境
        if (app_conf('ENV_FLAG') === 'online')
        {
            exit('Denied for online.');
        }
    }

    /**
     * 查询用户余额接口
     */
    public function getUserBanlance()
    {
        $userId = isset($_REQUEST['userid']) ? intval($_REQUEST['userid']) : 0;

        if (empty($userId))
        {
            exit('Need userid.');
        }

        $params = array(
            'source' => 1,
            'userId' => $userId,
        );
        $result = \libs\utils\PaymentApi::instance()->request('searchuserbalance', $params);

        echo json_encode($result);
        exit();
    }

}
