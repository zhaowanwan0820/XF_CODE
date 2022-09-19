<?php
namespace web\controllers\deal;

use libs\web\Form;
use web\controllers\BaseAction;

require_once(dirname(__FILE__) . "/../../../system/utils/phpqrcode.php");

class BonusQrcode extends BaseAction {

    public function init() {
        $this->check_login();
        $this->form = new Form();
        $this->form->rules = array(
                'code' => array('filter' => 'string'),
                'is_n' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {

        $code = $this->form->data['code'];
        $is_n = $this->form->data['is_n'];
        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];

        $data = get_config_db('API_BONUS_SHARE_HOST', $site_id) .'/hongbao/CheckOwner?is_n=' . $is_n . '&sn='.urlencode($code). '&site_id=' . $site_id . '&source=pc';

        $errorCorrectionLevel = 'H'; //L、M、Q、H
        $matrixPointSize = 5;

        \QRcode::png($data,false,$errorCorrectionLevel,$matrixPointSize, 1);
    }
}
