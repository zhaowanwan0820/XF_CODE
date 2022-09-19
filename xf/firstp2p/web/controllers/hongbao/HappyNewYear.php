<?php
/**
 * 拜年红包，根据登陆情况不同输出相应的地址
 * HappyNewYear.php
 */

namespace web\controllers\hongbao;

use web\controllers\BaseAction;
use libs\web\Form;

use core\service\BonusService;
ini_set('display_errors', 1);
error_reporting(E_ERROR);
class HappyNewYear extends BaseAction {

    public $backurl  = '';
    private $sit_id = 1;

    public function init() {
        $this->site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        $this->backurl = $_GET['backurl'];
    }

    public function invoke() {
        $codeurl = '';
        if ($GLOBALS['user_info']) {
            $mobile = \libs\utils\Aes::encode($GLOBALS['user_info']['mobile'], base64_decode(BonusService::HONGBAO_AES_KEY));
            $url = sprintf('%s/hongbao/YxHongbaoBind?referUsn=%s&sn=%s&site_id=%s&source=pc', app_conf('API_BONUS_SHARE_HOST'), urlencode($mobile), urlencode(app_conf('BONUS_HAPPY_NEW_YEAR')), $this->site_id);
            $codeurl = sprintf('%s%s/qrcode?url=%s', PRE_HTTP, APP_HOST, urlencode($url));
        }
        $loginurl = sprintf('%s%s/user/login?backurl=%s', PRE_HTTP, APP_HOST, urlencode($this->backurl));
        echo '_hongbaoHappeyNYCallback(', json_encode(array('codeurl' => $codeurl, 'loginurl' => $loginurl)), ');';
    }
}
