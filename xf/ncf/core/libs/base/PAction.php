<?php

namespace libs\base;

abstract class PAction {
    public function execute() {
        $this->init();
        if ($this->_before_invoke()) {
            $this->invoke();
        } else {
            
        }
        $this->_after_invoke();
    }

    abstract public function invoke();
}