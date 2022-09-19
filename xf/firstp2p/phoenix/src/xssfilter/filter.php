<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-11 12:06:20
 * @encode UTF-8编码
 */
class P_Xssfilter_Filter {

    private $_xhtml = '';
    private $_counter = array();
    private $_stack = array();
    private $_dc_counter = array();
    private $_dc_stack = array();
    private $_for_editor = false;
    private $_paragraph_num = 0;
    private $white_tags = array();

    public function __construct($forEditor = false) {
        $this->_for_editor = $forEditor;
    }

    private function _after_parse(&$doc) {
        
    }

    private function _before_parse(&$doc) {
        
    }

    public function clear() {
        $this->_paragraph_num = 0;
        $this->_xhtml = '';
        return true;
    }

    public function close_handler($name) {
        $name = strtolower($name);
        if (isset($this->_dc_counter[$name]) && ($this->_dc_counter[$name] > 0) && in_array($name, P_Conf_Xssfilter::$xss['black_tags'])) {
            while ($name != ($tag = array_pop($this->_dc_stack))) {
                $this->_dc_counter[$tag] --;
            }
            $this->_dc_counter[$name] --;
        }
        if (count($this->_dc_stack) != 0) {
            return true;
        }
        if ((isset($this->_counter[$name])) && ($this->_counter[$name] > 0)) {
            while ($name != ($tag = array_pop($this->_stack))) {
                $this->_close_tag($tag);
            }
            $this->_close_tag($name);
        }
        return true;
    }

    private function _close_tag($tag) {
        $this->_xhtml .= '</' . $tag . '>';
        $this->_counter[$tag] --;
        return true;
    }

    public function data_handler($data) {
        if (count($this->_dc_stack) == 0) {
            if (count($this->_stack) > 0) {
                $cur_tag = $this->_stack[count($this->_stack) - 1];
                if (isset($this->white_tags[$cur_tag]['trim_blank'])) {
                    if (in_array('head', $this->white_tags[$cur_tag]['trim_blank'])) {
                        $data = $this->_trim_head_blank($data);
                    }
                    if (in_array('tail', $this->white_tags[$cur_tag]['trim_blank'])) {
                        $data = $this->_trim_tail_blank($data);
                    }
                }
                if (isset($this->white_tags[$cur_tag]['data_rule'])) {
                    $rule = $this->white_tags[$cur_tag]['data_rule'];
                    if (preg_match($rule, $data)) {
                        $this->_xhtml .= $data;
                    }
                } else {
                    $this->_xhtml .= $data;
                }
            } else {
                $this->_xhtml .= $data;
            }
        }
        return true;
    }

    public function escape_handler($data) {
        return true;
    }

    public function get_xhtml() {
        while ($tag = array_pop($this->_stack)) {
            $this->_close_tag($tag);
        }
        $this->_xhtml = trim($this->_xhtml);
        $this->_xhtml = str_replace("\r\n", "\n", $this->_xhtml);
        $this->_xhtml = str_replace("\n", "", $this->_xhtml);
        return $this->_xhtml;
    }

    public function open_handler($name, $attrs) {
        $name = strtolower($name);
        if (in_array($name, P_Conf_Xssfilter::$xss['black_tags'])) {
            array_push($this->_dc_stack, $name);
            $this->_dc_counter[$name] = isset($this->_dc_counter[$name]) ? $this->_dc_counter[$name] + 1 : 1;
        }
        if (count($this->_dc_stack) > 0) {
            return true;
        }
        $is_single = 0;
        $check_adjacent = 1;
        $ok_nest_arr = array();
        if (array_key_exists($name, $this->white_tags)) {
            $is_single = $this->white_tags[$name]['is_single'];
            if (isset($this->white_tags[$name]['check_adjacent'])) {
                $check_adjacent = intval($this->white_tags[$name]['check_adjacent']);
            }
            if (array_key_exists('nesting', $this->white_tags[$name])) {
                $ok_nest_arr = $this->white_tags[$name]['nesting'];
            }
        } else {
            return true;
        }
        if (count($this->_stack) > 0 && count($ok_nest_arr) > 0) {
            if ($check_adjacent == 1) {
                $last_tag = $this->_stack[count($this->_stack) - 1];
                if (in_array($last_tag, $ok_nest_arr)) {
                    return true;
                }
            } else if ($check_adjacent == 0) {
                foreach ($ok_nest_arr as $invalidNestTag) {
                    if (in_array($invalidNestTag, $this->_stack)) {
                        return true;
                    }
                }
            }
        }
        if (isset($this->white_tags[$name]['is_child']) && $this->white_tags[$name]['is_child'] == 1 && count($this->_stack) == 0) {
            return true;
        }
        if ($is_single === 1) {
            if ($name == "br") {
                $this->_paragraph_num = intval($this->_paragraph_num) + 1;
            }
            if (count($this->_stack) == 0 && $this->_for_editor == true && $name == "br" && ($this->_paragraph_num % P_Conf_Xssfilter::$xss['int_paragraph'] == 0)) {
                $this->_xhtml .= "<br /></div><div>";
            } else {
                $this->_xhtml .= '<' . $name;
                $this->_write_attrs($name, $attrs);
                $this->_xhtml .= ' />';
            }
            return true;
        }
        $this->_xhtml .= '<' . $name;
        $this->_write_attrs($name, $attrs);
        $this->_xhtml .= '>';
        array_push($this->_stack, $name);
        $this->_counter[$name] = isset($this->_counter[$name]) ? $this->_counter[$name] + 1 : 1;
        return true;
    }

    public function parse($doc, $config) {
        $this->white_tags = $config['white_tags'];
        $this->_before_parse($doc);
        $this->clear();
        $parser = new P_Xssfilter_Parserentry();
        $parser->set_object($this);
        $parser->set_element_handler('open_handler', 'close_handler');
        $parser->set_data_handler('data_handler');
        $parser->set_escape_handler('escape_handler');
        $parser->set_pi_handler('escape_handler');
        $parser->set_jasp_handler('escape_handler');
        $parser->parse($doc);
        $this->_after_parse($doc);
        return $this->get_xhtml();
    }

    private function _trim_head_blank($data) {
        $data_back = $data;
        $data = preg_replace('/^( |&nbsp;|　)+/', '', $data);
        if ($data == null) {
            $data = $data_back;
        }
        return $data;
    }

    private function _trim_tail_blank($data) {
        $data_back = $data;
        $data = preg_replace('/( |&nbsp;|　)+$/', '', $data);
        if ($data == null) {
            $data = $data_back;
        }
        return $data;
    }

    private function _write_attrs($tag, $attrs) {
        $tag_stan = null;
        if (array_key_exists($tag, $this->white_tags)) {
            $tag_stan = $this->white_tags[$tag];
        } else {
            return true;
        }
        $attr_stan = null;
        if (array_key_exists('attrs', $tag_stan) && is_array($tag_stan['attrs']) && count($tag_stan['attrs']) > 0) {
            $attr_stan = $tag_stan['attrs'];
        } else {
            return true;
        }
        if (is_array($attrs)) {
            foreach ($attrs as $name => $value) {
                $name = strtolower($name);
                if (($value === true) || (is_null($value))) {
                    $value = $name;
                }
                if (array_key_exists($name, $attr_stan)) {
                    $is_match = preg_match($attr_stan[$name], $value);
                    if ($is_match > 0) {
                        $value = str_replace("\"", "&quot;", $value);
                        $this->_xhtml .= ' ' . $name . '="' . $value . '"';
                    }
                }
            }
        }
        return true;
    }

}
