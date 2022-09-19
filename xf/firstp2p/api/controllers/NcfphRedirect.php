<?php

namespace api\controllers;

use api\controllers\AppBaseAction;

class NcfphRedirect extends AppBaseAction {
    public function ncfphRedirect($phAction, $data) {
        if (empty($phAction)) {
            return false;
        }

        if(substr($phAction,0,1)!="/") {
            $phAction = '/'. $phAction;
        }

        $query = '';
        // 增加vconsole调试支持
        if (\libs\utils\ABControl::getInstance()->hit('vconsole')) {
            $data['_debug'] = 1;
        }

        if (!empty($data)) {
            $query = http_build_query($data);
        }

        $phWapUrl = app_conf('NCFPH_WAP_HOST'). $phAction. '?'. $query;
        return app_redirect($phWapUrl);
    }
}

