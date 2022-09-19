<?php
namespace api\controllers\user;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\controllers\AppBaseAction;
use core\service\QrCodeService;

/**
 * 用户扫码确认登录接口
 *
 * @uses AppBaseAction
 * @package
 */
class QrConfirm extends AppBaseAction {

    /**
     * 强制验签
     */
    protected $must_verify_sign = true;

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'qrtoken' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'qr_ref' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        // 获取登录用户信息
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }
        // 扫码来源标识
        $qrRef = addslashes($this->form->data['qr_ref']);
        if (empty($qrRef) || empty(QrCodeService::$qrRefStatusMap[$qrRef])) {
            $this->setErr('ERR_PARAMS_ERROR');
        }

        $qrToken = addslashes($this->form->data['qrtoken']);
        $qrInfo = QrCodeService::getQrInfo($qrToken);

        // 扫非网信二维码 或者过期
        if (empty($qrInfo)) {
            $this->setErr('ERR_MANUAL_REASON', '二维码无效，请重新扫码');
        }

        if ($loginUser['id'] != intval($qrInfo['userId'])) {
            $this->setErr('ERR_TOKEN_ERROR');
        }

        $result = array(
            'status' => 0,
            'msg' => '用户登录失败',
        );
        try {
            $loginSuc = QrCodeService::qrTokenAutoLogin($qrToken, $loginUser['user_name'], $loginUser['user_pwd'], $qrInfo);
            if ($loginSuc) {
                $result['status'] = 1;
                $result['msg'] = '登录成功';

                // 设置二维码扫码入口缓存信息
                $qrRefRet = QrCodeService::setQrRefInfo($loginUser['id'], ['qrRef'=>$qrRef, 'qrToken'=>$qrToken]);

                // 记录日志
                PaymentApi::log(sprintf('%s，userId：%d, qrToken：%s，qrInfo：%s，qrRefRet：%s，确认登录成功', __METHOD__, $loginUser['id'], $qrToken, json_encode($qrInfo, JSON_UNESCAPED_UNICODE), (int)$qrRefRet));
            }
        } catch (\Exception $e) {
            $result['status'] = 0;
            $msg = $e->getMessage();
            if ($msg) {
                $result['msg'] = $msg;
            }
            // 记录日志
            PaymentApi::log(sprintf('%s，userId：%d, qrToken：%s，qrInfo：%s，确认登录失败，errMsg：%s', __METHOD__, $loginUser['id'], $qrToken, json_encode($qrInfo, JSON_UNESCAPED_UNICODE), $result['msg']));

            // 抛出异常消息
            $this->setErr('ERR_MANUAL_REASON', $result['msg']);
        }

        $this->json_data = $result;
    }
}
