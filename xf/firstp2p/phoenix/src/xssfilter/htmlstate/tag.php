<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 14:54:00
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Tag {

    public function parse($context) {
        switch ($context->scan_character()) {
            case '/':
                return HTML_STATE_CLOSING_TAG;
                break;
            case '?':
                return HTML_STATE_PI;
                break;
            case '%':
                return HTML_STATE_JASP;
                break;
            case '!':
                return HTML_STATE_ESCAPE;
                break;
            default:
                $context->unscan_character();
                return HTML_STATE_OPENING_TAG;
        }
    }

}
