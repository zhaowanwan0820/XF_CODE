<?php
require(dirname(__FILE__) . '/../app/init.php');
require(APP_ROOT_PATH.'libs/utils/PhalconRPCInject.php');
use core\service\O2OService;
use core\service\GameService;
use core\service\UserService;
use core\event\O2ORetryEvent;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\GameEnum;
error_reporting(E_ERROR);
ini_set('display_errors', 1);
\libs\utils\PhalconRpcInject::init();

class WorldcupCoupon {
    public $o2oService = null;
    public $gameService = null;
    public $userService = null;
    public $unit = 200;//折算除数
    public function __construct() {
        $this->o2oService = new O2OService();
        $this->gameService = new GameService();
        $this->userService = new UserService();
    }
    public function run() {
        /**
         * 1.分页排行榜数据
         * 2.过滤数据，符合处理条件的折算大转盘次数并发券
         */
        //目前参与人数在10万级别，按照20万循环，分页数据为0时退出
        $pageSize = 100;
        $gameService = $this->gameService;
        $o2oService = $this->o2oService;
        $couponGroupId = app_conf('WORLDCUP_SCORE_COUPONGROUPID');
        $eventId = app_conf('WORLDCUP_GAME_EVENTID');
            
        for($i = 1; $i<=2000; $i++) {
            $pageList = $gameService->getRankScoreList($i, $pageSize);
            if ($pageList) {
                $formatUsers = $this->formatUsers($pageList);
                if ($formatUsers['couponUsers']) {
                    $o2oService->batchSendCoupons($formatUsers['couponUsers'], $couponGroupId);
                }
                if ($formatUsers['gameUsers']) {
                    $o2oService->batchIncrGameTimes($formatUsers['gameUsers'], $eventId, GameEnum::SOURCE_TYPE_SCORE_CONVERT, '积分折算');
                }
            } else {
                break;
            }
        }
    }

    public function formatUsers($users) {
        $res = array('couponUsers' => array(), 'gameUsers' => array());
        $userService = $this->userService;
        foreach($users as $item) {
            $userId = $item['userId'];
            if ($userService->checkEnterpriseUser($userId)) {
                PaymentApi::log("world_score_coupon, 企业用户". $userId ."不参与世界杯积分:");
                continue;
            }
            $userInfo = $userService->getUser($userId);
            if (in_array($userInfo['group_id'], GameEnum::$WORLDCUP_BLACKLIST_USERGROUP) ) {
                PaymentApi::log("world_score_coupon, userId: ".$userId.', groupId: '.$userInfo['group_id'].'会员组不参与世界杯积分');
                continue;
            }

            $gameTime = ceil($item['score']/$this->unit) - 1;
            if ($gameTime > 0) {
                $res['gameUsers'][$userId] = array('userId' => $userId, 'times' => $gameTime, 'token' => GameEnum::GUESS_SOURCE_SCORE_CONVERT.$userId);
            }
            $res['couponUsers'][$userId] = array('userId' => $userId, 'token' => 'worldcup_'.$userId);
        }
        return $res;
    }
}


$task = new WorldcupCoupon();
$task->run();


