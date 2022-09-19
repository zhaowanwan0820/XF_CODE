<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 10:29:55
 * @encode UTF-8编码
 */
class P_Conf_Xssfilter {

    public static $xss = array(
        'int_paragraph' => '50',
        'pcre_backtrack' => '300000',
        'black_tags' => array(
            0 => 'script',
            1 => 'style',
            2 => 'title',
            3 => 'xml',
        ),
        'black_charactors' => array(
            0 => '&#65279;',
        ),
        'white_tags' => array(
            'embed' => array(
                'is_single' => '1',
                'check_adjacent' => '1',
                'attrs' => array(
                    'src' => "/^(http|ftp):\\/\\/[^<>\'\\\"]*/i",
                    'width' => '/^\\d*$/',
                    'height' => '/^\\d*$/',
                    'align' => '/^\\w*$/',
                    'pluginspage' => '/^\\s*http:\\/\\/www\\.macromedia\\.com\\/go\\/getflashplayer\\s*$/',
                    'wmode' => '/^\\w*$/',
                    'type' => '/^application\\/x-shockwave-flash$/',
                    'allowFullScreen' => '/^true|false*$/',
                    'allowScriptAccess' => '/^\\s*never\\s*$/i',
                    'quality' => '/^\\w*$/',
                    'play' => '/^true|false$/',
                    'loop' => '/^true|false$/',
                    'menu' => '/^true|false$/',
                ),
            ),
            'img' => array(
                'is_single' => '1',
                'check_adjacent' => '1',
                'attrs' => array(
                    'alt' => '/^$/',
                    'width' => '/^\\d*$/',
                    'height' => '/^\\d*$/',
                    'style' => '/^(\\s*float:\\s*(left|right);?\\s*|\\s*display:\\s*block;?\\s*|\\s*width:\\s*\\d+px;?\\s*|\\s*height:\\s*\\d+px;?\\s*|\\s*margin:(\\s*\\d+px)+;?\\s*|\\s*margin-top:\\s*\\d+px;?\\s*|\\s*margin-right:\\s*\\d+px;?\\s*|\\s*margin-bottom:\\s*\\d+px;?\\s*|\\s*margin-left:\\s*\\d+px;?\\s*|\\s*clear:\\s*both;?\\s*|\\s*border\\s*:\\s*\\d+px;?\\s*)*$/i',
                    'src' => "/^(http|ftp):\\/\\/[^<>\'\\\"]*/i",
                ),
            ),
            'br' => array(
                'is_single' => '1',
                'check_adjacent' => '1',
            ),
            'b' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'trim_blank' => array(
                    0 => 'head',
                ),
            ),
            'strong' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'trim_blank' => array(
                    0 => 'head',
                ),
            ),
            'i' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'trim_blank' => array(
                    0 => 'head',
                ),
            ),
            'em' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
            'h2' => array(
                'is_single' => '0',
                'check_adjacent' => '0',
                'trim_blank' => array(
                    0 => 'head',
                ),
            ),
            'h3' => array(
                'is_single' => '0',
                'check_adjacent' => '0',
                'trim_blank' => array(
                    0 => 'head',
                ),
            ),
            'a' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'trim_blank' => array(
                    0 => 'head',
                ),
                'attrs' => array(
                    'title' => '/[^<>\'\\\"]*/',
                    'href' => '/^\\s*((http|https|mailto|ftp)[^<>\'\\\"]|@)\\s*/',
                    'target' => '/^\\s*(_blank|_self|_parent|_top)\\s*$/i',
                    'class' => '/[^<>\'\\\"]*/',
                ),
            ),
            'p' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'align' => '/^\\s*(left|right|center);?\\s*$/i',
                    'style' => '/^text-align:\\s*(left|right|center);?\\s*$/i',
                ),
            ),
            'div' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'style' => '/^(float:\\s*(left|right);?\\s*|\\s*text-align:\\s*(left|right|center);?\\s*|\\s*(color|font):\\s*(#\\w*|[\\w\\s]*|rgb\\s*\\(\\s*\\d+\\s*,\\s*\\d+\\s*,\\s*\\d+\\s*\\));?\\s*)*$/i',
                    'class' => '/qoute-content|inner-qoute-content|qoute-photo|inner-qoute-photo|qoute-detail/',
                ),
            ),
            'span' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'style' => '/^\\s*font-family\\s*:\\s*((\'|\\\"|&quot;|&#039;)?(楷体|楷体_GB2312|宋体|微软雅黑|黑体|,|\\s|\\w|sans-serif)(\'|\\\"|&quot;|&#039;)?)+;?\\s*|\\s*(color|font-size|background-color)\\s*:\\s*(#\\w*|[\\w\\s]*|rgb\\s*\\(\\s*\\d+\\s*,\\s*\\d+\\s*,\\s*\\d+\\s*\\));?\\s*|\\s*text-decoration\\s*:\\s*(underline|overline|line-through|blink)\\s*;?\\s*$/i',
                    'class' => '/qoute-time|qoute-user/',
                ),
            ),
            'u' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
            'ul' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'style' => '/^list-style-type:\\s*(decimal|disc);\\s*$/i',
                ),
            ),
            'ol' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'style' => '/^list-style-type:\\s*(decimal|disc);\\s*$/i',
                ),
            ),
            'li' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'style' => '/^text-align:\\s*(left|right|center);?\\s*$/i',
                ),
            ),
            'blockquote' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
            'table' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'class' => '/^\\s*costs\\s*/',
                ),
            ),
            'tr' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
            'th' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
                'attrs' => array(
                    'class' => '/^\\s*(cost-item|cost-value|cost-remark)\\s*/',
                ),
            ),
            'td' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
            'thead' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
            'tbody' => array(
                'is_single' => '0',
                'check_adjacent' => '1',
            ),
        ),
    );

}
