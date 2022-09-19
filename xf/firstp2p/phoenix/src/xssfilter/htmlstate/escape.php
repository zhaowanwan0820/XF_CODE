<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 15:07:28
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Escape {

    public function parse($context) {
        $char = $context->scan_character();
        if ($char == '-') {
            $char = $context->scan_character();
            if ($char == '-') {
                $context->unscan_character();
                $context->unscan_character();
                $text = $context->scan_until_string('-->');
                $text .= $context->scan_character();
                $text .= $context->scan_character();
            } else {
                $context->unscan_character();
                $text = $context->scan_until_string('>');
            }
        } else {
            $context->unscan_character();
            $text = $context->scan_until_string('>');
        }
        $context->ignore_character();
        if ($text != '') {
            $context->handler_object_escape->{$context->handler_method_escape}($text);
        }
        return HTML_STATE_START;
    }

}
