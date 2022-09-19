<?php
/**
 * 黄金项目service
 * @data 2017.06.27
 * @author wangzhen wangzhen@ucfgroup.com
 */


namespace core\service;
use libs\utils\Logger;
use core\service\CouponService;
use core\service\GoldService;
use core\service\UserCarryService;
use core\service\UserService;
use core\service\SendContractService;
use core\service\TransferService;
use core\service\P2pDealBidService;
use core\service\SupervisionFinanceService;
use core\service\DiscountService;
use core\service\oto\O2ODiscountService;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use core\data\GoldDealData;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use core\tmevent\gold\BidEvent;
use core\tmevent\gold\UserEvent;
use core\tmevent\gold\BonusEvent;
use core\tmevent\bid\BonusGoldConsumeEvent;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class GoldBidBaseService extends GoldService {

    protected $dealInfo;
    protected $userInfo;
    protected $moneyInfo;
    protected $buyAmount;
    protected $buyPrice;
    protected $coupon;
    protected $price_rate;
    protected $orderId;
    protected $discountId;            // 黄金券id
    protected $discountGroupId;       // 黄金券券组id
    protected $discountSign;          // 黄金券验签
    protected $discountSuccessDesc = ''; // 黄金券使用成功后的文案

    public static $fatal;

    public function __construct() {
        $this->price_rate = app_conf('GOLD_PRICE_RATE')?app_conf('GOLD_PRICE_RATE'):0.5;//浮动利率
    }

    public function doBid(){
    }

    public function checkCanBid(){
    }

    /**
    * 验证参数
    */
    protected function checkFirst(){
        if(empty($this->userId)){
            throw new \Exception('用户id参数不能为0');
        }
        if(empty($this->dealId)){
            throw new \Exception('标id不能为0');
        }
        if(bccomp($this->buyAmount,0,4) <= 0){
            throw new \Exception('投资额不能为0');
        }
        if(bccomp($this->buyPrice,0,4) <= 0){
            throw new \Exception('交易暂停中，请稍后再试');
        }
    }

    /**
     * 价格变动率
     */
    protected function checkvariablePriceRate(){
        $response= $this->getGoldPrice(true);
        if ($response['errCode'] != '0' || empty($response['data']['gold_price'])) {
            throw new \Exception('当前非交易时段');
        }
        $this->curent_price = $response['data']['gold_price'];
        if(bccomp(bcadd($this->curent_price,$this->price_rate,2),$this->buyPrice,2) <0 || bccomp(bcsub($this->curent_price,$this->price_rate,2),$this->buyPrice,2) >0 ){
            throw new \Exception('黄金价格超过波动范围，购买失败');
        }
    }

    /**
     * 验证其他信息
     */
    protected function checkEnd(){
        if($this->dealInfo['userId'] == $this->userInfo['id']){
            throw new \Exception($GLOBALS['lang']['CANT_BID_BY_YOURSELF']);
        }
    }

    protected function lock(){
        $dealData = new GoldDealData();
        $lock = $dealData->enterPool($this->dealId);
        if ($lock === false) {
            throw new \Exception('购买人数过多，请稍后再试');
        }
    }

    /**
     * 检查抵扣券是否可用
     */
    protected function checkDiscount() {
        $discountId      = $this->discountId;
        $discountGroupId = $this->discountGroupId;
        $discountSign    = $this->discountSign;
        $dealId          = $this->dealId;
        $userId          = $this->userId;
        $money           = $this->buyAmount;
        if ($discountId > 0) {
            $discountService = new DiscountService();
            if ($discountService->checkConsume($discountId)) {
                throw new \Exception("此优惠券已经使用");
            }
            $params = array('user_id'=> $userId, 'deal_id'=> $dealId, 'discount_id' => $discountId, 'discount_group_id' => $discountGroupId);
            $signStr = $discountService->getSignature($params);
            if ($discountSign != $signStr) {
                \libs\utils\Monitor::add(DiscountService::SIGN_FAILD);
                throw new \Exception("签名错误");
            }

            // 检查优惠券的其他的使用规则
            $o2oDiscountService = new O2ODiscountService();
            $errors = array();
            $checkResult = $o2oDiscountService->checkDiscountUseRules($discountGroupId, $dealId, $money, $errors);
            if (!$checkResult) {
                \libs\utils\Monitor::add(DiscountService::USE_ERR);
                throw new \Exception("使用优惠券错误");
            }
        }
    }

    protected function releaseLock(){
        // 投资成功，此时可以释放资源
        $dealData = new GoldDealData();
        $dealData->leavePool($this->dealId);
    }

    public function errCatch($dealId){
        $fatal = self::$fatal;
        if(!empty($dealId) && !empty($fatal)){
            $dealData = new GoldDealData();
            $dealData->leavePool($dealId);
            $lastErr = error_get_last();
            Logger::info("bid err catch" ." lastErr: ". json_encode($lastErr) . " trace: ".json_encode(debug_backtrace()));
        }
    }

    /**
     * 获取用户账户余额，如果大账户余额不够，需要从存管账转钱过来
     */
    protected function getMoneyInfo(){

        $sfService = new SupervisionFinanceService();
        $isNeedTip = $sfService->isPromptTransfer($this->userInfo['id']);

        if(!$isNeedTip){
            $p2pDealBidService = new P2pDealBidService();
            $transferMoney = $p2pDealBidService->needTransferMoney($this->userInfo,(new DealModel()),$this->money);
            // 3、资金划转
            if($transferMoney && bccomp($transferMoney,'0.00',2) ==1){
                $transferOrderId = Idworker::instance()->getId();
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP," dealId:{$this->dealId},userId:{$this->userId} 开始资金划转 金额：{$transferMoney}")));
                $transferRes = $p2pDealBidService->moneyTransfer($transferOrderId,$this->userInfo['id'],$transferMoney,false);
                if(!$transferRes){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $this->dealId, $this->userId, $this->money, $this->coupon, "资金划转失败 , transFerOrderId:".$transferOrderId)));
                    throw new \Exception("大账户余额不足，资金划转失败");
                }
                // 此处需要重新获取下用户信息,因为在资金划转后用户资金已经发生变化了
                $this->userInfo = UserModel::instance()->find($this->userId);
            }
        }

        $moneyInfo = (new UserService())->getMoneyInfo($this->userInfo ,$this->money,$this->orderId);
        return $moneyInfo;
    }

    protected function bidBonusTransfer($params)
    {
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $bonusAccountInfo = $params['bonusAccountInfo'];
        //从机构账户扣款
        $transferService = new TransferService();
        $transferService->payerChangeMoneyAsyn = true;

        // 分批转账
        foreach ($bonusAccountInfo as $item) {
            $payerId = $item['rpUserId'];
            $money = $item['rpAmount'];
            $payObj = UserModel::instance()->find($payerId);
            if(empty($payObj)) {
                throw new \Exception("payer用户不存在");
            }
            $payType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payNote = "{$userInfo['id']}使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$dealInfo['name']}";
            $changeDate = strtotime('2017-06-01 00:00:00');
            if (time() >= $changeDate) {
                $receiverType = '使用' . app_conf('NEW_BONUS_TITLE') . '充值';
            } else {
                $receiverType = '充值';
            }
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$dealInfo['name']}";
            $transRes = $transferService->transferById($payerId, $userInfo['id'], $money, $payType,
                    $payNote, $receiverType, $receiverNote, $outOrderId = '');
            if ($transRes === false) {
                throw new \Exception("红包充值失败");
            }
        }
        return true;
    }

    /**
     * 验证邀请码
     */
    protected function checkShortAlias(){

        //如果存在绑定优惠码，必须填绑定的优惠码，防止修改表单 20150303
        $couponService = new CouponService();
        $coupon_latest = $couponService->getCouponLatest($this->userInfo['id']);
        $is_fixed_coupon = !empty($coupon_latest) && $coupon_latest['is_fixed'];
        if ($is_fixed_coupon) {
            $this->coupon = $coupon_latest['short_alias'];
        }elseif($this->coupon){
            $coupon = $couponService->queryCoupon($this->coupon, true);
            if (!empty($coupon)) {
                if (!$coupon['is_effect']) {
                    throw new \Exception("您使用的优惠码不适应此项目，请输入有效的优惠码，谢谢");
                }
            } else {
                throw new \Exception("优惠码有误，请重新输入");
            }
        }
    }

    /**
     * 验证用户账户余额，并尝试划转
     */
    protected function checkMoney(){
        if(empty($this->orderId)){
            throw new \Exception('orderId不存在');
        }
        $this->fee = floorfix(bcmul($this->dealInfo['buyerFee'],$this->buyAmount,4),2);
        $this->money= floorfix(bcmul(bcadd($this->curent_price,$this->dealInfo['buyerFee'],4),$this->buyAmount,4),2);

        $this->moneyInfo = $this->getMoneyInfo();
        $totalCanBidMoney = bcadd($this->moneyInfo['lc'],$this->moneyInfo['bonus'],2);
        if((bccomp($this->money,$totalCanBidMoney,2) == 1)){
            throw new \Exception('余额不足，请充值');
        }
    }

    /**
     * 验证用信息
     * @param array $userInfo
     */
    protected function checkUser(){
        $this->userInfo = (new UserModel())->find($this->userId);
        if(empty($this->userInfo)){
            throw new \Exception('用户不存在');
        }
    }

    /**
     * 投资成功回调
     * @param array $params
     */
    public function goldBidSuccessCallback($param){
        $discountService = new DiscountService();
        $consumeType = $param['couponFields']['deal_id'] != CommonEnum::GOLD_CURRENT_DEALID
            ? CouponGroupEnum::CONSUME_TYPE_GOLD
            : CouponGroupEnum::CONSUME_TYPE_GOLD_CURRENT;

        $discountService->consumeEvent(
            $param['consumeUserId'],
            $param['discountId'],
            $param['dealLoadId'],
            $param['dealName'],
            $param['shortAlias'],
            $param['buyPrice'],
            $consumeType,
            $param['discountGoldOrderId']
        );

        if($param['couponFields']['deal_id'] != CommonEnum::GOLD_CURRENT_DEALID){
            $coupon = new CouponService(CouponLogService::MODULE_TYPE_GOLD);
            $coupon_consume_result = $coupon->consume($param['dealLoadId'],$param['shortAlias'],$param['consumeUserId'],$param['couponFields'],CouponService::COUPON_SYNCHRONOUS);
            return $coupon_consume_result;
        }
        return true;
    }
}
