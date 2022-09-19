<?php

namespace web\controllers\third;

use libs\web\Form;
use libs\utils\Curl;
use web\controllers\BaseAction;

class ConvertCode extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
           'hcode' => array('filter' => 'string'),
           'callback' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $hcode = $this->form->data['hcode'];
        $callback = $this->form->data['callback'];
        $url = "http://cytfinance.wangxinlicai.com/Pwap/Api/getWxCode?hcode={$hcode}&callback={$callback}";
        for ($retry = 1; $retry <= 3; $retry ++) {
            $result = Curl::get($url, true);
            if ($result['code'] === 0 && !empty($result['msg'])) {
                die($result['msg']);
            }
        }
    }

}
