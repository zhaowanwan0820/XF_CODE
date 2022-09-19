<?php
/**
 * 移动端内容管理图片相关
 * User: JU<zhaopengju@itouzi.com>
 * Date: 2016/8/16
 * Time: 16:09
 */
return array(
    // APP首页功能按钮
    'app_home_icon'      => array(
        'width'     => 78,
        'height'    => 78,
        'size'      => 3072,
        'format'    => array('image/png'),
        'select'    => ' id, number, title, picture, url1, bt_url2, bt_version, bt_advert_logo',
        'condition' => ' type = "app_home_icon" and status = 0 ',
    ),
    // APP发现页图标
    'app_find_icon'      => array(
        'width'     => 54,
        'height'    => 54,
        'size'      => 3072,
        'format'    => array('image/png'),
        'select'    => ' id, number, title, picture, url1, bt_url2, bt_version, bt_advert_logo',
        'condition' => ' type = "app_find_icon" and status = 0 ',
    ),
    // H5首页功能按钮
    'wap_home_icon'      => array(
        'width'     => 78,
        'height'    => 78,
        'size'      => 3072,
        'format'    => array('image/png'),
        'select'    => ' id, number, title, picture, url1',
        'condition' => ' type = "wap_home_icon" and status = 0 ',
    ),
    // APP首页公告图片
    'app_home_notice'    => array(
        'width'     => 590,
        'height'    => 170,
        'size'      => 51200,
        'format'    => array('image/png'),
        'select'    => ' id, number, picture, is_default',
        'condition' => ' type = "app_home_notice" and status = 0 ',
    ),
    // APP邀请好友文案
    'app_invite_friends' => array(
        'width'     => 550,
        'height'    => 94,
        'size'      => 20480,
        'format'    => array('image/png'),
        'select'    => ' id, number, picture, is_default',
        'condition' => ' type = "app_invite_friends" and status = 0 ',
    ),
    // APP项目列表直投图
    'app_item_direct'    => array(
        'width'     => 750,
        'height'    => 170,
        'size'      => 20480,
        'format'    => array('image/png'),
        'select'    => ' id, number, picture, show_version',
        'condition' => ' type = "app_item_direct" and status = 0 ',
    ),
    // APP项目列表债权图
    'app_item_bond'      => array(
        'width'     => 750,
        'height'    => 170,
        'size'      => 20480,
        'format'    => array('image/png'),
        'select'    => ' id, number, picture, show_version',
        'condition' => ' type = "app_item_bond" and status = 0 ',
    ),
    // APP发现页活动图
    'app_find_active'    => array(
        'width'     => 280,
        'height'    => 150,
        'size'      => 51200,
        'format'    => array('image/png'),
        'select'    => ' id, number, picture, title, sub_title, key_word, url1, hide_version, show_version, show_advert_logo',
        'condition' => ' type = "app_find_active" and status = 0 ',
    ),
    // APP首页引导页
    'app_home_guide'    => array(
    'width'     => 750,
    'height'    => 770,
    'size'      => 204800,
    'format'    => array('image/png'),
    'select'    => ' id, number, picture, show_version, show_advert_logo',
    'condition' => ' type = "app_home_guide" and status = 0 ',
    )
);