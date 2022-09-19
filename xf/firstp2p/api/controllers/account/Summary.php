<?php

/**
 * 账户总览
 * @author wenyanlei@ucfgroup.com
 * */

namespace api\controllers\account;

use libs\utils\ABControl;
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Finance;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\UserService;
use core\service\life\UserTripService;
use core\dao\vip\VipAccountModel;
use core\service\BwlistService;
use core\service\PaymentService;
use core\service\CouponService;
use api\conf\ConstDefine;

class Summary extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token不能为空'),
                'site_id' => array('filter' => 'int','option' => array('optional' => true)),
                );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        //特殊用户增长执行时间
        if (defined('SPECIAL_USER_ACCESS')) {
            set_time_limit(120);
        }
        $data = $this->form->data;
        //$info = $this->rpc->local('UserService\getUserByCode', array(htmlentities($data['token'])));
        $info = $this->getUserByToken();
        $site_id = isset($data['site_id']) ? $data['site_id'] : 1;
        if (empty($info)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        } else {
            $user_info = $info;

            if ($this->app_version >= 330) {
                $user_statics = $this->rpc->local('AccountService\getUserSummary', array($user_info['id']));
                $p2p_user_statics = (new \core\service\ncfph\AccountService())->getSummary($user_info['id']);
                $user_statics = $this->mergeP2pData($user_statics, $p2p_user_statics);
            } else {
                $user_statics = $this->rpc->local('AccountService\getUserStaicsInfo', array($user_info['id']));
                $compound = $user_statics['compound'];
            }

            $bank = [];
            $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $user_info['id']));
            if (!empty($bankcard)) {
                $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
                $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
                $bank_name = $bank['name'];
                $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
                $bank_icon = empty($attachment['attachment']) ? "" : 'http:' . $GLOBALS['sys_config']['STATIC_HOST'] . '/' . $attachment['attachment'];
                $bind_bank = $bankcard['verify_status'];
            } else {
                $bank_no = '无';
                $bank_name = '';
                $bank_icon = '';
                $bind_bank = 0;
            }
            $result['cardVerifyStatus'] = $bind_bank;
            //目前控制app 我的黄金里面 是否显示买金按钮，以后需要扩展为黄金所有标是否展示
            $userGoldAssets = $this->rpc->local('GoldService\getUserGoldAssets', array($user_info['id']));
            $result['hasGoldAssets'] = bccomp($userGoldAssets,0,5) > 0 ? 1 : 0;
            $isWhite=$this->rpc->local('GoldService\isWhite', array($user_info['id']));
            $result['isGoldSale']=( $isWhite==true )? 1 : 0 ;
            //判断是否显示多投数据
            $result['isDuotou'] = 1;
            if (app_conf('DUOTOU_SWITCH') == '0' || !is_duotou_inner_user()) {
                $result['isDuotou'] = 0;
            }

            if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
                $bonus = $this->rpc->local('BonusService\get_useable_money', array($user_info['id']));
            } else {
                $bonus['money'] = 0;
            }
            $result['name'] = $user_info['real_name'] ? $user_info['real_name'] : "无";
            $result['card'] = $bankcard['bankcard'] ? formatBankcard($bankcard['bankcard']) : "无";
            $result['mobile'] = $user_info['mobile'] ? moblieFormat($user_info['mobile'],$user_info['mobile_code']) : "无";
            $result['country_code'] = $user_info['country_code'] ? $user_info['country_code'] : "cn";
            $result['email'] = $user_info['email'] ? mailFormat($user_info['email']) : "无";
            $result['email_sub'] = $user_info['email_sub'] ? $user_info['email_sub'] : "无";
            $result['idno'] = $user_info['idno'];
            $result["idcard_passed"] = $user_info['idcardpassed'];
            $result["photo_passed"] = $user_info['photo_passed'];
            $result['remain'] = format_price($user_info['money'], false);
            $result['frozen'] = format_price($user_info['lock_money'], false);
            $result['p2p_principal'] = $user_statics['p2p_principal'];

            //专享在投
            $result['zxCorpus'] = format_price(bcsub($user_statics['corpus'], $user_statics['cg_principal'], 2), false);

            $userAssetInfo = array();
            if ($this->app_version >= 330) {
                // 冻结中减掉智多鑫待投本金，资产中增加智多鑫待投本金
                $user_statics['corpus'] = bcadd($user_statics['corpus'], $user_statics['dt_norepay_principal'], 2);
                if ($site_id == 1) {
                    $user_info['lock_money'] = bcsub($user_info['lock_money'], $user_statics['dt_remain'], 2);
                }

                $result['frozen'] = format_price($user_info['lock_money'], false);
                $result['corpus'] = format_price($user_statics['corpus'], false);
                $result['income'] = format_price($user_statics['income'], false);
                $result['earning_all'] = format_price($user_statics['earning_all'], false);
                $result['total'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['corpus']), 2);
                $result['totalExt'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['corpus']), 2);

                $userAssetInfo['corpus'] = $result['corpus'];
                $userAssetInfo['income'] = $result['income'];
                $userAssetInfo['earning_all'] = $result['earning_all'];
                $userAssetInfo['total'] = $result['total'];
                $userAssetInfo['totalExt'] = $result['totalExt'];
            } else {
                $result['earning_all'] = format_price($user_statics['earning_all'], false);
                $result['income'] = format_price($user_statics['interest'], false);
                $result['corpus'] = format_price($user_statics['principal'], false);
                $result['total'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['stay']), 2);
                $result['compound_principal'] = format_price($compound['compound_money'], false);
                $result['compound_interest'] = format_price($compound['interest']);
                //新资产总额 2015-09-15
                $result['totalExt'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['principal']), 2);

                $userAssetInfo['earning_all'] = $result['earning_all'];
                $userAssetInfo['income'] = $result['income'];
                $userAssetInfo['corpus'] = $result['corpus'];
                $userAssetInfo['total'] = $result['total'];
                $userAssetInfo['compound_principal'] = $result['compound_principal'];
                $userAssetInfo['compound_interest'] = $result['compound_interest'];
                $userAssetInfo['totalExt'] = $result['totalExt'];
            }
            $result["bank_no"] = $bank_no;
            $result["bank"] = $bank_name;
            $result["bank_icon"] = $bank_icon;
            $result['bonus'] = format_price($bonus['money'], false);

            $result['userNum'] = numTo32($info['id'], 0);//会员编号

            // 判断是否是企业用户
            $isEnterpriseUser = $this->rpc->local('UserService\checkEnterpriseUser', array($user_info['id']));
            $result['isEnterpriseUser'] = $isEnterpriseUser ? 1 : 0;
            if ($isEnterpriseUser) {
                $result['verify_status'] = $this->rpc->local('EnterpriseService\getVerifyStatus', array($user_info['id']));
                // 增加企业接收短信手机号的第一个手机号, 没有则返回""
                $result['enterprisePhone'] = $this->rpc->local('EnterpriseService\getFirstReceiveMsgPhone', array($user_info['id']));
                $result['userNum'] = numTo32($info['id'], 1); //企业编号
            }

            $result['user_purpose'] = $user_info['user_purpose'];
            // BEGIN { 增加商户参数支持 汇源推广系统
            $result['isSeller'] = $user_info['isSeller'];
            $result['couponUrl'] = $user_info['couponUrl'];
            $result['isO2oUser'] = $user_info['isO2oUser'];
            $result['showO2O'] = $user_info['showO2O'];
            // } END

            if ((int)app_conf('USER_JXSD_TRANSFER_SWITCH') !== 1) {
                $user_info['is_dflh'] = 0;
            }

            // 判断是否东方联合用户
            $result['is_dflh'] = intval($user_info['is_dflh']);

            $result['bind_bank'] = $bind_bank;
            $gy_sum = $this->rpc->local('DealLoadService\getDealLoadByLoantype', array($user_info['id'], 7, 0, 0, true));
            $result['gySum'] = number_format($gy_sum['sum'], 2);

            $bind_coupon = $this->rpc->local('CouponBindService\getByUserId', array($user_info['id']));
            $result['shortAlias'] = $bind_coupon['short_alias'] ? $bind_coupon['short_alias'] : ""; //服务人邀请码
            $result['inviteCode'] = $bind_coupon['invite_code'] ? $bind_coupon['invite_code'] : ""; //邀请人邀请码
            $result['bindCoupon'] = $bind_coupon['short_alias'];
            $result['canBindCoupon'] = $bind_coupon['is_fixed'] ? 0 : 1;
            $couponService = new CouponService();
            if(!empty($bind_coupon) && !empty($bind_coupon['refer_user_id']) && !$couponService->hasServiceAbility($bind_coupon['refer_user_id'])){
                $result['shortAlias'] = '';
            }

            $haveServiceEntrance = $this->rpc->local('CouponService\haveServiceEntrance', array($user_info['id']));
            $result['haveServiceEntrance'] = $haveServiceEntrance? 1 : 0;


            // 网信生活相关
            $isTrip = (int)UserTripService::isTripOpen();
            $result['life_info'] = [
                'trip_open' => $isTrip,// 用户是否在出行白名单
                'bank_number' => $this->rpc->local('PaymentUserService\getMyCardNumber', [$user_info['id']], 'life'),// 用户的银行卡数量
            ];
            if ($isTrip) {
                $mobile_code = (!empty($user_info['mobile_code']) && $user_info['mobile_code'] != '86')? $user_info['mobile_code'] . '-' : '';
                $result['life_info']['trip_mobile'] = !empty($user_info['mobile']) ? $mobile_code . $user_info['mobile'] : '';
            }

            //获取用户微信头像
            $avatar = $this->rpc->local('UserImageService\getUserImageInfo', array($info['id']));
            $result['avatar'] = '';
            $avatarFrom = '';//记录用户头像来源
            if ($avatar && !empty($avatar['attachment'])) {
                $avatarFrom = 'UserImageService本地用户头像';
                if (stripos($avatar['attachment'], 'http') === 0) {
                    $result['avatar'] = $avatar['attachment'];
                } else {
                    $result['avatar'] = 'http:' . (isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : '//static.firstp2p.com') . '/' . $avatar['attachment'];
                }
            } else {
                $avatar = $this->rpc->local('UserProfileService\getUserHeadImg', array($info['mobile']));
                if (!empty($avatar['headimgurl']) && stripos($avatar['headimgurl'], 'http') === 0) {
                    $avatarFrom = 'UserProfileService调用的微信用户头像';
                    $result['avatar'] = $avatar['headimgurl'];
                }
            }
            Logger::info(implode(" | ", array(__CLASS__, 'useravatar', $result['avatar'], 'mobile:' . user_name_format($info['mobile'], 3), 'uid:' . $user_info['id'], $avatarFrom)));
            $result['bad_avatar_md5'] = app_conf('WEIXIN_DEFAULT_IMG_MD5');

            //存管相关

            //专享总资产
            $result['wxAssets'] = format_price(bcsub($result['totalExt'], $user_statics['cg_principal'], 2), false);
            //专享在投
            $result['wxCorpus'] = format_price(bcsub($user_statics['corpus'], $user_statics['cg_principal'], 2), false);
            //p2p在投
            $result['svCorpus'] = format_price($user_statics['cg_principal'], false);
            // 用户当日充值总金额
            $result['dayChargeAmount'] = $user_statics['dayChargeAmount'];
            //智多鑫在投
            $result['zdxCorpus'] = format_price($user_statics['dt_norepay_principal'], false);

            $result['svInfo'] = $this->rpc->local('SupervisionService\svInfo', array($user_info['id']));

            // 企业站用户是否绑定的海口联合农商行的银行卡，否则不显示充值按钮（绑卡状态已验证+已开通网信账户）
            $result['boundHaikouBank'] = 0;
            if (!empty($user_info['payment_user_id']) && !empty($bankcard) && $bankcard['status'] == 1) {
                $userObj = new UserService($user_info['id']);
                if ($userObj->isEnterpriseUser() && !empty($bank['short_name']) && $bank['short_name'] == 'HKBC') {
                    $result['boundHaikouBank'] = 1;
                }
            }

            // 网信PC大额充值的开关
            $result['offlineChargeOpen'] = 1;
            // 用户绑定的银行是否在大额充值银行的白名单里
            $result['wxOfflineChargeSwitch'] = PaymentService::isOfflineBankList($user_info['id']);
            // 用户是否在大额充值的用户黑名单里
            $result['wxOfflineUserBlack'] = PaymentService::inBlackList($user_info['id']);

            //p2p总资产
            $svMoney = empty($result['svInfo']['svMoney']) ? 0 : $result['svInfo']['svMoney'];
            $result['svAssets'] = Finance::addition(array($svMoney, $user_statics['cg_principal']), 2);
            if ($this->is_firstp2p) {
                $result['svAssets'] = Finance::addition(array($result['svAssets'], $user_statics['dt_load_money']), 2);
            }

            if (!empty($result['svInfo']['isSvUser']) && $result['svInfo']['isActivated'] != 0) {
                $result['svUrl'] = sprintf(
                    $this->getHost() . "/payment/Transit?params=%s",
                    urlencode(json_encode(['srv' => 'info', 'return_url' => 'storemanager://api?type=closecgpages']))
                );
            } else {
                //普惠未绑卡用户去标准开户
                $srv = (empty($bankcard) && $site_id == 100) ? 'registerStandard' : 'register';
                $result['svUrl'] = sprintf(
                    $this->getHost() . "/payment/Transit?params=%s",
                    urlencode(json_encode(['srv' => $srv, 'return_url' => 'storemanager://api?type=closecgpages']))
                );
            }
            $result['wxUrl'] = sprintf(
                $this->getHost() . "/payment/Transit?params=%s",
                urlencode(json_encode(['srv' => 'superInfo', 'return_url' => 'storemanager://api?type=closecgpages']))
            );
            $result['isWxFreepayment'] = 1;
            /*
            if (in_array($site_id, [1, 100]) && !empty($result['svInfo']['status']) && !empty($bankcard['bankcard'])) {
                $result['isWxFreepayment'] = intval($user_info['wx_freepayment']);
            }
            */

            // 主站资产总额计算黄金，暂且将黄金加到网信资产
            if ($site_id == 1 && $this->app_version >= 460 && $isWhite) {
                try {
                    $request = new RequestCommon();
                    $request->setVars(array('userId'=>$user_info['id'], 'type' => 0));
                    $myGold = $this->rpc->local('GoldService\myGold', array($request));
                } catch(\Exception $e) {
                }

                if (!empty($myGold) && $myGold['errCode'] == 0) {
                    $wxAssets = bcsub($result['totalExt'], $user_statics['cg_principal'], 2);
                    $result['wxAssets'] = Finance::addition(array($wxAssets, $myGold['data']['hold_gold_market_value']), 2);
                    //黄金在投
                    $result['goldCorpus'] = format_price($myGold['data']['hold_gold_market_value'], false);
                }
            }
            //会员信息
            $result['isShowVip'] = 0;
            $result['vipGrade'] = 0;
            if ($this->rpc->local("VipService\isShowVip", array($user_info['id']), VipEnum::VIP_SERVICE_DIR)) {
                $result['isShowVip'] = 1;
                $vipInfo = $this->rpc->local("VipService\getVipInfoForSummary",array($user_info['id']), VipEnum::VIP_SERVICE_DIR);
                $result['vipGrade'] = $vipInfo['vipGrade'];
                $result['isUpgrade'] = $vipInfo['isUpgrade'];
                $result['vipGradeName'] = $vipInfo['vipGradeName'];
                $result['upgradeCondition'] = strcmp("当前经验值0", $vipInfo['upgradeCondition']) ? $vipInfo['upgradeCondition'] : '';
            }

            //信仔相关
            $result['isShowXinchat'] = true;
            $result['isShowXiaoneng'] = \libs\utils\ABControl::getInstance()->hit('xiaoneng');
            $result['isShowXiaonengWap'] = \libs\utils\ABControl::getInstance()->hit('xiaonengWap');

            //判断用户是否首投
            $result['isBid'] = $this->isBid($user_info['id']);

            //分享相关
            $result['euid'] = $this->rpc->local("OpenService\getEuid", array(array('userId' => $user_info['id'])));

            // AR红包开关
            $result['isShowArBonus'] = \libs\utils\ABControl::getInstance()->hit("ARBonus") ? 1 : 0;

            //账户授权管理开关
            $result['accountAuthManageSwitch'] = (int) app_conf('ACCOUNT_AUTH_MANAGE_SWITCH');

            // 红包禁用开关
            $result['bonusDisabled'] = $this->rpc->local('BonusService\isBonusEnable') ? 0 : 1;

            //电商生活白名单
            $result['wxLifeOpen'] = $this->rpc->local("ApiConfService\isWhiteList", ['wxLifeOpen']);

            //基金黑名单
            $bwlistService = new BwlistService();
            $result['fundOpen'] = 1;
            if ($bwlistService->inList('FUND_BLACK_LIST', $user_info['id'])) {
                $result['fundOpen'] = 0;
            }

            $result['simuOpen'] = $bwlistService->inList('SIMU_WHITE_LIST', $user_info['id']) ? 1 : 0;
            $usual = \SiteApp::init()->dataCache->getRedisInstance()->get("pefund_usual_" . $user_info['id']);
            $result['simuUsual'] = $usual ? $usual : 0;

            // 是否使用新h5充值
            $result['useH5Charge'] = $this->rpc->local('SupervisionFinanceService\isNewBankLimitOpen');
            //网贷大额充值地址
            $result['p2pOfflineChargeUrl'] = $this->rpc->local("SupervisionFinanceService\getOfflineChargeApiUrl", [$user_info['id'], $bankcard]);

            //能否绑定多张银行卡
            $result['canBindMultiCard'] = 0;
            $isMainlandRealAuthUser = $this->rpc->local('AccountService\isMainlandRealAuthUser', array($user_info));
            $inMultiCardWhite = $this->rpc->local('AccountService\inMultiCardWhite', array($user_info));
            //非企业 大陆实名且在白名单里
            if ( !$isEnterpriseUser && $isMainlandRealAuthUser && $inMultiCardWhite ) {
                $result['canBindMultiCard'] = 1;
            }

            //信宝余额
            $result['candyOpen'] = 0;
            $candyAccountInfo = $this->rpc->local('CandyAccountService\getAccountInfo', [$user_info['id']], 'candy');
            $result['candyAmount'] = !empty($candyAccountInfo) ? number_format($candyAccountInfo['amount'], 3) : 0.000;

            //信仔生日祝福链接
            $xinzaiBlessUrl = "";
            $userAccount = VipAccountModel::instance()->getVipAccountByUserId($user_info['id']);
            if ($userAccount['service_grade'] > 0) {
                if (\libs\utils\User::birthdayWishesCheck($user_info)) {
                    $xinzaiBlessUrl = \core\dao\BonusConfModel::get("XINZAI_BLESS_GAME_URL");
                }
            }
            $result['xinzaiBlessUrl'] = $xinzaiBlessUrl;

            //合规用户黑名单
            $mobile = $user_info['mobile'] ? $user_info['mobile'] : 0 ;
            $result['isCompliantUser'] = intval($this->rpc->local("BwlistService\inList", array('COMPLIANCE_BLACK', $user_info['id'])) || $this->rpc->local("BwlistService\inList", array('COMPLIANCE_BLACK', $mobile)));
            // 记录用户浏览时候的资产信息
            $userAssetRecord = array(
                'userAssetRecord',
                __CLASS__,
                __FUNCTION__,
                'userId:' . $user_info['id'],
                'assetInfo:' . json_encode($userAssetInfo),
            );
            Logger::debug(implode(',', $userAssetRecord));
            $this->json_data = $result;
        }
    }

    private function mergeP2pData($wxData, $p2pData)
    {
        $fileds = [
            'corpus',
            'income',
            'earning_all',
            'compound_interest',
            'js_norepay_principal',
            'js_norepay_earnings',
            'js_total_earnings',
            'p2p_principal',
            'cg_principal',
            'cg_income',
            'cg_earnings',
            'dt_norepay_principal',
            'dt_load_money',
            'dt_remain',
            'dayChargeAmount',
        ];

        $data = [];
        foreach ($fileds as $filed) {
            $data[$filed] = bcadd($wxData[$filed], $p2pData[$filed], 2);
        }

        return $data;
    }
}
