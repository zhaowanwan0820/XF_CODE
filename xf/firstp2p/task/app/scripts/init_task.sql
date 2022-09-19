#为线下初始化taskdb

drop database if exists taskdb;
create database taskdb;

use taskdb;

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `task`
-- ----------------------------
DROP TABLE IF EXISTS `task`;
CREATE TABLE `task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` enum('run_timed','run_now','run_waiting','invalid') DEFAULT NULL,
  `nowtry` mediumint(9) NOT NULL DEFAULT '0' COMMENT '尝试次数',
  `maxtry` mediumint(9) NOT NULL COMMENT '最大尝试次数',
  `app_name` varchar(255) NOT NULL DEFAULT 'p2p' COMMENT '此任务所属app',
  `event` mediumblob NOT NULL COMMENT '事件 任务实际执行的内容',
  `event_type` varchar(200) NOT NULL COMMENT '事件类型',
  `priority` enum('normal','low','high') NOT NULL COMMENT '优先级',
  `execute_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '执行时间',
  `paralleled` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否可以并发',
  `start_execute_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始执行时间',
  `end_execute_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束执行时间',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `executetime` (`execute_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=429 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `task_fail`
-- ----------------------------
DROP TABLE IF EXISTS `task_fail`;
CREATE TABLE `task_fail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event` mediumblob NOT NULL COMMENT '事件 任务执行内容',
  `event_type` varchar(200) NOT NULL COMMENT '事件类型',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'taskid',
  `trycnt` mediumint(9) NOT NULL DEFAULT '0' COMMENT '尝试次数',
  `execute_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '执行时间',
  `exception_log` mediumtext COMMENT '异常日志',
  `app_name` varchar(255) NOT NULL DEFAULT 'p2p' COMMENT '此任务所属app',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  `running` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT '是否正在运行',
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `task_success`
-- ----------------------------
DROP TABLE IF EXISTS `task_success`;
CREATE TABLE `task_success` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event` mediumblob NOT NULL COMMENT '事件内容',
  `event_type` varchar(200) NOT NULL COMMENT '事件类型',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'taskid',
  `trycnt` mediumint(9) NOT NULL DEFAULT '0' COMMENT '尝试次数',
  `accept_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '任务接受时间',
  `start_execute_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '任务开时执行时间',
  `end_execute_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '任务结束执行时间',
  `app_name` varchar(255) NOT NULL DEFAULT 'p2p' COMMENT '此任务所属app',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `accept_time` (`accept_time`),
  KEY `start_execute_time` (`start_execute_time`),
  KEY `end_execute_time` (`end_execute_time`),
  KEY `ctime` (`ctime`),
  KEY `mtime` (`mtime`),
  KEY `taskid` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
