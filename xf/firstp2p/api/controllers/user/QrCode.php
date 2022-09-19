<?php
namespace api\controllers\user;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\controllers\AppBaseAction;
use core\service\QrCodeService;

/**
 * 用户扫一扫二维码登录接口
 *
 * @uses AppBaseAction
 * @package
 */
class QrCode extends AppBaseAction {

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
        // 多人扫同一码
        if (isset($qrInfo['userId']) && $qrInfo['userId'] != $loginUser['id']) {
            $this->setErr('ERR_MANUAL_REASON', '二维码无效，请重新扫码');
        }

        $result = array();
        // 1、把qrtoken作为key，检查是否已经绑定了用户id，是否绑定的该用户id
        if (isset($qrInfo['status']) && $qrInfo['status'] === QrCodeService::QRCODE_STATUS_VALID && !isset($qrInfo['userId'])) {
            // 2、把二维码状态设置为“扫码认证中”
            // 远程修改登录状态失败
            $longConnectionScanKey = sprintf('%s_%s', QrCodeService::LONGCONNECTION_SCAN, $qrToken);
            if (!QrCodeService::remoteStatusUpdate($longConnectionScanKey, QrCodeService::QRCODE_STATUS_ING)) {
                $this->setErr('ERR_MANUAL_REASON', '扫码失败，请重试');
            }

            // 3、把qrtoken作为key，把用户id作为value，存入redis，有效期60秒
            $qrInfo['userId'] = $loginUser['id'];
            $qrInfo['status'] = QrCodeService::QRCODE_STATUS_ING;
            // 本地修复登录状态失败
            if (!QrCodeService::setQrInfo($qrToken, $qrInfo, QrCodeService::QRCODE_LOGIN_KEY_EXPIRE_TIME)) {
                $this->setErr('ERR_MANUAL_REASON', '扫码失败，请重试');
            }

            // 设置二维码扫码入口缓存信息
            if (!QrCodeService::setQrRefInfo($loginUser['id'], ['qrRef'=>$qrRef, 'qrToken'=>$qrToken])) {
                $this->setErr('ERR_MANUAL_REASON', '扫码失败，请重试');
            }

            $result['status'] = $qrInfo['status'];
            $result['msg'] = QrCodeService::$qrCodeStatusMap[$result['status']];
            // 记录日志
            PaymentApi::log(sprintf('%s，userId：%d, qrToken：%s，result：%s，扫码成功待确认', __METHOD__, $loginUser['id'], $qrToken, json_encode($result, JSON_UNESCAPED_UNICODE)));
        } else {
            $this->setErr('ERR_MANUAL_REASON', '扫码失败，请重试');
        }

        $this->json_data = $result;
    }
}