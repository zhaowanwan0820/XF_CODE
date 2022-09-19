<?php
require(dirname(__FILE__) . '/../app/init.php');

use libs\utils\RemoteTag;
use libs\utils\RedisTag;
use libs\utils\PaymentApi;

$startUserId = intval($argv[1]);
$endUserId = intval($argv[2]);

$moveTags = ['FirstBidAmount', 'O2O_ACQUIRE_COUPON_GROUP', 'O2O_GENERATE_COUPON', 'O2O_STHB'];
if (!RedisTag::getAllTagAttr()) {
    $tagAttrs = RemoteTag::getAllTagAttr();
    foreach ($tagAttrs as $key => $attr) {
        RedisTag::setTagAttr($key, $attr);
    }

    $redisTagAttrs = RedisTag::getAllTagAttr();
    if ($redisTagAttrs !== $tagAttrs) {
        PaymentApi::log('RemoteTag属性迁移异常');
    }
}

for ($startUserId; $startUserId <= $endUserId; ++$startUserId) {
    usleep(1000);
    $tags = RemoteTag::getUserAllTag($startUserId);
    if (empty($tags)) {
        PaymentApi::log('RemoteTag无需迁移|'. $startUserId);
        continue;
    }

    $preMoveTags = array();
    foreach ($tags as $key => $value) {
        if (!in_array($key, $moveTags)) {
            continue;
        }
        $preMoveTags[$key] = $value;
    }

    if (empty($preMoveTags)) {
        PaymentApi::log('RemoteTag无需迁移|'. $startUserId);
        continue;
    }

    foreach ($preMoveTags as $key => $tags) {
        if ($key == 'FirstBidAmount' || $key == 'O2O_STHB') {
            RedisTag::setKvTag($startUserId, array($key => $tags));
        } else {
            foreach($tags as $tag) {
                RedisTag::appendSetTag($startUserId, $key, $tag);
            }
        }
    }

    $redisTags = RedisTag::getUserAllTag($startUserId);
    if ($redisTags != $preMoveTags) {
        PaymentApi::log("RemoteTag迁移后异常|" . $startUserId . '|preMoveTag|' . json_encode($preMoveTags, JSON_UNESCAPED_UNICODE). '|redisTag|' . json_encode($redisTags, JSON_UNESCAPED_UNICODE));
        continue;
    }
    PaymentApi::log("RemoteTag迁移成功|" . $startUserId . '|preMoveTag|' . json_encode($preMoveTags, JSON_UNESCAPED_UNICODE). '|redisTag|' . json_encode($redisTags, JSON_UNESCAPED_UNICODE));
}
