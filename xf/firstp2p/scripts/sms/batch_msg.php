<?php
/**
 * 批量发站内信接口
 * 用户ID文件不要超过100万行
 * 使用方式: php batch_msg.php userid.csv
 */
set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once(dirname(__FILE__).'/../../app/init.php');
\libs\utils\Script::start();

use libs\utils\Process;
use core\service\MsgBoxService;

if (Process::exists($_SERVER['PHP_SELF'])) {
    exit("进程已经启动\n");
}

//用户列表文件
$filename = $argv[1];
if (!is_file($filename)) {
    exit('用户ID文件没传或不存在');
}

if (strtolower(substr($filename, -4)) !== '.csv') {
    exit('只支持csv格式的文件');
}

if (false === ($handle = fopen($filename, 'r'))) {
    exit('csv 文件打开失败！');
}

$typeId = 1;
$title = '系统通知';
$msgbox = new MsgBoxService();
$header = true;
while(false !== ($data = fgetcsv($handle))) {
    if (8 != count($data)) {
        exit('数据格式错误：csv不是8列！');
    }

    //跳过第一行表头
    if ($header) {
        $header = false;
        continue;
    }

    list($uid, $var1, $var2, $var3, $var4, $var5, $var6, $var7) = $data;
    $content = "根据您通过网信平台签署的《委托投资协议》（合同编号：{$var1}），受托方已于2017年6月{$var2}日将投资款项投向委托投资项目，现经项目贷后管理人员核查发现，该项目融资方未按照约定用途使用资金。为避免您的投资款项遭受损失，受托方向融资方宣布该项目提前到期并要求融资方立即偿还应还款项。目前应还款项均已到账，其中包括您的投资本金{$var3}元，利息{$var4}元（按实际使用天数计算），共计{$var5}元（大写人民币{$var6}元）。为感谢您对网信平台的关注和支持，平台稍后将向您发送价值{$var7}元的投资红包，请您及时登录查看！<p>受托方：深圳市前海新金盎资产管理有限公司；<p>平台方：北京经讯时代科技有限公司；<p>日期：2017年7月7日";

    $msgbox->create($uid, $typeId, $title, $content);

    \libs\utils\Script::log("batch_msg. uid:{$uid}, {$var1}, {$var2}, {$var3}, {$var4}, {$var5}, {$var6}, {$var7}");

}

\libs\utils\Script::end();
