<?php

//生产测试和生产上firstp.cn、firstp2p.com域名http强制跳转到https, session共享的坑
if (is_http_to_https_env() && is_http_to_https_domain() && !is_https_protocol()) {
    $url = (sprintf('location:https://%s%s', get_current_host(), $_SERVER['REQUEST_URI']));
    header($url);
    exit;
}

function is_https_protocol() {
    if (isset($_SERVER['HTTP_XHTTPS']) && 1 == $_SERVER['HTTP_XHTTPS']) {
        return true;
    } else {
        return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? true : false;
    }
}

function is_http_to_https_domain() {
    $current_domain = get_current_host();
    return in_array($current_domain, array('firstp2p.com', 'firstp2p.cn', 'www.firstp2p.com', 'www.firstp2p.cn','wangxinlicai.com','www.wangxinlicai.com','ncfwx.com','www.ncfwx.com'));
}

function is_http_to_https_env() {
    $env = strtolower(trim(get_cfg_var("phalcon.env")));
    return in_array($env, array('pdtest', 'product'));
}

function get_current_host() {
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        return strtolower($_SERVER['HTTP_X_FORWARDED_HOST']);
    }

    if (isset($_SERVER['HTTP_HOST'])) {
        return strtolower($_SERVER['HTTP_HOST']);
    }

    if (isset($_SERVER['SERVER_NAME'])) {
        return strtolower($_SERVER['SERVER_NAME']);
    }

    if (isset($_SERVER['SERVER_ADDR'])) {
        return strtolower($_SERVER['SERVER_ADDR']);
    }

    return '';
}
