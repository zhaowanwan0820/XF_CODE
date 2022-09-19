<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * 选择银行列表页
 *
 * @uses BaseAction
 * @package default
 */
class BankList extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $bankList = $GLOBALS['dict']['CREDITLOAN_BANKLIST'];
        $this->tpl->assign('bank_list', $bankList);
    }
}
