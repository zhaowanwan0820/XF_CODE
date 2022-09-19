<?php
namespace api\controllers\help;


use api\controllers\BaseAction;
use libs\web\Form;

class Contactus extends FaqIndex {

    public function init() {
        //parent::init();
        $this->form = new Form();
    }

    public function invoke() {
    }
}
