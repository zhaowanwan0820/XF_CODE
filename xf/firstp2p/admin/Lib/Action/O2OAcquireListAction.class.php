<?php
/**
 * O2OAcquireList class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
use core\service\O2OService;
use core\service\UserService;
use core\dao\OtoConfirmLogModel;
use core\service\CouponService;
use core\dao\UserModel;
use core\dao\DealLoadModel;
use core\service\UserTagService;
use core\service\RemoteTagService;
use core\dao\DealModel;
use core\dao\CompoundRedemptionApplyModel;
use NCFGroup\Protos\O2O\RequestGetCouponInfo;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\dao\OtoAcquireLogModel;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use core\service\oto\O2OCouponGroupService;
use core\service\ncfph\DealLoadService as PhDealLoadService;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\service\CouponBindService;

class O2OAcquireListAction extends CommonAction{

    public static $userToolTips = array();
    public static $dealInfos = array();
    public static $userCache = array();

    public function __construct() {
        \libs\utils\PhalconRPCInject::init();
        parent::__construct();
        $this->model = M('OtoAcquireLog', 'Model', true);
    }

    public function index() {
        $this->assign('actionEnum', CouponGroupEnum::$TRIGGER_MODE_FOR_ADMIN);

        if (empty($_GET)) {
            $this->display();
            return false;
        }

        $userService = new UserService();
        //定义条件
        $where = ' 1=1';

        $this->assign('statusEnum', CouponEnum::$STATUS);
        $id = intval($_GET['id']);
        $userId = intval($_GET['user_id']);
        $triggerMode = intval($_GET['trigger_mode']);
        $dealLoadId = intval($_GET['deal_load_id']);
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $giftCode = trim($_GET['gift_code']);
        $groupId = intval($_GET['group_id']);
        $mobile =  intval($_GET['mobile']);
        $userIdNo = trim($_GET['userIdNo']);
        if ($userIdNo) {
            $userId = de32Tonum($userIdNo);
        }

        if ($mobile) {
            $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id');
            $userId = $userInfo['id'];
        }

        if ($id) {
            $where .= " AND id = " . $id;
        }
        if ($userId) {
            $where .= " AND user_id = " . $userId;
        }

        if (array_key_exists($triggerMode, CouponGroupEnum::$TRIGGER_MODE_FOR_ADMIN)) {
            $where .= " AND trigger_mode = $triggerMode";
        } else {
            $where .= " AND trigger_mode in (".implode(',', array_keys(CouponGroupEnum::$TRIGGER_MODE_FOR_ADMIN)).")";
        }

        if ($dealLoadId) {
            $where .= " AND deal_load_id = $dealLoadId";
        }

        if ($timeStart) {
            $where .= " AND create_time >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND create_time <= '". strtotime($timeEnd) ."'";
        }

        if ($giftCode) {
            $where .= " AND gift_code = '". $giftCode ."'";
        }

        if ($groupId) {
            $where .= " AND gift_group_id = $groupId";
        }

        $_REQUEST ['listRows'] = 10;
        $this->_list($this->model, $where);
        $result = $this->get('list');
        if (empty($result)) {
            $this->display();
            return false;
        }

        $dataList = array();
        $userCache = array();
        $o2oService = new O2OService();
        $couponService = new CouponService();
        foreach ($result as $key => $item) {
            if (!self::$userCache[$item['user_id']]) {
                $userInfo = \core\dao\UserModel::instance()->find($item['user_id'], 'id,user_name,real_name,mobile, refer_user_id, invite_code, group_id, site_id', true);
                self::$userCache[$item['user_id']] = $userInfo;
            } else {
                $userInfo = self::$userCache[$item['user_id']];
            }

            $data = $item;
            $extra = json_decode($item['extra_info'], true);
            $data['deal_type'] = isset($extra['consume_type']) ? $extra['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
            $data['deal_type_desc'] = isset(CouponGroupEnum::$CONSUME_TYPES[$data['deal_type']])
                ? CouponGroupEnum::$CONSUME_TYPES[$data['deal_type']] : '';

            $data['user_tooltips'] = $this->getUserToolTips($item['user_id']);
            $data['real_name'] = userNameFormat($userInfo['real_name']);
            $data['user_name'] = $userInfo['user_name'];
            $data['mobile'] = adminMobileFormat($userInfo['mobile']);
            $data['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $data['group_status'] = '点击查看';

            if ($item['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_EMPTY) {
                $data['group_status'] = '无领奖机会';
            } else if ($item['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_SUC && $item['expire_time'] < time()) {
                $data['group_status'] = '已过期';
            } else if ($item['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_INIT) {
                $data['group_status'] = '待触发';
            }

            if ($item['gift_id'] > 0) {
                $data['group_status'] = '已领奖';
            }

            $data['expire_time'] = $item['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_SUC ? date('Y-m-d H:i:s', $item['expire_time']) : '无领奖机会';
            $data['confirm'] = 1;

            // 勋章单独处理
            if ($item['trigger_mode'] == CouponGroupEnum::TRIGGER_MEDAL) {
                $data['group_status'] = '已领奖';
                $data['expire_time'] = '已领奖';
            }

            $referUserId = 0;
            $short_alias = '';
            $coupon_bind_service = new CouponBindService();
            $coupon_bind = $coupon_bind_service->getByUserId($item['user_id']);
            if (!empty($coupon_bind)) {
                $short_alias = $coupon_bind['short_alias'];
                if (!empty($short_alias)) {
                    $referUserId = $couponService->getReferUserId($short_alias);
                }
            }
            $data['invite_code'] = $short_alias;
            $data['refer_user_id'] = $referUserId;
            $data['deal_detail'] = '';
            if (in_array($item['trigger_mode'], CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
                if ($data['deal_type'] == CouponGroupEnum::CONSUME_TYPE_P2P) {
                    //专享和p2p显示投资时带的码
                    //每日首次充值和充值的不用查投资详情
                    $dealLoadInfo = DealLoadModel::instance()->findViaSlave($item['deal_load_id']);
                    if ($dealLoadInfo) {
                        $data['invite_code'] = $dealLoadInfo['short_alias'];
                        $data['refer_user_id'] = $couponService->getReferUserId($dealLoadInfo['short_alias']);
                        $data['deal_detail'] = $this->getDealDetail($dealLoadInfo);
                    } else {
                        //普惠拆分后交易信息需要单独取
                        $phDealLoadInfo = $this->getPhDealInfo($item['deal_load_id']);
                        $data['deal_detail'] = $this->getPhDealDetail($phDealLoadInfo);
                        $data['invite_code'] = $phDealLoadInfo['short_alias'];
                        $data['refer_user_id'] = $couponService->getReferUserId($phDealLoadInfo['short_alias']);
                    }
                } else if ($data['deal_type'] == CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG) {
                    $dealLoadInfo = DealLoadModel::instance()->findViaSlave($item['deal_load_id']);
                    $data['invite_code'] = $dealLoadInfo['short_alias'];
                    $data['refer_user_id'] = $couponService->getReferUserId($dealLoadInfo['short_alias']);
                    $data['deal_detail'] = $this->getDealDetail($dealLoadInfo);
                } else if ($data['deal_type'] == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
                    //数据异常的请求智多新获取准确数据
                    if ($extra['dealBidDays'] || $extra['deal_money']) {
                        $dtLoadInfo = $this->getDtLoadInfo($item['deal_load_id']);
                        $extra['deal_money'] = $dtLoadInfo['money'];
                        $extra['deal_annual_amount'] = round($dtLoadInfo['money'] * $dtLoadInfo['lockPeriod'] / 360, 2);
                        $extra['dealBidDays'] = $dtLoadInfo['lockPeriod'];
                    }
                    $data['deal_detail'] = '投资智多鑫<br/>投资金额：'.$extra['deal_money'].'<br/>投资年化：'
                        .$extra['deal_annual_amount'].'<br/>锁定期限：'.$extra['dealBidDays'].'天';
                } else if ($data['deal_type'] == CouponGroupEnum::CONSUME_TYPE_GOLD) {
                    $data['deal_detail'] = '购买优长金<br/>交易名称 : '.$extra['dealName'].'<br/>交易期限 : '.$extra['dealRepayTime'].'天'
                        .'<br/>购买金价 : '.$extra['buyPrice'].'元/克'.'<br/>购买克数 : '.$extra['deal_money'].'克'.'<br>支付金额 : '.$extra['goldMoney'].'元';
                } else if ($data['deal_type'] == CouponGroupEnum::CONSUME_TYPE_GOLD_CURRENT) {
                    $data['deal_detail'] = '购买优金宝<br/>购买金价 : '.$extra['buyPrice'].'元/克'.'<br/>购买克数 : '.$extra['deal_money'].'克'
                        .'<br/>支付金额 : '.$extra['deal_money'].'元';
                }
            }

            // 已经领取了礼券的情况
            if ($item['gift_id'] > 0) {
                $couponInfo = $this->getCouponInfo($item['gift_id']);
                if ($couponInfo['coupon']['status'] == CouponEnum::STATUS_USED) {
                    $confirmLog = OtoConfirmLogModel::instance()->getConfirmLogByGiftId($item['gift_id']);
                    if (empty($confirmLog)) {
                        $data['confirm'] = 0;
                    }
                }
                $data['coupon_detail'] = "已领取券组编号:{$item['gift_group_id']}<br>券组价格:{$couponInfo['couponGroup']['goodPrice']}<br>
                    券码领取时间:". date("Y-m-d H:i:s", $couponInfo['coupon']['createTime']) . "<br>券码失效时间:". date('Y-m-d H:i:s', $couponInfo['coupon']['useEndTime']) . "<br>
                    券码状态:".CouponEnum::$STATUS[$couponInfo['coupon']['status']]."<br>实际展现券码:{$couponInfo['coupon']['couponNumber']}";
                $data['coupon_name'] = $couponInfo['product']['productName'];

                $data['transfer_detail'] = '';
                if ($couponInfo['coupon']['supAllowanceStore'] > 0 && $couponInfo['couponGroup']['supplierUserId'] > 0 && $couponInfo['couponGroup']['storeId'] > 0) {
                    $data['transfer_detail'] .= '供应商补贴零售店: '.$couponInfo['coupon']['supAllowanceStore'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['supAllowanceStoreType']).'<br/>';
                }

                if ($couponInfo['coupon']['channelAllowanceStore'] > 0 && $couponInfo['couponGroup']['channelId'] > 0 && $couponInfo['couponGroup']['storeId'] > 0) {
                    $data['transfer_detail'] .= '渠道补贴零售店 '.$couponInfo['coupon']['channelAllowanceStore'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['channelAllowanceStoreType']).'<br/>';
                }

                if ($couponInfo['coupon']['wxAllowanceStore'] > 0 && $couponInfo['couponGroup']['wxUserId'] > 0 && $couponInfo['couponGroup']['storeId'] > 0) {
                    $data['transfer_detail'] .= '网信补贴零售店: '.$couponInfo['coupon']['wxAllowanceStore'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['wxAllowanceStoreType']).'<br/>';
                }

                if ($couponInfo['coupon']['wxAllowanceSup'] > 0 && $couponInfo['couponGroup']['wxUserId'] > 0 && $couponInfo['couponGroup']['supplierUserId'] > 0) {
                    $data['transfer_detail'] .= '网信补贴供应商: '.$couponInfo['coupon']['wxAllowanceSup'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['wxAllowanceSupType']).'<br/>';
                }

                if ($couponInfo['coupon']['wxAllowanceChannel'] > 0 && $couponInfo['couponGroup']['wxUserId'] > 0 && $couponInfo['couponGroup']['channelId'] > 0) {
                    $data['transfer_detail'] .= '网信补贴渠道: '.$couponInfo['coupon']['wxAllowanceChannel'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['wxAllowanceChannelType']).'<br/>';
                }

                if ($couponInfo['coupon']['wxAllowanceInviter'] > 0 && $couponInfo['couponGroup']['wxUserId'] > 0 && $data['refer_user_id'] > 0) {
                    $data['transfer_detail'] .= '网信补贴邀请人: '.$couponInfo['coupon']['wxAllowanceInviter'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['wxAllowanceInviterType']).'<br/>';
                }

                if ($couponInfo['coupon']['luckyMoneyAllowanceMoney'] > 0 && $couponInfo['couponGroup']['wxUserId'] > 0) {
                    $data['transfer_detail'] .= '兑券后网信补贴投资人: '.$couponInfo['coupon']['luckyMoneyAllowanceMoney'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['luckyMoneyAllowanceType']).'<br/>';
                }

                if ($couponInfo['coupon']['acquiredWxOwnerAllowanceMoney'] > 0 && $couponInfo['couponGroup']['wxUserId'] > 0) {
                    $data['transfer_detail'] .= '领券后网信补贴投资人: '.$couponInfo['coupon']['acquiredWxOwnerAllowanceMoney'].', 补贴类型: '.$this->getAllowanceTypeInfo($couponInfo['coupon']['acquiredWxOwnerAllowanceType']);
                }

                if ($data['transfer_detail'] != '') {
                    $data['transfer_detail'] = '网信ID:'.$couponInfo['couponGroup']['wxUserId'] . '<br>供应商ID:' . $couponInfo['couponGroup']['supplierUserId'] . '<br>渠道ID:' . $couponInfo['couponGroup']['channelId'] . '<br>' . $data['transfer_detail'];
                }
            }

            $data['refer_user_tooltips']= $this->getUserToolTips($data['refer_user_id']);
            $data['userIdNo'] = numTo32($data['user_id']);
            $dataList[] = $data;
        }

        $this->assign('list', $dataList);
        $this->display();
    }

    public function reConfirm() {

        $ajax = 1;
        $giftId = intval($_REQUEST['gift_id']);
        $storeId = intval($_REQUEST['store_id']);
        if (!$giftId) {
            $this->error('id不能为空', $ajax);
        }
        $o2oService = new O2OService();
        $res = $o2oService->p2pConfirmCoupon($giftId, $storeId);
        if (!$res) {
            $this->error('重兑失败', $ajax);
        }

        $this->success('重兑成功', $ajax);
    }

    private function getDealDetail($dealLoadInfo) {

        if (!isset(self::$dealInfos[$dealLoadInfo['deal_id']])) {
            $dealInfo = DealModel::instance()->findViaSlave($dealLoadInfo['deal_id']);
            self::$dealInfos[$dealLoadInfo['deal_id']] = $dealInfo;
        }
        $dealInfo = self::$dealInfos[$dealLoadInfo['deal_id']];

        $dealDetail = '投资站点:'. \libs\utils\Site::getTitleById($dealLoadInfo['site_id']).'('.$dealLoadInfo['site_id'].')<br>投资类型:';
        if ($dealInfo['deal_type'] == O2OService::DEAL_TYPE_COMPOUND) {
            $apply = CompoundRedemptionApplyModel::instance()->getApplyByDealLoanId($dealLoadInfo['id']);
            if (!$apply) {
                return false;
            }

            $dealInfo['repay_time'] = ($apply['repay_time'] - $dealInfo['repay_start_time']) /86400;
            $dealDetail .= '通知贷<br>申请赎回时间:' . to_date($apply['create_time']) . '<br>';
        } else {
            $dealDetail .= '普通标<br>';
        }

        $dealDetail .= '投资期限:' . $dealInfo['repay_time'];
        if ($dealInfo['loantype'] == O2OService::LOAN_TYPE_5) {
            $divideRate = $dealInfo['repay_time'] / 360;//360天,金融领域年周期为360天
            $dealDetail .= '天<br>';
        } else {
            $divideRate = $dealInfo['repay_time'] / 12;//12月
            $dealDetail .= '月<br>';
        }

        if ($dealInfo) {
            $rebateRate = $dealInfo->getRebateRate($dealInfo->loantype);
            $annualizedAmount = round($dealLoadInfo['money'] * $divideRate * $rebateRate, 2);//不乘利率
        } else {
            $annualizedAmount = round($dealLoadInfo['money'] * $divideRate, 2);//不乘利率
        }

        $sourceTypeMap = array(
            0 => 'Web',
            3 => 'iOS',
            4 => 'Android',
            8 => 'WAP'
        );
        $sourceType = isset($sourceTypeMap[$dealLoadInfo['source_type']]) ? $sourceTypeMap[$dealLoadInfo['source_type']] : '其他';
        $dealDetail .= '投资金额:' . $dealLoadInfo['money'] . '<br>投资年化:' . $annualizedAmount . '<br>借款编号:' . $dealInfo['id'] . '<br>借款标题:' . $dealInfo['name']
                       . '<br>投资来源:' . $sourceType;
        return $dealDetail;
    }

    private function getPhDealDetail($dealLoadInfo) {
        $dealDetail = '投资站点:'. \libs\utils\Site::getTitleById($dealLoadInfo['site_id']).'('.$dealLoadInfo['site_id'].')<br>投资类型:';
        $dealDetail .= '普通标<br>';
        $dealDetail .= '投资期限:' . $dealLoadInfo['repay_time'];
        if ($dealLoadInfo['loantype'] == O2OService::LOAN_TYPE_5) {
            $dealDetail .= '天<br>';
        } else {
            $dealDetail .= '月<br>';
        }
        $annualizedAmount = $dealLoadInfo['annualizedAmount'];
        $sourceTypeMap = array(
            0 => 'Web',
            3 => 'iOS',
            4 => 'Android',
            8 => 'WAP'
        );
        $sourceType = isset($sourceTypeMap[$dealLoadInfo['source_type']]) ? $sourceTypeMap[$dealLoadInfo['source_type']] : '其他';
        $dealDetail .= '投资金额:' . $dealLoadInfo['money'] . '<br>投资年化:' . $annualizedAmount . '<br>借款编号:' . $dealLoadInfo['deal_id'] . '<br>借款标题:' . $dealLoadInfo['name']
                       . '<br>投资来源:' . $sourceType;
        return $dealDetail;
    }

    private function getUserToolTips($userId) {
        if (!$userId) {
            return '';
        }

        if (isset(self::$userToolTips[$userId])) {
            return self::$userToolTips[$userId];
        }

        if (isset(self::$userCache[$userId])) {
            $userInfo = self::$userCache[$userId];
        } else {
            $userInfo = \core\dao\UserModel::instance()->find($userId, 'id,user_name,real_name,mobile, refer_user_id, invite_code, group_id, site_id', true);
            self::$userCache[$userId] = $userInfo;
        }

        $userTagService = new UserTagService();
        $remoteTagService = new RemoteTagService();
        $localTags = $userTagService->getTags($userId);
        $userToolTips = '所属会员组ID:'.$userInfo['group_id'].'<br>';
        $userToolTips .= '首次登录的分站ID:'.$userInfo['site_id'].'<br>';
        if (!empty($localTags)) {
            $localTagStr = '';
            foreach($localTags as $tag) {
                if (strpos($tag['const_name'], 'O2O') === false) {
                    continue;
                }
                $localTagStr .= $tag['const_name'] . '|';
            }
        }

        if ($localTagStr != '') {
            $userToolTips .= 'Tag(旧)<br>' . trim($localTagStr, '|') . '<br>';
        }

        $remoteTags = $remoteTagService->getUserAllTag($userId);
        if (!empty($remoteTags)) {
            $remoteTagStr = '';
            foreach ($remoteTags as $key => $tags) {
                if (strpos($key, 'O2O') === false) {
                    continue;
                }
                if (is_array($tags)) {
                    $remoteTagStr .= $key. ':' .implode(',', $tags) . '|';
                } else {
                    $remoteTagStr .= $key. ':' .$tags . '|';
                }
            }
        }
        if ($remoteTagStr != '') {
            $userToolTips .= 'Tag(新)<br>' . trim($remoteTagStr, '|');
        }
        self::$userToolTips[$userId] = $userToolTips;
        return $userToolTips;
    }

    public function getCouponGroupList() {
        $action = intval($_REQUEST['action']);
        $userId = intval($_REQUEST['userId']);
        $dealLoadId = trim($_REQUEST['dealLoadId']);
        $dealType = isset($_REQUEST['dealType']) ? $_REQUEST['dealType'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $o2oService = new O2OService();
        $groupList = $o2oService->getCouponGroupList($userId, $action, $dealLoadId, $dealType);
        echo json_encode($groupList, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getCouponInfo($couponId) {
        $o2oService = new O2OService();
        try {
            $request = new RequestGetCouponInfo();
            $request->setCouponId(intval($couponId));
            $response = $o2oService->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getCouponInfo', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return array();
        }

        if (!is_array($response)) {
            return array();
        }

        return $response;
    }
    /**
     * 获取补贴类型描述
     */
    protected function getAllowanceTypeInfo($type) {
        $allowanceTypes = CouponGroupEnum::$ALLOWANCE_TYPE;
        return isset($allowanceTypes[$type]) ? $allowanceTypes[$type] : '';
    }

    private function getDtLoadInfo($dealLoadId) {
        try {
            $rpc = new Rpc('duotouRpc');
            $request = new RequestCommon();
            $request->setVars(array('id' => $dealLoadId));
            $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanById',$request);
        } catch (\Exception $e) {
            return array();
        }
        return $response['data'] ?: array();
    }

    private function getPhDealInfo($dealLoadId) {
        $phDealService = new PhDealLoadService();
        $dealLoadInfo = $phDealService->getO2ODealLoadInfo($dealLoadId);
        return $dealLoadInfo;
    }
}
?>
