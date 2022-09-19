<?php
/**
 * 输出url地址的二维码图片
 * Index.php
 */

namespace web\controllers\qrcode;

use web\controllers\BaseAction;
use libs\web\Form;

require_once(dirname(__FILE__) . "/../../../system/utils/phpqrcode.php");

class Index extends BaseAction {

    private $url = '';
    private $errorCorrectionLevel = 'H';
    private $matrixPointSize = 4;
    private $margin = 0;
    private $filename = false;

    public function init() {
        $this->url = urldecode($_GET['url']);
    }

    public function invoke() {
        \QRcode::png($this->url, $this->filename, $this->errorCorrectionLevel, $this->matrixPointSize);
    }

}
