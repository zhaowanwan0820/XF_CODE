<?php
/**
 * DealConfirm controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-07-26
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use libs\utils\Rpc;
use core\service\DealService;
use core\service\UserCarryService;
use core\service\PaymentService;
use core\dao\EnterpriseModel;
use api\conf\Error;

/**
 * 预约确认页
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class BidConfirm extends DuotouBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'token is required',
            ),
            'deal_id' => array(
                    'filter' => 'required',
                    'message' => 'deal_id is required',
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke()
    {
        if (!$this->dtInvoke()) {
            return false;
        }

        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }

        //仅允许投资户投资
        if(!$this->rpc->local('UserService\allowAccountLoan', array($userInfo['user_purpose']))){
            return $this->assignError('ERR_INVESTMENT_USER_CAN_BID');//非投资账户不允许投资

        }

        if($userInfo['idcardpassed'] == 3) {
            return $this->assignError('ERR_MANUAL_REASON','您的身份信息正在审核中，预计1到3个工作日内审核完成');
        }

        $userService = new UserService($userInfo['id']);
        $isEnterprise = $userService->isEnterpriseUser();
        if (!$isEnterprise) {
            // 如果未绑定手机
            if(intval($userInfo['mobilepassed'])==0 || intval($userInfo['idcardpassed'])!=1 || !$userInfo['real_name']) {
                return $this->assignError('ERR_MANUAL_REASON', '请进行身份认证');
            }

        }

        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $dealService = new DealService();
        $ageCheck = $dealService->allowedBidByCheckAge($userInfo);

        if($ageCheck['error'] == true){
            return $this->assignError('ERR_IDENTITY_NO_VERIFY','本项目仅限18岁及以上用户投资');
        }

        //智多新直接跳转到普惠wap
        $phWapUrl = app_conf('NCFPH_WAP_HOST').'/duotou/DealDetail?activity_id='.$data['deal_id'].'&token='.$data['token'];
        return app_redirect($phWapUrl);
        $rpc = new Rpc('duotouRpc');
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'project_id' => intval($data['deal_id']),
            'user_id' => $userInfo['id'],
            'isEnterprise' => $isEnterprise,
        );
        $request->setVars($vars);
        if ($rpc) {
            $response = $rpc->go('\NCFGroup\Duotou\Services\Project','getProjectByIdForBid',$request);
        }
        if(!$response) {
            return $this->assignError('ERR_SYSTEM','系统繁忙，如有疑问，请拨打客服电话：95782');
        }
        if ($response['errCode'] != 0) {
            return $this->assignError('ERR_SYSTEM',$response['errMsg']);
        }
        $deal = $response['data'];

        if (empty($deal)) {
            return $this->assignError('ERR_DEAL_NOT_EXIST');
        }
        if ($deal['isFull'] && $deal['userLoanMoney'] == 0) {
            return $this->assignError('ERR_DEAL_NOT_EXIST','额度已满，仅允许持有用户查看');
        }
        $advisoryId = $deal['projectInfo']['manageId'];//取值为管理方

        $advisoryInfo = $this->rpc->local('DealAgencyService\getDealAgency', array($advisoryId));

        $advisoryId = $deal['projectInfo']['manageId'];//取值为管理方

        $advisoryInfo = $this->rpc->local('DealAgencyService\getDealAgency', array($advisoryId));

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($userId));
        $this->tpl->assign('total_money', bcadd($userInfo['money'], $bonus['money'], 2));

        if (app_conf('PAYMENT_ENABLE') && empty($userInfo['payment_user_id'])) {
            return $this->assignError('ERR_MANUAL_REASON','无法投标');
        }

        $bankcardInfo = $this->rpc->local("UserBankcardService\getBankcard", array($userInfo['id']));
        if(!$bankcardInfo || $bankcardInfo['status'] != 1){
            return $this->assignError('ERR_MANUAL_REASON','请完善银行卡信息');
        }
        $this->tpl->assign("deal", $deal);
        $this->tpl->assign('token',$data['token']);
    }
    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

}
