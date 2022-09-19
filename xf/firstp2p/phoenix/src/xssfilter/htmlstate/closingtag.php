<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 14:55:40
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Closingtag {

    public function parse($context) {
        $tag = $context->scan_until_characters('/>');
        if ($tag != '') {
            $char = $context->scan_character();
            if ($char == '/') {
                $char = $context->scan_character();
                if ($char != '>') {
                    $context->unscan_character();
                }
            }
            $context->handler_object_element->{$context->handler_method_closing}($tag);
        }
        return HTML_STATE_START;
    }

}
