<?php

namespace core\service\candy;

use core\service\UserService;
use libs\db\Db;
use libs\utils\Logger;

class CandyWishService
{

    // 获奖
    const WINNER_WISH = 1;

    // 得奖人数
    const WINNER_LIMIT = 11;

    // redis锁键名
    const REDIS_KEY_LOTTERY = 'candy_wish_lottery_';

    private static $products = array(
        1 => array(
            'id' => 1,
            'title' => '佳能相机',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/a76e39ad403da90058560d5d83a0b735/index.jpg.png',
        ),
        2 => array(
            'id' => 2,
            'title' => 'MacBookPro 13',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/4d537d7882ae249d08fad083a4274b5b/index.jpg.png',
        ),
        3 => array(
            'id' => 3,
            'title' => '戴森三件套',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/83752493bab6e1953d1fe590cb7122fb/index.jpg.png',
        ),
        4 => array(
            'id' => 4,
            'title' => '大疆无人机',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/1d6799767aaa50cf27dad887f749762e/index.jpg.png',
        ),
        5 => array(
            'id' => 5,
            'title' => '坚果投影仪',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/6c101c833f53ce6ec3bbedce2ca52849/index.jpg.png',
        ),
        6 => array(
            'id' => 6,
            'title' => '华为MATE20 + WATCH',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/19/f804b70c23706a26b51aa198d7d75e2e/index.jpg.png',
        ),
        7 => array(
            'id' => 7,
            'title' => '森海塞尔耳机',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/9902a3ba71e9eafbf30834d076947ff0/index.jpg.png',
        ),
        8 => array(
            'id' => 8,
            'title' => 'IponeXs',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/80262231ed4f6f4c2ea8e17c9f16954c/index.jpg.png',
        ),
        9 => array(
            'id' => 9,
            'title' => '全自动咖啡机',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/5e611f3c5c385419e52a12c334e56d04/index.jpg.png',
        ),
        10 => array(
            'id' => 10,
            'title' => '凯伍德多功能料理机',
            'image' => 'http://static.firstp2p.com/attachment/201811/01/14/36f283994a039dbd5a59e265694adafd/index.jpg.png',
        ),
    );

    /**
     * 获取所有的商品
     */
    public function getProducts()
    {
        return self::$products;
    }

    /**
     * 获取商品许愿比例
     */
    public function getWishRate($productId)
    {
        $total = Db::getInstance('candy')->getOne("SELECT count(user_id) FROM wish");
        $productTotal = Db::getInstance('candy')->getOne("SELECT count(user_id) FROM wish WHERE product_id = {$productId}");
        return intval($productTotal / $total * 100) . '%' ;
    }

    /**
     * 检查用户是否已经满足投资额度
     */
    public function checkInvest($userId)
    {
        $startTime = strtotime("2018-10-30") - date("Z");
        $endTime = strtotime("2018-11-16") - date('Z');
        $wxLoad = Db::getInstance('firstp2p')->getRow("SELECT money FROM firstp2p_deal_load WHERE user_id={$userId} AND money >= 10000 AND create_time >= {$startTime} AND create_time < {$endTime}");
        if (!empty($wxLoad)) {
            return true;
        }

        $phLoad = \core\service\ncfph\DealLoadService::getUserLoadMoreTenThousand($userId);
        Logger::info("checkInvest.  ph: {$phLoad['money']}");
        if (!empty($phLoad)) {
            return true;
        }

        return false;
    }

    /**
     * 检查用户是否已经许愿
     */
    public function hasMakeWish($userId)
    {
        $wishInfo = Db::getInstance('candy')->getRow("SELECT * FROM wish WHERE user_id = {$userId}");
        if (empty($wishInfo)) {
            return false;
        }

        return true;
    }

    /**
     * 许愿
     */
    public function makeWish($userId, $productId) 
    {
        $data = array(
            'user_id' => $userId,
            'product_id' => $productId,
            'create_time' => time(),
            'lottery_time' => 0,
            'lottery_result' => 0,
        );
        return Db::getInstance('candy')->insert('wish', $data);
    }

    /**
     * 显示用户许愿商品
     */
    public function getUserWish($userId)
    {
        $productId = Db::getInstance('candy')->getOne("SELECT product_id FROM wish WHERE user_id = {$userId}");
        return self::$products[$productId];
    }

    /**
     * 检查用户是否已经抽过奖
     */
    public function hasWishLottery($userId)
    {
        return Db::getInstance('candy')->getOne("SELECT id FROM wish WHERE user_id = {$userId} AND lottery_time > 0");
    }

    /**
     * 抽奖
     */
    public function lottery($userId)
    {
        $winNum = $this->getWinnerNum();
        if ($winNum >= self::WINNER_LIMIT && $this->hasWishLottery($userId)) {
            return true;
        }
        
        if ($winNum >= self::WINNER_LIMIT) {
            $this->setLotteryResult($userId);
            return false;
        }

        if (!$redis = \SiteApp::init()->dataCache->getRedisInstance()) {
            Logger::error("lottery. redis:启动异常");
            throw new \Exception("redis启动异常");
        }

        $response = $redis->executeRaw(array('SET', self::REDIS_KEY_LOTTERY, 0, 'ex', 10, 'nx'));
        if (empty($response)) {
            Logger::info("lottery. user_id:" .$userId. "redis:locked");
            throw new \Exception("系统繁忙");
        }

        if ($this->hasWishLottery($userId)) {
            Logger::info("lottery. user_id:" .$userId. "用户已抽奖");
            $redis->del(self::REDIS_KEY_LOTTERY);
            return true;
        }

        if ($this->getWinnerNum() >= self::WINNER_LIMIT) {
            $this->setLotteryResult($userId);
            return false;
        }

        if (mt_rand(1, $this->getWishNumber() * app_conf('WISH_PROBABILITY')) > 11) {
            $this->setLotteryResult($userId);
            $redis->del(self::REDIS_KEY_LOTTERY);
            return false;
        }

        $this->setLotteryResult($userId, self::WINNER_WISH);
        $redis->del(self::REDIS_KEY_LOTTERY);
        return true;
    }

    /**
     * 获取已中奖人数
     */
    public function getWinnerNum()
    {
        return Db::getInstance('candy')->getOne("SELECT count(id) FROM wish WHERE lottery_result > 0");
    }

    /**
     * 计算投资总人数
     */
    private function getWishNumber()
    {
        return Db::getInstance('candy')->getOne("SELECT count(id) FROM wish");
    }

    /**
     * 标记中奖人
     */
    private function setLotteryResult($userId, $lotteryResult = 0)
    {
        $where = "user_id = {$userId}";
        $data = array(
            'lottery_result' => $lotteryResult,
            'lottery_time' => time(),
        );

        Db::getInstance('candy')->update('wish', $data, $where);
    }

    /**
     * 检查用户是否中奖
     */
    public function checkPrize($userId)
    {
        return Db::getInstance('candy')->getOne("SELECT id FROM wish WHERE user_id={$userId} AND lottery_result=" .self::WINNER_WISH);
    }

    /**
     * 检查用户是否是锦鲤奖
     */
    public function checkKoi($userId)
    {
        $userPrize = Db::getInstance('candy')->getAll("SELECT user_id FROM wish WHERE lottery_result=" .self::WINNER_WISH. " ORDER BY lottery_time ASC");
        if ($userPrize[5]['user_id'] !== $userId) {
            return false;
        }

        return true;
    } 

}