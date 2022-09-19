<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 15:09:46
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Pi {

    public function parse($context) {
        $data = $context->scan_until_string('>');
        if ($data != '') {
            $context->handler_object_pi->{$context->handler_method_pi}($data);
        }
        $context->ignore_character();
        return HTML_STATE_START;
    }

}
