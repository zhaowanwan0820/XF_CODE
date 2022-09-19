<?php
/**
 * 小额代发
 */
class CashpresentAction extends CommonAction
{

    public function index()
    {
        $this->display();
    }

    public function pay()
    {
        exit('5元现金功能已经停用，如有疑问，请联系市场部同学');
        $userId = isset($_REQUEST['userId']) ? intval($_REQUEST['userId']) : 0;
        $user = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM firstp2p_user WHERE id='{$userId}'");
        if (empty($user)) {
            $this->error('用户不存在');
        }

        $bankcard = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM firstp2p_user_bankcard WHERE user_id='{$userId}'");
        if (empty($bankcard)) {
            $this->error('用户未绑卡');
        }

        $bankInfo = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM firstp2p_bank WHERE id='{$bankcard['bank_id']}'");
        if (empty($bankInfo)) {
            $this->error('用户银行卡短码查询失败');
        }

        $session = es_session::get(md5(conf('AUTH_KEY')));
        \libs\utils\PaymentApi::log("CashpresentFromAdmin. userId:{$user['id']}, adm_name:{$session['adm_name']}");

        try {
            $cashpresentService = new \core\service\CashpresentService();
            // 补单机制，强制删除已经存在的订单
            $cashpresentService->pay($user['id'], $user['real_name'], $user['mobile'], 500, $bankcard['bankcard'], $bankInfo['short_name'], true);
        } catch (\Exception $e) {
            $this->error('支付失败:'.$e->getMessage());
        }

        $this->success('支付成功');
    }

}
