<?php

/**
 * @file HTML_Parser.php
 * @author gengyankun(gengyankun@baidu.com)
 * @date 2009/11/11 11:11:11
 * @version $vision: 1.0 $ 
 * @brief Main parser components
 * */
M::D('HTML_STATE_STOP', 0);
M::D('HTML_STATE_START', 1);
M::D('HTML_STATE_TAG', 2);
M::D('HTML_STATE_OPENING_TAG', 3);
M::D('HTML_STATE_CLOSING_TAG', 4);
M::D('HTML_STATE_ESCAPE', 6);
M::D('HTML_STATE_JASP', 7);
M::D('HTML_STATE_PI', 8);

class P_Xssfilter_Parser {

    public $handler_default;
    public $handler_method_closing;
    public $handler_method_data;
    public $handler_method_escape;
    public $handler_method_jasp;
    public $handler_method_opening;
    public $handler_method_pi;
    public $handler_object_data;
    public $handler_object_element;
    public $handler_object_escape;
    public $handler_object_jasp;
    public $handler_object_pi;
    public $length;
    public $position;
    public $rawtext;
    public $state = array();

    public function __construct() {
        $this->state[HTML_STATE_START] = new P_Xssfilter_Htmlstate_Starting();
        $this->state[HTML_STATE_CLOSING_TAG] = new P_Xssfilter_Htmlstate_Closingtag();
        $this->state[HTML_STATE_TAG] = new P_Xssfilter_Htmlstate_Tag();
        $this->state[HTML_STATE_OPENING_TAG] = new P_Xssfilter_Htmlstate_Openingtag();
        $this->state[HTML_STATE_PI] = new P_Xssfilter_Htmlstate_Pi();
        $this->state[HTML_STATE_JASP] = new P_Xssfilter_Htmlstate_Jasp();
        $this->state[HTML_STATE_ESCAPE] = new P_Xssfilter_Htmlstate_Escape();
    }

    public function ignore_character() {
        $this->position += 1;
    }

    public function ignore_whitespace() {
        $this->position += strspn($this->rawtext, " \n\r\t", $this->position);
    }

    public function judge_start_tag() {
        if ($this->position >= $this->length) {
            return true;
        }
        $start = $this->position + 1;
        $next_start = strpos($this->rawtext, '<', $start);
        $next_end = strpos($this->rawtext, '>', $start);
        if ($next_end === false) {
            $str = substr($this->rawtext, $this->position, $this->length - $this->position);
            $this->position = $this->length;
            return str_replace('<', '&lt;', $str);
        }
        if ($next_start === false || $next_start > $next_end) {
            return true;
        }
        $temp_str = substr($this->rawtext, $this->position, $next_end - $this->position + 1);
        $temp_pos = strrpos($temp_str, '<');
        $temp_data = substr($temp_str, 0, $temp_pos);
        $this->position = $this->position + $temp_pos;
        return str_replace('<', '&lt;', $temp_data);
    }

    public function parse($data) {
        $this->rawtext = $data;
        $this->length = strlen($data);
        $this->position = 0;
        $this->_parse();
    }

    private function _parse($state = HTML_STATE_START) {
        do {
            $state = $this->state[$state]->parse($this);
        } while ($state != HTML_STATE_STOP && $this->position < $this->length);
    }

    public function scan_character() {
        if ($this->position < $this->length) {
            return $this->rawtext{$this->position++};
        }
    }

    public function scan_until_characters($string) {
        $startpos = $this->position;
        $length = strcspn($this->rawtext, $string, $startpos);
        $this->position += $length;
        return substr($this->rawtext, $startpos, $length);
    }

    public function scan_until_string($string) {
        $start = $this->position;
        $this->position = strpos($this->rawtext, $string, $start);
        if ($this->position === FALSE) {
            $this->position = $this->length;
        }
        return substr($this->rawtext, $start, $this->position - $start);
    }

    public function unscan_character() {
        $this->position -= 1;
    }

}
