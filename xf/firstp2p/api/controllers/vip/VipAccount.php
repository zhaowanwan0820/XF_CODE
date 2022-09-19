<?php
/**
 * 会员等级&特权
* @author yanjun <yanjun5@ucfgroup.com>
*/
namespace api\controllers\vip;
use api\controllers\AppBaseAction;
use libs\web\Form;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use libs\utils\PaymentApi;

class VipAccount extends AppBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }
    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        if (!$this->rpc->local("VipService\isShowVip", array($user['id']), VipEnum::VIP_SERVICE_DIR)) {
            return false;
        }

        $vipGradeInfo = $this->rpc->local("VipService\getVipGrade",array($user['id']), VipEnum::VIP_SERVICE_DIR);
        //会员等级
        $vipGrade = $vipGradeInfo['service_grade'] ? : 0;
        $vipInfo = $this->rpc->local("VipService\getVipAccoutForGrade",array($user['id'],$vipGrade), VipEnum::VIP_SERVICE_DIR);
        if(empty($vipInfo)){
            $this->setErr("ERR_PARAMS_ERROR", 'get vipInfo fail');
            return false;
        }

        $vipInfo['point'] = $vipGradeInfo['point'];//总经验值
        //获取vip详情信息，含升级相关字段
        $upgradeInfo = $this->rpc->local("VipService\getVipAccountInfo",array($user['id']), VipEnum::VIP_SERVICE_DIR);
        $vipInfo['upgradeDesc'] = '您当前已是最高等级';
        //获取即将过期信息
        $pointInfo = $this->rpc->local("VipService\getExpireInfoAndIncome",array($user['id']), VipEnum::VIP_SERVICE_DIR);
        $vipInfo['expireDesc'] = $pointInfo['expirePoint'] ? '即将过期'.$pointInfo['expirePoint'] : '';
        if ($upgradeInfo['nextGradeName']) {
            //如果未升级到最高级，文案显示:还需x经验值升级为钻石会员;即将过期x
            $vipInfo['upgradeDesc'] = '还需'.$upgradeInfo['remain_invest_money'].'经验值升级为'.$upgradeInfo['nextGradeName'];
        }
        if($vipGrade != 0){
            $vipInfo['gradeingDesc'] = $vipGradeInfo['is_relegated'] ? '保级中' : '';//是否保级
            $vipInfo['actName'] = $vipGradeInfo['actName'];//实际等级
            if ($vipGradeInfo['is_relegated']) {
                //保级中，文案显示：x天后结束保级并将为xx会员  ; 还需x免降级
                $vipInfo['upgradeDesc'] = ceil($upgradeInfo['remain_relegated_time']/86400).'天后结束保级并降为'.$vipInfo['actName'];
                $vipInfo['expireDesc'] = '还需'.$upgradeInfo['remain_relegated_point'].'免降级';
            }
        }
        $vipInfo['progress'] = sprintf("%.3f", $vipGradeInfo['upgrade_percent']);//升级进度
        $vipInfo['isUpgrade'] = $upgradeInfo['is_upgrade'] ? 1 : 0; //是否升级
        if ($vipInfo['isUpgrade']) {
            if($upgradeInfo['gift_count'] > 0){
                $vipInfo['upgradeGiftDesc'] = '获得升级礼包'.$upgradeInfo['gift_count'].'个';
                $vipInfo['upgradeDiscountDesc'] = '请查看您的“优惠券”';
            }else{
                $isMainSiteUser = $this->rpc->local("VipService\checkMainSite",array($user['id']), VipEnum::VIP_SERVICE_DIR);
                $vipInfo['upgradeGiftDesc'] = $isMainSiteUser ? "您已领过该等级的升级礼包\n只可享受一次":"人生需要规划\n理财贵在坚持";
            }
        }

        $this->json_data = $vipInfo;
    }

}
