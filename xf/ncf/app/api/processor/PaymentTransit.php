<?php

namespace api\processor;

class PaymentTransit extends Processor {

    public function beforeInvoke() {

        if (!isset($this->params['params'])) {
            $allParams = $this->params;
            $returnUrl = $allParams['return_url'];

            if (strpos($returnUrl, '?') !== false) {
                $returnUrl .= '&is_callback=1';
            } else {
                $returnUrl .= '?is_callback=1';
            }

            $returnUrl = parent::replaceUrlScheme($returnUrl);
            $allParams['return_url'] = urldecode($returnUrl);

            $this->params['params'] = json_encode($allParams);
        }
    }

}
