<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once dirname(__FILE__).'/../../app/init.php';


// 获取输入参数
$start = 1;
if ( count($argv)<2 ){
    echo '`which php` flushUserProfileById.php ${start_userId} ${end_userId}'."\n";
    exit(0);
}
$start = intval($argv[1]);
$end = intval($argv[2]);

use core\service\UserProfileService;
use core\dao\UserModel;

$userProfileService = new UserProfileService();
$userProfileService->flushData($start,$end);
