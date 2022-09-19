<?php
/**
 * Moneylog.php
 *
 * @date 2014-03-26
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\UserLogService;

/**
 * 资金记录列表接口
 *
 * Class MoneyLog
 * @package api\controllers\account
 */
class MoneyLog extends AppBaseAction {

    private $label_text = array(1 => '冻', 2 => '支', 3 => '解', 4 => '收', 5 => '划');

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "offset" => array("filter" => "int", "message" => "offset is error", 'option' => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", 'option' => array('optional' => true)),
            "logType" => array("filter" => "string", "message" => "logInfo is error", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $params['offset'] = intval($params['offset']);
        $params['count'] = empty($params['count']) ? 20 : intval($params['count']);
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $logInfo = !empty($params['logType']) ? trim($params['logType']) : '';
        $res = $this->rpc->local(
                    'UserLogService\get_user_log',
                    array(
                        array($params['offset'], $params['count']),
                        $user['id'],
                        'money',
                        false,
                        $logInfo
                        )
                    );
        $list = $res['list'];
        $result = array();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $result[$k]['id'] = $v['id'];
                $result[$k]['time'] = to_date($v['log_time']);
                $result[$k]['type'] = $v['log_info'];
                $result[$k]['remark'] = $v['note'];
                $result[$k]['remain'] = $v['remaining_total_money'];
                $result[$k]['label'] = $this->label_text[$v['label']];
                if ($v['label'] == UserLogService::LOG_INFO_SHOU) {
                    $result[$k]['money'] = '+' . format_price($v['showmoney'], false);
                } else {
                    $result[$k]['money'] = format_price($v['showmoney'], false);
                }

                /* JIRA 5623 */
                $jumpType = ['交易冻结', '交易放款', '还本', '支付收益', '支付利息', '提前还款本金', '提前还款收益', '提前还款利息', '提前还款补偿金'];
                $result[$k]['dealId'] = in_array($v['log_info'], $jumpType) ? (preg_match('/编号\s*(\d+)/', $v['note'], $a) ? $a[1] : null) : null;
            }
        }
        if ($this->app_version > 321) {
            $data = array();
            $data['list'] = $result;
            $data['logType'] = UserLogService::$money_log_types;
            $this->json_data = $data;
        } else {
            $this->json_data = $result;
        }
    }

}
