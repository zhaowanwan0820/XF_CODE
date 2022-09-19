<?php
/**
 * 红包互斥检验
 */
namespace web\controllers\hongbao;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\rpc\Rpc;
use core\service\BonusService;
use core\dao\UserModel;
use core\dao\BonusModel;

class CheckMobile extends BaseAction 
{

    /**
     * 活动来源
     */
    const FROM_XSLB = 'xslb3.1';
    const FROM_ZZHB = 'zzhb';

    /**
     * 检查配置
     * checkNewByConf:通过配置检查是否区分新老用户，false则全部为新手专属
     * checkMutex:是否检查互斥规则
     * @var array
     */
    private $platformConfig = array(
        self::FROM_ZZHB => array( // 红包活动
            'checkNewByConf' => true,
            'checkMutex' => true,
        ),
        self::FROM_XSLB => array( // 新手
            'checkNewByConf' => false,
            'checkMutex' => true,
            'type' => BonusModel::BONUS_CASH_NORMAL_FOR_NEW,
        ),
    );

    /**
     * 页面提示
     */
    const TIP = "领取本活动奖励（%s元投资红包，有效期至%s）将放弃已领取的活动奖励：<br/>“%s”活动奖励（%s元投资红包，有效期至%s）。";

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "fromPlatform" => array("filter" => array($this, 'validateType'), "message" => "参数错误"),
            "mobile"  => array("filter" => "required", "message" => "手机号缺失"),
            "sn" => array("filter" => "string"),
            "cn" => array("filter" => "string", "option" => array("optional" => true)),
        );
        if (!$this->form->validate()) {
            $this->show($this->form->getErrorMsg(), 2000);
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $from = $data['fromPlatform'];
        $fromPlatform = $this->fromPlatform = $this->platformConfig[$from];

        $owner = UserModel::instance()->findByViaSlave('mobile=":mobile"', 'id', array(':mobile' => $data['mobile']));
        $this->bonusService = new BonusService();

        if (isset($data['sn'])) {
            $groupID = $this->bonusService->encrypt($data['sn'], 'D');
            $groupInfo = $this->bonusService->getGroupByIdUseCache($groupID);
            if ($groupInfo['bonus_type_id'] == BonusService::TYPE_ACTIVITY ) {
                $this->active = $this->bonusService->getActivityByGroupId($groupID);
            }
        }

        switch ($from) {
            case self::FROM_XSLB:
                $fromPlatform['activityKey'] = $fromPlatform['type'];
                break;
            case self::FROM_ZZHB:
                $fromPlatform['activityKey'] = $this->active['name'];
                break;
        }

        // 新老用户检验，默认均为新用户专享
        if ($fromPlatform['checkNewByConf']) { // 需要检查活动配置，某些活动可为非新手专享
            if (empty($this->active)) {
                $this->show('sn非法', 2000);
            }
            if ($this->active['is_diff_new_old_user'] == 1 && !empty($owner)) {
                $this->show('用户已经注册', 4001);
            }
        } else {
            if (!empty($owner)) {
                $this->show('用户已经注册', 4001);
            }
        }

        // 互斥检验
        if ($fromPlatform['checkMutex']) {
            $bonus = $this->bonusService->checkMutex($data['mobile'], $fromPlatform['activityKey']);
            if ($bonus !== true) {// 互斥
                // 检查当前活动是否领过
                switch ($from) {
                    case self::FROM_XSLB:
                        if ($bonus['type'] == $fromPlatform['type']) {
                            $this->show('success', 0, true);
                        }
                        break;
                    case self::FROM_ZZHB:
                        if ($bonus['group_id'] == $groupID) {
                            $this->show('success', 0, true);
                        }
                        break;
                    default:
                        break;
                }
                // 获取邀请人信息
                $replaceUser =  $activityUser = '';
                if ($referUid = $bonus['refer_mobile']) {
                    $user = UserModel::instance()->find($referUid);
                    $replaceUser = $user['real_name'];
                }
                if (isset($this->form->data['cn'])) {
                    $coupon = $this->rpc->local('CouponService\checkCoupon', array($this->form->data['cn']));
                    if ($coupon !== FALSE) {
                        $referUid = $coupon['refer_user_id'];
                        $user = UserModel::instance()->find($referUid);
                        $activityUser = $user['real_name'];
                    }
                }

                $retData = $this->getActivityInfo($from);
                $date = date('Y年m月d日', $bonus['expired_at']);
                $msg = "返利冲突";
                $retData['activityUser'] = $this->formatName($activityUser);
                $retData['replaceMoney'] = $bonus['money'];
                $retData['replaceName'] = $bonus['name'];
                $retData['replaceDate'] = $date;
                $retData['replaceUser'] = $this->formatName($replaceUser);
                $this->show($msg, 4002, $retData);

            }
        }
        $this->show("success", 0, true);

    }

    public function validateType($fromPlatform)
    {
        if (empty($fromPlatform)) return false;
        if (!in_array($fromPlatform, array_keys($this->platformConfig))) return false;
        return true;
    }

    public function getActivityInfo($from)
    {
        $money = $date = 0;
        switch ($from) {
            case self::FROM_XSLB:
                $rebateRule = $this->bonusService->getBonusNewUserRebate('CASH_BONUS_RULE');
                $forNew = $rebateRule['forNew'];
                
                $money = $forNew['money'];
                $date = strtotime("+{$forNew['send_limit_day']} day");
                $name = BonusModel::$nameConfig[$this->fromPlatform['type']];
                break;
            case self::FROM_ZZHB:
                
                $money = $this->active['multiple_money'];
                $mStart = $this->active['range_money_start'];
                $mEnd = $this->active['range_money_end'];

                if ($money > 0) {
                    $money = number_format($money, 2);
                } elseif ($mStart != $mEnd) {
                    $money = $mStart . "~" . $mEnd;
                } else {
                    $money = $mStart;
                }
                $date = strtotime("+{$this->active['valid_day']} day");
                $name = $this->active['name'];
                break;
            
            default:
                # code...
                break;
        }

        return array('activityMoney' => $money, 'activityDate' => date('Y年m月d日', $date), 'activityName' => $name);
    }

    protected function formatName($name)
    {
        $nameLen = mb_strlen($name);
        if ($nameLen <= 0) return "";
        return str_repeat('*', $nameLen - 1) . mb_substr($name, $nameLen - 1);
    }

    public function show($msg, $code, $data=null)
    {
        $res = array(
            'errCode' => $code,
            'errMsg' => $msg,
        );
        if (!empty($data)) {
            $res['data'] = $data;
        }
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($res));
    }
}
