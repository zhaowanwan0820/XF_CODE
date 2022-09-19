<?php
/**
 * Contract.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 * 输出投标合同
 *
 * Class Contract
 * @package api\controllers\deals
 */
class Contract extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'dealId' => array('filter' => 'int'),
            "token" => array("filter" => "required", "message" => "token is required"),
            "attchment" => array("filter" => "string", "message" => "附件合同地址有误"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return $this->return_error();
        }

        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return $this->return_error();
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return $this->return_error();
        }

        $data = $this->form->data;
        $dealId = $data['dealId'];
        $deal = $this->rpc->local('DealService\getDeal', array($dealId, true, false));
        //请求普惠数据---- Start -----//
        if (empty($deal) || (isset($deal['deal_type']) && $deal['deal_type'] == '0')) {
            // 网信查不到的直接转到普惠
            $phWapUrl = app_conf('NCFPH_WAP_HOST').'/deals/contract?id='.$data['id'].'&dealid='.$dealId.'&token='.$data['token'];
            return app_redirect($phWapUrl);
        }
        //请求普惠数据---- End -----//

        // 如果是附件合同
        $is_attachment = false;
        if (!empty($data['attchment'])) {
            $is_attachment = true;
            $this->tpl->assign('attchment', urldecode($data['attchment']));
        } else {
            $id = $this->form->data['id'];
            $dealId = $this->form->data['dealId'];
            $contract = $this->rpc->local("ContractInvokerService\getOneFetchedContract", array('viewer', $id, $dealId,1, $user));
            if (empty($contract)) {
                $this->setErr("ERR_PARAMS_ERROR", "id is error");
                return $this->return_error();
            }

            $contract['content'] = hide_message($contract['content']);
            $this->tpl->assign('contract', $contract);
        }
        $this->tpl->assign('is_attachment', $is_attachment);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

    /**
     * 错误输出
     */
    public function return_error() {
        parent::_after_invoke();
        return false;
    }


}
