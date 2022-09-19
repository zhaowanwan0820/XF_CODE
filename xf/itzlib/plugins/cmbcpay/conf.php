<?php
/*
 * 主配置文件
 * 这里只配置必选和部分选填参数参数即可
 */

return array(
    'clientId' => '2200003220',//企业客户号
    'userId' => '2200003220001',//登录用户号
    'userPswd' => '',//登录密码
    'language' => 'chs',//服务器响应语言
    'appId' => 'nsbdes',//应用程序编码，目前取nsbdes
    'appVer' => '201',//应用程序版本
    'payerAcct' => '600033029',//付款账户
    'payType' => 0,//付款类型
    
    'proxyPort' => 8080,//代理软件监听端口
    'proxyUrl' => 'http://192.168.23.210:8080/eweb/b2e/connect.do'//代理软件请求URL
);
?>