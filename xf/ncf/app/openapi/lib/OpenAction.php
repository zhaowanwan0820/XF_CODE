<?php

namespace openapi\lib;

use libs\web\Action;

class OpenAction extends Action {
    public function execute() {
        $this->logInit();

        if ($this->init() !== false) {
            try {
                if ($this->before() && $this->authCheck()) {
                    $this->invoke();
                }
            } catch (\Exception $exc) {
                $err = \openapi\conf\Error::get($exc->getMessage());
                $this->errorCode = $err['errorCode'];
                $this->errorMsg = $err['errorMsg'];
                $this->excCode = $exc->getCode();
                $this->excMsg = $exc->getMessage();
            }
        }

        $this->after();
        $this->log();
    }
}
