<?php
/**
 * 二维码扫描跳转页
 * @author <wenyanlei@ucfgroup.com>
 **/

namespace mobileapp\controllers\download;

use mobileapp\controllers\BaseAction;

class Index extends BaseAction {

    public function invoke() {
        //hotfix
        header("Location: http://app.firstp2p.com");
        return ;
    }
}
