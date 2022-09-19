<?php
/**
 * 每天0点向邮件告警地址中配置的邮件地址发送前一天所配置用户ID中包含所有提现的明细，提现明细根据“用户管理-提现申请管理-申请列表”中捞取。
 */
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\UserCarryModel;

\libs\utils\Script::start();

ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

function get_user_bank_info($user_id, $field = '')
{
    $user_bankcard_model = new core\dao\UserBankcardModel();
    $info = $user_bankcard_model->getOneCardByUser($user_id);
    $bankId = intval($info['bank_id']);
    $info['bankName'] = '';
    if ($bankId != 0) {
        $info['bankName'] = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT name FROM firstp2p_bank WHERE id ='{$bankId}'");
    }

    return empty($field) ? $info : (empty($info) ? '' : $info[$field]);
}

function getStatusDesc($status) {
    $statusMap = [
        'CARRY_STATUS_0'=>'运营待处理',
        'CARRY_STATUS_1'=>'财务待处理',
        'CARRY_STATUS_2'=>'运营拒绝',
        'CARRY_STATUS_3'=>'批准',
        'CARRY_STATUS_4'=>'财务拒绝',
    ];
    return $statusMap['CARRY_STATUS_' . $status];
}

/**
 * 获取警告信息
 *
 * @param intger $warningStat
 * @access public
 * @return string
 */
function getWarningInfo($warningStat, $split = "<br>", $moneyLimit) {

    $userCarryService = new \core\service\UserCarryService();
    return $userCarryService->getWarningInfo($warningStat, $split, $moneyLimit);
}


try {
    //自动提现用户
    $userIds = app_conf('AUTO_WITHDRAW_USER_IDS');
    $userIdArr = explode(',', $userIds);
    foreach ($userIdArr as $key => $userId) {
        $userId = (int) $userId;
        if (empty($userId)) {
            unset($userIdArr[$key]);
        }
    }
    if (empty($userIdArr)) {
        throw new \Exception('未配置自动提现用户ID');
    }

    $title = array(
        '编号',
        '借款标题', '放款方式', '放款类型',
        '用户ID', '会员名称', '用户姓名', '提现金额',
        '手续费', '申请时间', '状态', '类型', '备注', '异常款项备注',
        '处理时间', '支付状态', '支付时间',
        '开户名', '银行卡号', '开户行名称','支行名称','提现失败操作'
    );

    $body = '';
$body .= '<style>table { width:100%; margin:5px 0; background:#666; font-size:13px; border-spacing:1px; }
th { padding:5px; background:#698CC3; color:#fff; }
td { background:#F8F8F8; padding:5px 6px 3px 6px; }</style>';
    $body .= '<h3>截止'.date('Y-m-d').' 00:00:00 自动提现用户明细</h3>';
    $body .='<table>';

    $body .= '<tr>';
    $body .= '<th>';
    $body .= implode('</th><th>', $title);
    $body .= '</th>';
    $body .= '</tr>';

    //前一天
    $startTime = mktime(-8, 0, 0, date('m'), date('d') - 1, date('Y'));
    $endTime = mktime(-8, 0, -1, date('m'), date('d'), date('Y'));
    $condition = sprintf('user_id in (%s) AND create_time >= %d AND create_time <= %d', implode(',', $userIdArr), $startTime, $endTime);

    $sql = "SELECT * FROM " .DB_PREFIX. "user_carry WHERE $condition ORDER BY id DESC";
    $db = \libs\db\Db::getInstance('firstp2p', 'slave');
    $res = $db->query($sql);

    $withdraw_status = UserCarryModel::$withdrawDesc;
    $types = array('1' => '用户提现', '2' => '咨询服务费', '3' => '担保费', '4' => '咨询费');

    $userModel = UserModel::instance();
    while($v = $db->fetchRow($res)) {
        $user_bank = get_user_bank_info($v['user_id']);

        $dealName = '';
        $loanMoneyTypeName = '';
        $loanTypeName = '';

        $cardName = $user_bank['card_name'];
        $cardNo = $user_bank['bankcard'];
        $bankName = $user_bank['bankName'];
        $bankZoneName = $user_bank['bankzone'];
        // 提现失败显示内容
        $withdrawFailedMsg = '';

        $arr = array();
        $arr[] = $v['id'];

        //借款标题、放款方式、放款类型
        $arr[] = $dealName;
        $arr[] = $loanMoneyTypeName;
        $arr[] = $loanTypeName;

        $arr[] = $v['user_id'];
        $user = $userModel->find($v['user_id'], 'user_name');
        $arr[] = !empty($user['user_name']) ? $user['user_name'] : '';
        $arr[] = $v['real_name'];
        $arr[] = $v['money'];
        $arr[] = $v['fee'];
        $arr[] = to_date($v['create_time']);
        $arr[] = getStatusDesc($v['status']);
        $arr[] = $types[$v['type']];
        $arr[] = str_replace(array("</p>","<p>"),array('',''),$v['desc']);
        $arr[] = getWarningInfo($v['warning_stat'], "，", $v['money_limit']);
        // 处理时间更换
        $dealTime = "";
        if ($v['update_time_step1']) {
            $dealTime .= "运营：" . to_date($v['update_time_step1']) . "，";
        }

        if ($v['update_time_step2']) {
            $dealTime .= "财务：" . to_date($v['update_time_step2']) . "，";
        }
        $dealTime .= '系统自动处理：' . to_date($v['update_time']);
        $arr[] = $dealTime;
        $withdarwStatus = $withdraw_status[$v['withdraw_status']];
        $arr[] = $withdarwStatus;
        $arr[] = to_date($v['withdraw_time']);
        $arr[] = $cardName;
        $arr[] = $cardNo."\t ";
        $arr[] = $bankName."\t ";
        $arr[] = $bankZoneName."\t ";
        $arr[] = $withdrawFailedMsg;
        $arr[] = "\t";

        $body .= '<tr>';
        $body .= '<td>';
        $body .= implode('</td><td>', $arr);
        $body .= '</td>';
        $body .= '</tr>';
    }

    $body .= '</table>';

    $subject = '自动提现用户明细';
    $mailAddress = app_conf('AUTO_WITHDRAW_EMAILS');
    $msgcenter = new \Msgcenter();
    $msgcenter->setMsg($mailAddress, 0, $body, false, $subject);
    $ret = $msgcenter->save();

    Logger::info('auto_withdraw_emails. success');

} catch (\Exception $e) {
    Logger::info('auto_withdraw_emails. error: ' . $e->getMessage());
}

\libs\utils\Script::end();
