<?php
/**
 * Gongyi.php
 * 公益标列表接口
 * @date 2015-10-13
 * @author longbo<longbo@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;

/**
 * 公益列表接口
 * Class Gongyi
 * @package api\controllers\account
 */
class Gongyi extends AppBaseAction {

    const GY_LT = 7;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $params['offset'] = empty($params['offset']) ? 0 : intval($params['offset']);
        $params['count'] = empty($params['count']) ? 10 : intval($params['count']);
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('DealLoadService\getDealLoadByLoantype', array($user['id'], self::GY_LT, $params['offset'], $params['count'], true));
        $list = $res['list'];
        $now = get_gmtime();
        $result =  $load_list = array();
        $result['sum'] = number_format($res['sum'], 2);
        if (!empty($list)) {
            $columnsStr = 'name, deal_status, deal_type';
            foreach ($list as $k => $v) {
                $deal_info = $this->rpc->local('DealService\getManualColumnsVal', array($v['deal_id'], $columnsStr));
                $load_list[$k]['id'] = $v['id'];
                $load_list[$k]['deal_id'] = $v['deal_id'];
                $load_list[$k]['deal_name'] = $deal_info['name'];
                $load_list[$k]['deal_status'] = $deal_info['deal_status'];
                $load_list[$k]['deal_load_money'] = number_format($v['money'], 2);
                $load_list[$k]['deal_type'] = $deal_info['deal_type'];
                $load_list[$k]['create_time'] = to_date($v['create_time']);
            }
            $result['list'] = $load_list;
        }
        $this->json_data = $result;
    }

}
