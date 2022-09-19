<?php

namespace task\apis\deal;

use task\lib\ApiAction;

class Hello extends ApiAction {
    public function invoke() {
        $this->json_data = $this->getParam();
    }
}