<?php
/**
 * 修改银行卡提交审核
 * @author yanjun<yanjun5@ucfgroup.com>
 */

namespace api\controllers\payment;
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;

class SaveBank extends AppBaseAction {

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token不能为空'),
                'bank_id'=>array('filter'=>'required', 'message' => 'bank_id不能为空'),
                'certStatus' => array('filter' => 'int'),

                'bankzone'=>array('filter'=>'string'),
                'bankcard'=>array('filter'=>'required', 'message' => 'bankcard不能为空'),
                'bankcardSignature' => array('filter'=>'required', 'message' => 'bankcardSignature不能为空'),
                'image_id' => array('filter'=>'int'),
                );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }


    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }
        $userId = $userInfo['id'];

        if (PaymentApi::isServiceDown()) {
            $this->setErr('ERR_MANUAL_REASON', PaymentApi::maintainMessage());
            return false;
        }
        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$userId]) && Supervision::isServiceDown()) {
            $this->setErr('ERR_MANUAL_REASON', Supervision::maintainMessage());
            return false;
        }


        //查询有无修改正在审核中
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($userId));
        if ($bankcard['is_audit'] == 1) {
            $this->setErr('ERR_MANUAL_REASON', '您已提交了一次修改申请，不能重复提交，请耐心等待审核结果!');
            return false;
        }

        $data = $this->rpc->local('BankService\bankInfoXssFilter', array($data));

        //去除空格
        $data['bankcard'] = str_replace(" ", "", $data['bankcard']);

        // 验证银行卡签名
        if (!empty($data['bankcardSignature'])) {
            $cardNoSignature = PaymentApi::instance()->getGateway()->getSignature(['cardNo' => $data['bankcard'], 'certStatus' => $data['certStatus']]);
            if ($cardNoSignature !== $data['bankcardSignature']) {
                $this->setErr('ERR_MANUAL_REASON', '您的银行卡数据发生变化');
                return false;
            }
        }

        if(!in_array(strlen($data['bankcard']), array(12,15,16,17,18,19))) {
            $this->setErr('ERR_MANUAL_REASON', '银行卡号长度不正确');
            return false;
        }
        //查询银行卡已绑定的信息
        $can_bind = $this->rpc->local('BankService\checkBankCardCanBind',array($data['bankcard'], $userId));

        if(!$can_bind){
            $this->setErr('ERR_MANUAL_REASON', '该银行卡已被其他用户绑定，请重新设置提现银行卡。');
            return false;
        }
        if (empty($data['image_id'])) {
            //验证上传的图片
            $uploadFileInfo = array(
                    'file' => $_FILES['Filedata'],
                    'isImage' => 1,
                    'asAttachment' => 1,
                    'asPrivate' => 1,
                    'limitSizeInMB' => 3,
                    'userId' => $userId,
                    );
            $imgInfo = $this->rpc->local('AccountService\getImgId',array($uploadFileInfo));
            if($imgInfo['code'] != 0){
                $this->setErr('ERR_MANUAL_REASON', $imgInfo['msg']);
                return false;
            }else{
                $data['image_id'] = $imgInfo['imageId'];
            }
        }

        $data['bankcard'] = trim($data['bankcard']);
        $data['create_time'] = get_gmtime();
        $data['status'] = 1;    //审核中
        $data['fastpay_cert_status'] = !empty($data['certStatus']) ? $data['certStatus'] : 0; //四要素审核结果
        $data['user_id'] = $userId;
        $data['bankzone'] = htmlspecialchars($data['bankzone'], ENT_QUOTES);
        $res = $this->rpc->local('BankService\saveBank',array($data));
        if(!$res){
            $this->setErr('ERR_MANUAL_REASON', '提交失败');
            return false;
        }
        // 清除验卡状态
        $redisKey = 'authcard_result_'.$userId;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->del($redisKey);
    }

}
