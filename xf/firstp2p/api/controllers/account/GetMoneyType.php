<?php
/**
 * User: yangshuo5@ucfgroup.com
 * Date: 2017/12/11
 * Time: 下午9:24
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use core\service\UserLogService;

/**
 * 资金记录列表获取分类接口
 * Class GetType
 * @package api\controllers\account
 */
class GetMoneyType extends AppBaseAction
{
    public function init() {
        parent::init();
    }

    public function invoke() {
        $menu = array();
        $highest = UserLogService::$money_log_types_highest;
        $quick_entry = explode(",",str_replace(array('，', ' ', '|'),',',app_conf('MONEY_LOG_QUICK_TYPE')));

        foreach ($highest as $k => $v) {
            $type['logType'] = $k;
            $type['childMenu'] = $v;
            array_push($menu,$type);
        }

        $data['menu'] = $menu;
        $data['quickEntry'] = $quick_entry;
        $this->json_data = $data;
    }
}
