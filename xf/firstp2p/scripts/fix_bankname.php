<?php
/**
 * 银行名称修复工具
 * 每天晚上11点开始执行,修复用户已绑卡但是银行名称为空的问题
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__DEBUG', false);

use core\dao\UserModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;

$bankcardModel = new UserBankcardModel();
$list = $bankcardModel->getEmptyBankNameList();

if (count($list)) {
    $params =  array(
        'source' => 'p2p_fix_script',
    );
    foreach ($list as $item) {
        $params['accountNo'] = $item['bankcard'];
        $i = 0;
        do {
            $result = PaymentApi::instance()->request('searchcardbin', $params);
            // 修复银行卡信息
            if ($result['status'] == '00') {
                // 匹配P2P银行id
                $bank_id = $GLOBALS['db']->get_slave()->getOne("SELECT id FROM firstp2p_bank WHERE short_name = '{$result['bankCode']}'");
                if (empty($bank_id)) {
                    PaymentApi::log(" p2p_fix_script  fix failed, userbank_id:'{$item['id']}', unsupported bank code :".json_encode($result));
                    break;
                }
                // 更新用户银行卡信息
                $GLOBALS['db']->autoExecute('firstp2p_user_bankcard', array('bank_id' => $bank_id), 'UPDATE' , " id = '{$item['id']}'");
                $affRows = $GLOBALS['db']->affected_rows();
                if ($affRows <= 0) {
                    PaymentApi::log(" p2p_fix_script update failed, userbank_id:'{$item['id']}', unsupported bank code :".json_encode($result));
                    continue;
                }
                echo '更新成功';
                break;
            }
        } while ($i ++  < 3);
    }

}
echo "修复完成";
