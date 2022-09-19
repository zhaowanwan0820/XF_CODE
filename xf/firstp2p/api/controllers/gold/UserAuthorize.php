<?php

/**
 * Detail.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use core\service\DealLoanTypeService;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package api\controllers\deals
 */
class UserAuthorize extends GoldBaseAction {
    /**
     * 输出页面
     */
    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

}
