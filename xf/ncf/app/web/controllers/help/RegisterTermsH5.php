<?php

/**
 * Index.php
 *
 * @date 2014-04-17
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace web\controllers\help;

use libs\web\Form;
use web\controllers\BaseAction;

class RegisterTermsH5 extends BaseAction {

    public function init() {
        
    }

    public function invoke() {
        
        // help 没有首页
       
        
        $this->template = 'web/views/help/register_terms_h5.html';
    }

}
