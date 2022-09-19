<?php

require_once dirname(__FILE__).'/../app/init.php';

for ($i=0; $i<64; $i++) {
    $sql = "
CREATE TABLE `firstp2p_user_log_{$i}` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `log_info` varchar(512) NOT NULL DEFAULT '' COMMENT '日志信息',
  `log_time` int(11) NOT NULL COMMENT '记录时间',
  `log_admin_id` int(11) NOT NULL COMMENT '操作管理员id',
  `log_user_id` int(11) NOT NULL COMMENT '会员id',
  `money` double(20,4) NOT NULL COMMENT '金额',
  `score` int(11) NOT NULL COMMENT '积分',
  `point` int(11) NOT NULL COMMENT '信用',
  `quota` double(20,0) NOT NULL COMMENT '限额',
  `lock_money` double(20,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `remaining_money` decimal(20,2) NOT NULL COMMENT '可用资金余额',
  `user_id` int(11) NOT NULL COMMENT '会员id',
  `related_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对方用户id',
  `related_user_show_name` varchar(100) NOT NULL DEFAULT '' COMMENT '对方用户名称',
  `note` varchar(512) NOT NULL DEFAULT '' COMMENT '备注',
  `remaining_total_money` decimal(20,2) NOT NULL COMMENT '用户资金总额',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已删除 0:未删除 1:已删除',
  `item_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '伴随业务ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员账户日志记录表'
";

    $GLOBALS['db']->query($sql);
}
