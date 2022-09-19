<?php
/**
 * 贴息导入并自动通过
 **/
require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ERROR);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '2G');
//读取csv数据
$file = $argv[1];
$userModel = new \core\dao\UserModel();
$dealLoadModel = new \core\dao\DealLoadModel();
// 扣款账户
$outUser = $userModel->find(4159, 'id,user_name', true);
$userCache = array();
$rowNo = 0;
$headArray = array('序号', '投资记录ID', '转账金额', '备注');
//导入
if (($handle = fopen("/tmp/$file.csv", "r")) !== FALSE or die('文件不存在' . PHP_EOL)) {
    while (($row = fgetcsv($handle)) !== FALSE) {
        ++$rowNo;
        $line = implode(',', $row);
        if ($rowNo == 1) { //第一行标题，检查标题行
            if (count($row) != count($headArray)) {
                die("标题不正确！". PHP_EOL);
            }
            for ($i = 0; $i < count($headArray); $i++) {
                $row[$i] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$i])));
                if ($row[$i] != $headArray[$i]) {
                    die("列标题不正确！应为'{$headArray[$i]}'" . PHP_EOL);
                }
            }
            file_put_contents('/tmp/'.$file . '_result.csv', $line .iconv('UTF-8', 'GB2312', ",处理结果,异常信息") . PHP_EOL, FILE_APPEND);
        } else { //数据
            //\libs\utils\Runtime::start('process');
            try {
                //\libs\utils\Runtime::start('dataCheck');
                $item = array();
                $item['type'] = 1; //会员转账
                $item['batch_id'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[0])));
                $item['deal_load_id'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[1])));
                $item['money'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[2])));
                $item['info'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[3])));
                //检查数据
                if (empty($item['batch_id']) || empty($item['deal_load_id'])) {
                    throw new \Exception("'{$headArray[0]}'或'{$headArray[1]}'不正确！");
                }
                if (!is_numeric($item['money']) || $item['money'] <= 0 || $item['money'] > 99999999.99) {
                    throw new \Exception("'{$headArray[2]}'不正确！请填写正确数值，不能超过99999999.99");
                }
                if (strlen($item['info']) > 450) {
                    throw new \Exception("'{$headArray[3]}'不正确！不能超过450字节");
                }
                //检查用户名
                $dealInfo = $dealLoadModel->find($item['deal_load_id'], 'id,user_id', true);
                if (empty($dealInfo)) {
                    throw new \Exception("'{$headArray[1]}'不正确！");
                }

                if (isset($userCache[$dealInfo['user_id']])) {
                    $inUser = $userCache[$dealInfo['user_id']];
                } else {
                    $inUser = $userModel->find($dealInfo['user_id'], 'id, user_name', true);
                    if (empty($inUser)) {
                        throw new \Exception("'{$headArray[1]}'不正确！");
                    }
                    $userCache[$dealInfo['user_id']] = $inUser;
                }
                //\libs\utils\Runtime::stop('dataCheck');
                //echo 'dataCheck:' . \libs\utils\Runtime::spent('dataCheck') . PHP_EOL;
            } catch (\Exception $e) {
                file_put_contents('/tmp/'.$file . '_result.csv', $line .",0," . iconv('UTF-8', 'GB2312', $e->getMessage()) . PHP_EOL, FILE_APPEND);
                continue;
            }

            $GLOBALS['db']->startTrans();
            try {
                //\libs\utils\Runtime::start('insertAudit');
                // 存库
                $currentTime = date("Y-m-d H:i:s");
                $logInfo = $currentTime . " A角色批准:admin" . "<br>" . $currentTime . " B角色批准:admin" ;
                $financeAuditModel = new \core\dao\FinanceAuditModel();
                $financeAuditModel['out_name'] = $outUser['user_name'];
                $financeAuditModel['into_name'] = $inUser['user_name'];
                $financeAuditModel['money'] = $item['money'];
                $financeAuditModel['info'] = $item['info'];
                $financeAuditModel['create_time'] = get_gmtime();
                $financeAuditModel['apply_user'] = 'admin';
                $financeAuditModel['status'] = 3;
                $financeAuditModel['update_time'] = get_gmtime();
                $financeAuditModel['log'] = $logInfo;
                $rs1 = $financeAuditModel->insert();
                //\libs\utils\Runtime::stop('insertAudit');
                if (!$rs1) {
                    throw new \Exception("数据插入失败");
                }
                // 转账全部走异步
                //\libs\utils\Runtime::start('transfer');
                $transferService = new \core\service\TransferService();
                $payerType = '转出资金';
                $payerNote = '您的账户向会员' . $inUser['user_name'] . '的账户转入金额' . $money . '元' . ' ' . $item['info'];
                $receiverType = '转入资金';
                $receiverNote = '会员' . $outUser['user_name'] . '的账户向您的账户转入金额' . $money . '元' . ' ' . $item['info'];
                $transferService->payerChangeMoneyAsyn = true;
                $transferService->receiverChangeMoneyAsyn = true;
                $transferService->transferByUser($outUser, $inUser, $item['money'], $payerType, $payerNote, $receiverType, $receiverNote);
                //\libs\utils\Runtime::stop('transfer');
                $GLOBALS['db']->commit();
                file_put_contents('/tmp/'.$file . '_result.csv', $line .",1, " . PHP_EOL, FILE_APPEND);
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                file_put_contents('/tmp/'.$file . '_result.csv', $line .",0,". iconv('UTF-8', 'GB2312', $e->getMessage()) . PHP_EOL, FILE_APPEND);
            }
            //\libs\utils\Runtime::stop('process');
            //echo 'insertAudit:' . \libs\utils\Runtime::spent('insertAudit') . PHP_EOL;
            //echo 'transfer:' . \libs\utils\Runtime::spent('transfer') . PHP_EOL;
            //echo 'process:' . \libs\utils\Runtime::spent('process') . PHP_EOL;
        }
    }
    fclose($handle);
}
