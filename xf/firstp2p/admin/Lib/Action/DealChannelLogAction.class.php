<?php
/**
 * 渠道推广投标记录
 * 2013年11月8日16:08:33
 * @author changlu
 *
 */

class DealChannelLogAction extends CommonAction {

    public static $deal_status_list = array(
        '0' => '投标成功',
        '1' => '还款中',
        '2' => '流标',
    );

    public function index() {
        $condition['is_delete'] = 0;
        $this->assign("default_map", $condition);
        parent::index();
    }

    public function insert() {
        $deal_load_id = intval($_POST['deal_load_id']);
        $channel_value = $_POST['channel_value'];
        $pay_factor = $_POST['pay_factor'];
        $add_type = 2; // 只能手工添加

        $form = D(MODULE_NAME);
        // 字段校验
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }

        if (empty($deal_load_id) || empty($channel_value) || !is_numeric($channel_value) || empty($pay_factor) || !is_numeric($pay_factor)) {
            $this->error(L("DEALCHANNEL_CHANNEL_VALUE_ERROR"), 0, L("DEALCHANNEL_CHANNEL_VALUE_ERROR"));
        }
        $channel_value = intval($channel_value);

        $advisor_info = get_user_info($channel_value, true);
        if (empty($advisor_info) || $advisor_info['is_effect'] == 0 || $advisor_info['is_delete'] == 1) {
            $this->error(L("DEALCHANNEL_CHANNEL_VALUE_ERROR"), 0, L("DEALCHANNEL_CHANNEL_VALUE_ERROR"));
        }

        $channel_fee_service = new \core\service\ChannelFeeService();
        $result = $channel_fee_service->insert_deal_channel_log($deal_load_id, $channel_value, $add_type, $pay_factor);

        //开始验证有效性
        if ($result) {
            //成功提示
            save_log($data['id'] . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($data['id'] . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"), 0, $data['id'] . L("INSERT_FAILED"));
        }
    }

    public function update(){
        parent::update();
        $channel_log_id = $_POST['id'];
        $channel_fee = new \core\service\ChannelFeeService();
        $channel_fee->updatePayFee($channel_log_id);
    }

    /**
     * 渠道推广分成结清
     */
    public function pay_channel_fee() {
        $channel_log_id = intval($_REQUEST['channel_log_id']);
        if (empty($channel_log_id)) {
            $this->ajaxReturn($channel_log_id, "参数错误", 0);
        }

        $channelFeeService = new \core\service\ChannelFeeService();
        if ($channelFeeService->pay_channel_fee($channel_log_id)) {
            $this->ajaxReturn($channel_log_id, L('REFERRALS_PAY_SUCCESS'), 1);
        } else {
            $this->ajaxReturn($channel_log_id, L('REFERRALS_PAY_FAILED'), 0);
        }
    }

}