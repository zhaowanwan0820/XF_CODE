<?php
/**
 * 移动端更新管理相关
 * User: JU<zhaopengju@itouzi.com>
 * Date: 2016/09/01
 * Time: 16:09
 */
return array(
    // 安卓整体更新
    '1' => array(
        'select_list' => ' id, switch, updatetime, apk_name, apk_size, version_name, version_code, apk_md5, update_content',
        'select_view' => ' id, type, apk_name, apk_url, apk_size, apk_md5, version_name, version_code, update_image, update_title, update_content, update_version',
        'condition'   => ' type = 1 and status = 0 ',
    ),
    // 安卓渠道更新
    '2' => array(
        'select_list' => ' id, switch, updatetime, apk_name, apk_size, version_name, version_code, apk_md5, channel',
        'select_view' => ' id, type, apk_name, apk_url, apk_size, apk_md5, version_name, version_code, update_image, update_title, update_content, update_version, channel',
        'condition'   => ' type = 2 and status = 0 ',
    ),
    // ios自动更新
    '3' => array(
        'select_list' => ' id, switch, updatetime, version_name, update_content',
        'select_view' => ' id, type, version_name, update_image, update_title, update_content, update_version',
        'condition'   => ' type = 3 and status = 0 ',
    ),
    // 安卓整体修复
    '4' => array(
        'select_list' => ' id, switch, updatetime, apk_name, apk_size, version_name, version_code, apk_md5',
        'select_view' => ' id, type, apk_name, apk_url, apk_size, apk_md5, version_name, version_code',
        'condition'   => ' type = 4 and status = 0 ',
    ),
    // 安卓渠道修复
    '5' => array(
        'select_list' => ' id, switch, updatetime, apk_name, apk_size, version_name, version_code, apk_md5, channel',
        'select_view' => ' id, type, apk_name, apk_url, apk_size, apk_md5, version_name, version_code, channel',
        'condition'   => ' type = 5 and status = 0 ',
    )
);