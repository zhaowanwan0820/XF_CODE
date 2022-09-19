<?php
namespace web\controllers\user;

use NCFGroup\Common\Library\Status\Status;
use web\controllers\BaseAction;
use core\service\QrCodeService;

require_once(dirname(__FILE__) . "/../../../system/utils/phpqrcode.php");

/**
 * 获取登录二维码接口
 *
 * @uses BaseAction
 * @package
 */
class QrShow extends BaseAction {
    private $errorCorrectionLevel, $matrixPointSize;

    public function init() {
        $this->errorCorrectionLevel = 'H'; // L、M、Q、H
        $this->matrixPointSize = 5;
    }

    public function invoke() {
        $data = [];
        // 初始化二维码
        $qrToken = QrCodeService::initQrToken();

        // 缓存输出
        ob_start();
        \QRcode::png($qrToken, false, $this->errorCorrectionLevel, $this->matrixPointSize, 1);
        $qrPng = ob_get_clean();
        // 二维码图片
        $data['qrPng'] = base64_encode($qrPng);
        // 二维码token
        $data['qrToken'] = $qrToken;
        // 长连接域名
        $data['qrUrl'] = Status::getUrl();
        ajax_return($data);
    }
}