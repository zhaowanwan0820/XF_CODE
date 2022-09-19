<?php
/**
 * BindMobile.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\BonusService;
use libs\weixin\Weixin;

class YxHongbaoSend extends YxHongbao{

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {
        if (!$this->referMobile && !$this->form->data['mobile']) {
            $this->tpl->assign('only_new_user', '手机号不能为空');
            $this->template = "web/views/hongbao/yx/qianghongbao.html";
            return false;
        }
        $this->tpl->assign("host", get_config_db('API_BONUS_SHARE_HOST', $site_id));
        $this->tpl->assign("sn", $sn);
        $this->tpl->assign("site_id", $site_id);
        $this->template = "web/views/hongbao/yx/share.html";

    }
}
