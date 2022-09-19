<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 15:19:12
 * @encode UTF-8编码
 */
class P_Xssfilter_Parserentry {

    public $state_parser;

    public function __construct() {
        $this->state_parser = new P_Xssfilter_Parser();
        $null_handler = new P_Xssfilter_Nullhandler();
        $this->set_object($null_handler);
        $this->set_element_handler('do_nothing', 'do_nothing');
        $this->set_data_handler('do_nothing');
        $this->set_pi_handler('do_nothing');
        $this->set_jasp_handler('do_nothing');
        $this->set_escape_handler('do_nothing');
    }

    public function get_current_position() {
        return $this->state_parser->position;
    }

    public function get_length() {
        return $this->state_parser->length;
    }

    public function parse($data) {
        $this->state_parser->parse($data);
    }

    public function set_data_handler($data_method) {
        $this->state_parser->handler_object_data = $this->state_parser->handler_default;
        $this->state_parser->handler_method_data = $data_method;
    }

    public function set_element_handler($opening_method, $closing_method) {
        $this->state_parser->handler_object_element = $this->state_parser->handler_default;
        $this->state_parser->handler_method_opening = $opening_method;
        $this->state_parser->handler_method_closing = $closing_method;
    }

    public function set_escape_handler($escape_method) {
        $this->state_parser->handler_object_escape = $this->state_parser->handler_default;
        $this->state_parser->handler_method_escape = $escape_method;
    }

    public function set_jasp_handler($jasp_method) {
        $this->state_parser->handler_object_jasp = $this->state_parser->handler_default;
        $this->state_parser->handler_method_jasp = $jasp_method;
    }

    public function set_object($object) {
        if (is_object($object)) {
            $this->state_parser->handler_default = $object;
            return true;
        } else {
            return false;
        }
    }

    public function set_pi_handler($pi_method) {
        $this->state_parser->handler_object_pi = $this->state_parser->handler_default;
        $this->state_parser->handler_method_pi = $pi_method;
    }

}
