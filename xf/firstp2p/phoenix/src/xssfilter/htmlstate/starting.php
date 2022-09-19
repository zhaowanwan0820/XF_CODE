<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 14:51:37
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Starting {

    public function parse($context) {
        $data = $context->scan_until_string('<');
        if ($data != '') {
            $context->handler_object_data->{$context->handler_method_data}($data);
        }
        $data = $context->judge_start_tag();
        if ($data !== true) {
            $context->handler_object_data->{$context->handler_method_data}($data);
        }
        $context->ignore_character();
        return HTML_STATE_TAG;
    }

}
