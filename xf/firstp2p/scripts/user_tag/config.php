<?php

/**
 * cnt 是统计语句总数的  用来分页的
 * exec 是真正执行的语句
 */
$config = array(
    'BID_DEAL_YEAR' => array(
        'cnt' => "SELECT count(distinct(user_id))  FROM `firstp2p_deal_load` where deal_id in (select id from firstp2p_deal where repay_time = 12 and loantype != 5)",
        'exec' => "SELECT distinct(user_id) as `user_id` FROM `firstp2p_deal_load` where deal_id in (select id from firstp2p_deal where repay_time = 12 and loantype != 5)"
    ),

    'BID_TEN_THOUSAND' => array(
        'cnt' => 30000,
        'exec' => "select `user_id`, sum(money) as m from firstp2p_deal_load group by user_id having m > 10000 limit 30000"
    ),
    'BF_HB' => array(
        'cnt' => 200000,
        'exec' => "SELECT id as user_id FROM firstp2p_user where id not in (SELECT user_id FROM firstp2p_bonus_group where batch_id = 18) limit 200000"
    ),
    'BID_1218' => array(
        'cnt' => 10000,
        'exec' => "select user_id, sum(money) sum_money from firstp2p_deal_load where create_time>=(UNIX_TIMESTAMP('2014-12-18')-28800) and create_time<(UNIX_TIMESTAMP(concat(CURDATE(),' 17:00'))-28800) group by user_id having sum_money>=((DAYOFMONTH(CURDATE())-20)*1000) limit 10000",
    ),
    'INVITE_1218' => array(
        'cnt' => 10000,
        'exec' => "select l.refer_user_id as user_id, count(DISTINCT l.consume_user_id) count_invite from firstp2p_coupon_log l where l.create_time>=(UNIX_TIMESTAMP('2014-12-18')-28800) and l.create_time<(UNIX_TIMESTAMP(concat(CURDATE(),' 17:00'))-28800) and l.type=2 and l.refer_user_id>0 and exists (select 1 from firstp2p_deal d where d.id=l.deal_id and d.deal_crowd not in (1,8)) group by l.refer_user_id having count_invite>=((DAYOFMONTH(CURDATE())-20)*2) limit 10000",
    ),

    'BID_HUNDRED' => array(
        'cnt' => "select count(user_id) from (select user_id, sum(money) as m from firstp2p_deal_load group by user_id having m >= 100) as bid_more_100",
        'exec' => "select user_id, sum(money) as m from firstp2p_deal_load group by user_id having m >= 100",
    ),

    'BONUS_BBG' => array(
        'cnt' => 30000,
        'exec' => "select l.user_id as user_id, sum(l.money) sum_money, count(id) count_deal from firstp2p_deal_load l where l.create_time>=(UNIX_TIMESTAMP('2014-12-24 18:00')-28800) and l.create_time<(UNIX_TIMESTAMP(concat(CURDATE(),' 18:00'))-28800) and exists (select 1 from firstp2p_deal d where d.id=l.deal_id and d.deal_crowd not in (1,8)) group by user_id having sum_money>=((DAYOFMONTH(CURDATE())-24)*1000) or count_deal>=((DAYOFMONTH(CURDATE())-24)) limit 30000",
    ),

    //14年 累计100+
    'BID2015_100' => array(
        'cnt' => "select count(user_id) from (select `user_id` , sum(money) as m from firstp2p_deal_load where `create_time` > (UNIX_TIMESTAMP('2014-01-01 00:00:00')-28800) AND `create_time` < (UNIX_TIMESTAMP('2014-12-31 16:00:00')-28800) group by user_id having m >= 100) as tmp",
        'exec' => "select `user_id` , sum(money) as m from firstp2p_deal_load where `create_time` > (UNIX_TIMESTAMP('2014-01-01 00:00:00')-28800) AND `create_time` < (UNIX_TIMESTAMP('2014-12-31 16:00:00')-28800) group by user_id having m >= 100",
    ),

    //14年 累计200+
    'BID2015_200' => array(
        'cnt' => "select count(user_id) from (select `user_id` , sum(money) as m from firstp2p_deal_load where `create_time` > (UNIX_TIMESTAMP('2014-01-01 00:00:00')-28800) AND `create_time` < (UNIX_TIMESTAMP('2015-01-01 00:00:00')-28800) group by user_id having m >= 200) as tmp",
        'exec' => "select `user_id` , sum(money) as m from firstp2p_deal_load where `create_time` > (UNIX_TIMESTAMP('2014-01-01 00:00:00')-28800) AND `create_time` < (UNIX_TIMESTAMP('2015-01-01 00:00:00')-28800) group by user_id having m >= 200",
    ),

    //14年 累计300+
    'BID2015_300' => array(
        'cnt' => "select count(user_id) from (select `user_id` , sum(money) as m from firstp2p_deal_load where `create_time` > (UNIX_TIMESTAMP('2014-01-01 00:00:00')-28800) AND `create_time` < (UNIX_TIMESTAMP('2015-01-02 00:00:00')-28800) group by user_id having m >= 300) as tmp",
        'exec' => "select `user_id` , sum(money) as m from firstp2p_deal_load where `create_time` > (UNIX_TIMESTAMP('2014-01-01 00:00:00')-28800) AND `create_time` < (UNIX_TIMESTAMP('2015-01-02 00:00:00')-28800) group by user_id having m >= 300",
    ),

    // 女性投资过2次以上的
    'BID_MORE_FEMALE_0306' => array(
        'cnt' => "select count(id) from firstp2p_user where sex=0 and id in (select uid from firstp2p_user_tag_relation where tag_id = 18)",
        'exec' => "select id as user_id from firstp2p_user where sex=0 and id in (select uid from firstp2p_user_tag_relation where tag_id = 18)",
    ),
    // 2015年1.1 0:00 到3.15  24:00 注册用户且投资 2次以上的
    'FIRST_REG_2015' => array(
        'cnt' => 45367,
        'exec' => "select user_id,count(user_id) as cnt from firstp2p_deal_load as dl where dl.create_time >=(UNIX_TIMESTAMP('2015-01-01 00:00:00')-28800) and dl.create_time <= (UNIX_TIMESTAMP('2015-03-16 00:00:00')-28800) and user_id in (select id from firstp2p_user where create_time>=(UNIX_TIMESTAMP('2015-01-01 00:00:00')-28800) and create_time <= (UNIX_TIMESTAMP('2015-03-16 00:00:00')-28800)) group by dl.user_id having cnt > 1",
    ),
    // 给产融贷_(产融北分)资金渠道_外部菁英资金账户 group_id 95
    'HYJK_MEMBER' => array(
        'cnt' => 'select count(id) from firstp2p_user where group_id=95',
        'exec' => 'select id as user_id from firstp2p_user where group_id=95',
    ),
    // 给存量企业用户打标签
    'USER_TYPE_QY' => array(
        'cnt' => 'select count(id) from firstp2p_enterprise',
        'exec' => 'select user_id from firstp2p_enterprise',
    )
);
?>
