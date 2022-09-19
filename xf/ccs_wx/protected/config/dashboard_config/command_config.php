<?php
return array(

     //金币监控脚本相关配置
     'gold_monitor' => array(
          'all_user_gold_increase' => 700000,//当日论坛全部用户累计金币增加数超过70万报警
          'single_user_gold_increase' => 1000,//当日单个用户累计金币增加数超过1000报警
          'all_user_gold_exchange' => -450000,//当日论坛全部用户累计兑换积分超过4500万报警
          'single_user_gold_exchange' => -10000,//当日单个用户累计兑换积分超过100万超过报警
          'gold_monitor_run_email_list' => array(//金币监控脚本收件人
              'guoxinze@itouzi.com',
              'renqingnan@itouzi.com',
              'zhenxiaomeng@itouzi.com',
              'zhangjian@itouzi.com',
              'chenghan@itouzi.com',
              'hanshiqi@itouzi.com',
              'genglequn@itouzi.com',
              'zhaowanwan@itouzi.com',
          ),
     ),
     
);
?>






















