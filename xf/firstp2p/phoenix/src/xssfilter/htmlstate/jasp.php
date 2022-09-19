<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 15:09:04
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Jasp {

    public function parse($context) {
        $text = $context->scan_until_string('%>');
        if ($text != '') {
            $context->handler_object_jasp->{$context->handler_method_jasp}($text);
        }
        $context->ignore_character();
        $context->ignore_character();
        return HTML_STATE_START;
    }

}
