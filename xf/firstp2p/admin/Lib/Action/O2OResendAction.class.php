<?php

/**
 * O2OResendAction class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class O2OResendAction extends CommonAction {

    public function __construct() {
        \libs\utils\PhalconRPCInject::init();
        parent::__construct();
    }

    public function index() {
        $this->assign('actionEnum', CouponGroupEnum::$TRIGGER_MODE_FOR_ADMIN);
        $this->display();
    }

    public function resend() {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $couponGroupId = intval($_POST['couponGroupId']);
        if (!$couponGroupId) {
            $this->error('券组ID不能为空');
        }
        $fixIds = trim($_POST['fixIds']);
        if (!$fixIds) {
            $this->error('用户id不能为空');
        }
        $triggerMode = intval($_POST['triggerMode']);
        if (!$triggerMode) {
            $this->error('触发事件不能为空');
        }

        $fixIds = explode(',', $fixIds);

        $o2oService = new O2OService();
        foreach ($fixIds as $fixId) {
            if (empty($fixId)) {
                continue;
            }
            $fixId = trim($fixId, "\n");
            $fixId = trim($fixId, "\r\n");
            $dealLoadId = 0;
            $userId = $fixId;
            if (strpos($fixId, '-') !== false) {
                $fixArray = explode('-', $fixId);
                $userId = $fixArray[0];
                $dealLoadId = $fixArray[1];
            }

            $res = $o2oService->resend($couponGroupId, $userId, $triggerMode, $dealLoadId);
            if (!empty($res)) {
                \libs\utils\PaymentApi::log('O2OGiftResendSuccess.' . $fixId);
                $success[] = $fixId;
            } else {
                \libs\utils\PaymentApi::log('O2OGiftResendFailed.' . $fixId);
                $failed[] = $fixId;
            }
        }

        /**
         * 发送监听邮件
         */
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        send_my_mail(C('O2O_ALARM_MAIL'), $adm_session['adm_name'] . '操作o2o补发优惠券', var_export($_POST, true));


        $this->assign('success', $success);
        $this->assign('failed', $failed);
        $this->display();
    }

}

?>
