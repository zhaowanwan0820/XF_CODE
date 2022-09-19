<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 14:57:54
 * @encode UTF-8编码
 */
class P_Xssfilter_Htmlstate_Openingtag {

    public function parseAttributes($context) {
        $Attributes = array();
        $context->ignore_whitespace();
        $attributename = $context->scan_until_characters("=/> \n\r\t");
        while ($attributename != '') {
            $attributevalue = NULL;
            $context->ignore_whitespace();
            $char = $context->scan_character();
            if ($char == '=') {
                $context->ignore_whitespace();
                $char = $context->scan_character();
                if ($char == '"') {
                    $attributevalue = $context->scan_until_string('"');
                    $context->ignore_character();
                } else if ($char == "'") {
                    $attributevalue = $context->scan_until_string("'");
                    $context->ignore_character();
                } else {
                    $context->unscan_character();
                    $attributevalue = $context->scan_until_characters("> \n\r\t");
                }
            } else if ($char !== NULL) {
                $attributevalue = NULL;
                $context->unscan_character();
            }
            $Attributes[$attributename] = $attributevalue;
            $context->ignore_whitespace();
            $attributename = $context->scan_until_characters("=/> \n\r\t");
        }
        return $Attributes;
    }

    public function parse($context) {
        $tag = $context->scan_until_characters("/> \n\r\t");
        if ($tag != '') {
            $this->attrs = array();
            $Attributes = $this->parseAttributes($context);
            $char = $context->scan_character();
            if ($char == '/') {
                $char = $context->scan_character();
                if ($char != '>') {
                    $context->unscan_character();
                }
                $context->handler_object_element->{$context->handler_method_opening}($tag, $Attributes);
                $context->handler_object_element->{$context->handler_method_closing}($tag);
            } else {
                $context->handler_object_element->{$context->handler_method_opening}($tag, $Attributes);
            }
        }
        return HTML_STATE_START;
    }

}
