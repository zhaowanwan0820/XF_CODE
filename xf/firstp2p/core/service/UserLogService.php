<?php
/**
 * UserLogService class file.
 *
 * @author pengchanglu@ucfgroup.com
 **/

namespace core\service;

use core\dao\UserLogModel;
use core\dao\BaseModel;
use core\dao\PaymentNoticeModel;
use core\dao\CouponLogModel;
use core\service\DealService;
use core\dao\DealModel;

/**
 * UserLogService
 *
 * @packaged default
 * @author pengchanglu@ucfgroup.com
 **/
class UserLogService extends BaseService
{
    const LOG_INFO_DONG  = 1; // 标签--冻
    const LOG_INFO_ZHI  = 2; // 标签--支
    const LOG_INFO_JIE  = 3; // 标签--解
    const LOG_INFO_SHOU = 4; // 标签--收
    const LOG_INFO_TRANSFER = 5; // 标签--划

    // 收支明细需要哪些log_info
    public $detail_in_log_info=array('充值','还本','付息','逾期罚息','邀请返利','投资返利','转入资金','投标贴息','账户余额贴息','注册返利','投资返利','平台贴息','超额收益');
    public $detail_out_log_info = array('提现成功','投资放款','转出资金',);
    // 投资收益需要用到的log_info
    public $invest_income_log_info=array('付息','逾期罚息','平台贴息','超额收益');
    // 返利收益用到的
    public $referrals_income_log_info=array('邀请返利','注册返利','投资返利');

    // 资金记录筛选类型映射标识
    public static $logInfoMap = array(
        'ALL' => '',
        'PRINCIPAL' => '还本',
        'INTEREST' => '支付收益/利息',
        'EARLYPRINCIPAL' => '提前还款本金',
        'EARLYINTEREST' => '提前还款利息',
        'EARLYSUBSIDY' => '提前还款补偿金',
        'GRANT' => '投资放款',
        'BIDFREEZE' => '投标冻结',
        'CHARGE' => '充值',
        'WITHDRAWAPPLY' => '提现申请',
        'WITHDRAWSUCC' => '提现成功',
        'FUNDAPPLY' => '基金申购',
        'FUNDSUCC' => '基金申购成功',
        'FUNDREDEEM' => '基金到账',
        'PLATFORMINTEREST' => '平台贴息',
        'TRANSFERINCOME' => '转入资金',
        'REPAYPRINCIPALINTEREST' => '偿还本息',
        'DEALSUCC' => '招标成功',
        'WITHDRAWFAIL' => '提现失败',
        'BIDCANCEL' => '取消投标',
        'OVERLAYINTEREST' => '逾期罚息',
        'INVESTPROFIT' => '邀请返利',
        'BIDPROFIT' => '投资返利',
        'COUPONPROFIT' => '返现券返利',
        'COUPONPROFIT2' => '加息券返利',
        'TRANSFERSUCCESS' => '余额划转成功',
        'PREREPAYPROFIT' => '提前还款收益/利息',
        'TRADEGRANT' => '交易放款',
        'TRADEFREEZE' => '交易冻结',
        'MARKETINGPROFIT' => '营销补贴',
        'TRADECANCEL' => '取消交易',
        'INVITEPRIZE' => '邀请奖励',
        'PRIZE' => '奖励',
        'COUPONPPRIZE' => '返现券奖励',
        'COUPONPADD' => '加息券奖励',
        'ZDXMATCH' => '智多鑫-匹配债权',
        'ZDXFREEZE' => '智多鑫-转入本金冻结',
        'ZDXINTEREST' => '智多鑫-支付收益',
        'ZDXTRANSFER' => '智多鑫-转让本金到账',
        'ZDXMANAGEFEE' => '智多鑫-管理费',
        'ZDXBONUS' => '智多鑫-红包充值',
        'ZDXCANCEL' => '智多鑫-投资失败解冻',
        'ZDXRETURNBONUS' => '智多鑫-返还红包余额',
        'ZDXCANCELDEAL' => '智多鑫-流标冻结',
        'BONUSCHARGE' => '使用红包充值',
        'GOLDBUY' => '买金',
        'GOLDFREEZE' => '买金冻结',
        'GOLDTRANSFER' =>  '买金货款划转',
        'GOLDSERVICEFEEFREEZE' => '买金手续费冻结',
        'GOLDSERVICEFEE' => '买金手续费',
        'GOLDRETURN' => '买金流标返还',
        'GOLDRETURNSERVICEFEE' => '买金流标手续费返还',
        'GOLDWITHDRAWSERVICEFEE' => '提金手续费',
        'GOLDWITHDRAWSERVICEFEEFREEZE' => '提金手续费冻结',
        'GOLDWITHDRAWSERVICEFEEUNFREEZE' => '提金手续费解冻',
        'GOLDCASHFEE' => '黄金变现手续费',
        'GOLDCASH' => '黄金变现',
        'GOLDPROFIT' => '黄金收益',
        'CREDITLOANRETURNFREEZE' => '网信速贷还款冻结',
        'CREDITLOANREPAY' => '网信速贷还款',
        'CREDITLOANSERVICEFEE' => '网信速贷平台服务费',
        'CREDITLOANREPAYUNFREEZE' => '网信速贷还款剩余金额解冻',

    );

    // public static $money_log_types = array('还本','支付收益/利息','提前还款本金','提前还款利息','提前还款补偿金','投资放款','投标冻结',
    //         '充值','提现申请','提现成功','基金申购','基金申购成功','基金到账','平台贴息','转入资金','偿还本息','招标成功','提现失败',
    //         '取消投标','逾期罚息','邀请返利','投资返利','返现券返利', '加息券返利','智多新-本金冻结','智多新-匹配债权','智多新-支付利息',
    //         '智多新-本金到账','智多新-管理费','智多新-红包充值','智多新-加入失败解冻','智多新-返还红包余额','智多新-流标冻结','余额划转成功');

    //返利映射为奖励 - 前端展示
    public static $money_log_types = array('还本','支付收益/利息','提前还款本金','提前还款收益/利息','提前还款补偿金','交易放款','交易冻结','营销补贴',
            '充值','提现申请','提现成功','基金申购','基金申购成功','基金到账','平台贴息','转入资金','偿还本息','招标成功','提现失败',
            '取消交易','逾期罚息','邀请奖励','奖励','返现券奖励', '加息券奖励','智多新-本金冻结','智多新-匹配债权','智多新-支付利息',
            '智多新-本金到账','智多新-管理费','智多新-红包充值','智多新-加入失败解冻','智多新-返还红包余额','智多新-流标冻结','余额划转成功', '使用红包充值',
            '买金','买金冻结','买金货款划转','买金手续费冻结','买金手续费','买金流标返还','买金流标手续费返还','提金手续费冻结','提金手续费解冻','提金手续费','黄金变现手续费','黄金变现',
            '黄金收益',
            '网信速贷还款剩余金额解冻','网信速贷还款','网信速贷平台服务费','网信速贷还款冻结'
            );

    /**
     * 资金记录优化
     * @var array
     */
    public static $money_log_types_highest = array(
        '充值'        =>array('充值'),
        '提现'        =>array('提现申请','提现成功','提现失败'),
        '还本'        =>array('还本','提前还款本金','基金到账','智多新-本金到账'),
        '收益/补偿'    =>array('支付收益/利息','提前还款收益/利息','提前还款补偿金','智多新-支付利息','黄金收益','营销补偿'),
        '奖励'        =>array('平台贴息','邀请奖励','奖励','返现券奖励','加息券奖励','智多新-红包充值','使用红包充值'),
        '放款'        =>array('交易放款','基金申购成功'),
        '手续费/罚息'  =>array('逾期罚息','智多新-管理费','买金手续费','买金流标手续费返还','提金手续费','黄金变现手续费'),
        '消费'        =>array('买金','买金流标返还'),
        '冻结/划转'    =>array('交易冻结','基金申购','智多新-本金冻结','智多新-流标冻结','余额划转成功','买金冻结','买金货款划转', '买金手续费冻结','提金手续费冻结','提金手续费解冻'),
        '黄金变现'     =>array('黄金变现'),
        '智多新'      =>array('智多新-本金冻结','智多新-匹配债权','智多新-支付利息','智多新-本金到账','智多新-管理费','智多新-红包充值','智多新-加入失败解冻','智多新-返还红包余额','智多新-流标解冻','智多新-顾问服务费'),
        '速贷'        =>array('网信速贷还款冻结','网信速贷平台服务费','网信速贷还款','网信速贷还款剩余金额解冻'),
        '其它'        =>array('转入资金','偿还本息','招标成功','取消交易'),
    );


    public static $logTypeMap = array(
            '邀请返利' => '邀请奖励',
            '返现券返利' => '返现券奖励',
            '加息券返利' => '加息券奖励',
            '投资返利' => '奖励',
            '投资放款' => '交易放款',
            '投标冻结' => '交易冻结',
            '取消投标' => '取消交易',
            '取消投标' => '取消交易',
            );

    /**
     * 用户 记录
     * @param unknown $limit
     * @param unknown $user_id
     * @param string $t
     * @param bool $pagination 是否分页
     * @param string $log_info 资金记录类型
     * @param integer $start 开始时间戳
     * @param integer $end 结束时间戳
     * @param boolean $withoutSupervision 返回包含存管的交易明细？
     * @param boolean $excludeExtra 返回存管的可以显示的交易明细
     * @return multitype:unknown
     */
    public function get_user_log($limit, $user_id, $t = '', $pagination = false, $log_info = '', $start = 0, $end = 0, $withoutSupervision = false, $excludeExtra = false)
    {

        if (!in_array($t,array("money","score","point","money_only"))) {
            $t = "";
        }

        //时间偏移 导致查询不准
        $log_info = $log_info ?: getRequestString("log_info");
        $start    = $start ?: intval(to_timespan(getRequestString("start"),'Y-m-d'));
        $end      = $end ?: intval(to_timespan(getRequestString("end"),'Y-m-d'));
        // 如果起止时间是同一天,则默认结束时间是当前时间24小时以后
        if ($start == $end && $start != 0) {
            $end += 86400;
        }

        if ( $log_info == '支付收益/利息' ){
            $log_info = '付息';
        }

        //智多鑫老资金记录保留
        if ( $log_info == '智多新-本金到账' ){
            $log_info = '智多鑫-转让本金到账';
        }
        if ( $log_info == '智多新-本金冻结' ){
            $log_info = '智多鑫-转入本金冻结';
        }
        if ( $log_info == '智多新-支付利息' ){
            $log_info = '智多鑫-支付收益';
        }
        if ( $log_info == '智多新-加入失败解冻' ){
            $log_info = '智多鑫-投资失败解冻';
        }
        if ( $log_info == '智多新-管理服务费' ){
            $log_info = '智多鑫-转让服务费';
        }
        if ($log_info == '智多新-匹配债权'){
            $log_info = '智多鑫-匹配债权';
        }
        if ($log_info == '智多新-顾问服务费'){
            $log_info = '智多鑫-顾问服务费';
        }

        //JIRA#5410
        $log_info = ($log_info == '提前还款收益/利息') ? "提前还款利息" : $log_info;

        $logMapFlip = array_flip(self::$logTypeMap);
        if ($log_info != '' && isset($logMapFlip[$log_info])) { //查询映射
            $log_info = $logMapFlip[$log_info];
        }

        //web端需要list和count, api只需要列表，可以减少一次count查询
        if ($pagination === true) {
            $list_count = 'both';
        }else{
            $list_count = 'only_list';
        }

        //排除的loginfo
        $excludedLogInfo = [];
        if (empty($log_info)) {
            // 默认排除资金记录
            $excludedLogInfo = ['余额划转申请', '余额划转失败', '网贷余额划转成功', '赠金充值', '系统赠金', '系统赠金手续费', '智多鑫-转入本金解冻','智多鑫-本金回款并冻结','智多鑫-债权出让','智多鑫-债权出让本金回款并冻结','系统余额修正'];
            // userMoneyLog查询接口
            if ($excludeExtra) {
                $excludedLogInfo = ['余额划转申请', '余额划转失败', '网贷余额划转成功', '赠金充值', '系统赠金', '系统赠金手续费','基金申购', '基金申购成功', '基金到账', '转入资金', '买金', '买金冻结', '买金货款划转', '买金手续费冻结',
                    '买金手续费', '买金流标返还', '买金流标手续费返还', '提金手续费冻结' , '提金手续费解冻', '提金手续费', '黄金变现手续费', '黄金变现','黄金收益','网信速贷还款剩余金额解冻', '网信速贷还款', '网信速贷还款冻结', '网信速贷平台服务费',
                    '系统余额修正',
                ];
            }
        }
        $list =  UserLogModel::instance()->getList($user_id, $t, $log_info, $start, $end, $limit, $list_count, $withoutSupervision, $excludedLogInfo);

        // 北京IDC不查询备份库
        $idcEnvironment = get_cfg_var('idc_environment');
        if ($idcEnvironment != 'BEIJINGZHONGJINIDC') {
            //主库查询不足一页需要查询备份库
            if (count($list['list']) - $limit['1'] < 0) {
                if (!$pagination){
                    $master = UserLogModel::instance()->getList($user_id, $t, $log_info, $start, $end, $limit, 'only_count', $withoutSupervision, $excludedLogInfo);
                    $list['count'] = $master['count'];
                }
                $limit['0'] -= $list['count'];
                if ($limit['0'] < 0) $limit['0'] = 0;
                $limit['1']  -= count($list['list']);
                $backup_list = UserLogModel::instance(['isBackupDb' => true])->getList($user_id, $t, $log_info, $start, $end, $limit, 'only_list', $withoutSupervision, $excludedLogInfo);
                $list['list'] = array_merge($list['list'], $backup_list['list']);
            }

            //web端需要总数分页
            if ($pagination === true) {
                $backup = UserLogModel::instance(['isBackupDb' => true])->getList($user_id, $t, $log_info, $start, $end, $limit, 'only_count', $withoutSupervision, $excludedLogInfo);
                $list['count'] += $backup['count'];
            }
        }

        foreach($list['list'] as &$one){
            if( $one['log_info'] == '付息'){
                $one['log_info'] = in_array($one['deal_type'], [DealModel::DEAL_TYPE_GENERAL, DealModel::DEAL_TYPE_SUPERVISION]) ? '支付利息' : '支付收益';
            }

            if($one['log_info'] == '智多鑫-转让本金到账'){
                $one['log_info'] = '智多新-本金到账';
            }
            if($one['log_info'] == '智多鑫-转入本金冻结'){
                $one['log_info'] = '智多新-本金冻结';
            }
            if($one['log_info'] == '智多鑫-支付收益'){
                $one['log_info'] = '智多新-支付利息';
            }
            if($one['log_info'] == '智多鑫-投资失败解冻'){
                $one['log_info'] = '智多新-加入失败解冻';
            }
            if($one['log_info'] == '智多鑫-转让服务费'){
                $one['log_info'] = '智多新-管理服务费';
            }
            if($one['log_info'] == '智多鑫-匹配债权'){
                $one['log_info'] = '智多新-匹配债权';
            }
            if($one['log_info'] == '智多鑫-顾问服务费'){
                $one['log_info'] = '智多新-顾问服务费';
            }
            if($one['log_info'] =='邀请返利' || $one['log_info'] =='投资返利' ){
                $one['note'] = self :: phone_format( $one['note'] );
            }

            $one['note'] = str_replace('返利', '奖励', $one['note']);
            if ($one['log_info'] == '投资返利') {
                $one['note'] = str_replace('投资', '', $one['note']);
            }

            if (isset(self::$logTypeMap[$one['log_info']])) {
                $one['log_info'] = self::$logTypeMap[$one['log_info']];
            }

            // JIRA#5410
            $isDealZx = (new DealService())->isDealEx($one['deal_type']);
            if( $one['log_info'] == '提前还款利息' && $isDealZx){
                $one['log_info'] = '提前还款收益';
            }

            $one['label'] = $this->getLogInfoLabel($one['money'],$one['lock_money']);
            //划转标签
            if (strpos($one['log_info'], '余额划转成功') !== false) {
                $one['label'] = self::LOG_INFO_TRANSFER;
            }
            switch ($one['label']) {
                case self::LOG_INFO_DONG:
                    $one['showmoney'] = $one['lock_money'];
                    break;
                case self::LOG_INFO_ZHI:
                    $one['showmoney'] = (bccomp($one['money'],0.00,2) == 0) ? $one['lock_money'] : $one['money'];
                    break;
                case self::LOG_INFO_JIE:
                    $one['showmoney'] = $one['money'];
                    break;
                case self::LOG_INFO_SHOU:
                    $one['showmoney'] = (bccomp($one['money'],0.00,2) == 0) ? $one['lock_money'] : $one['money'];
                    break;
                case self::LOG_INFO_TRANSFER:
                    $one['showmoney'] = bcadd(abs($one['money']), abs($one['lock_money']), 2);
                    break;
                default:
                    $one['showmoney'] = (bccomp($one['money'],0.00,2) == 0) ? $one['lock_money'] : $one['money'];
                    break;
            }
            $one['showmoney'] = bcadd($one['showmoney'], 0, 2);
            $one['note'] = str_replace('网信理财', '网信', $one['note']);
        }
        return $list;
    }

    public function getLogInfoLabel($money,$lockMoney) {
        if(bccomp($money,0.00,2) == -1 && bccomp($lockMoney,0.00,2) == 1) {
            return self::LOG_INFO_DONG;
        }
        elseif(bccomp($money,0.00,2) == 1 && bccomp($lockMoney,0.00,2) == -1) {
            return self::LOG_INFO_JIE;
        }
        elseif(bccomp($money,0.00,2) == 0 && bccomp($lockMoney,0.00,2) == 1) {
            return self::LOG_INFO_SHOU;
        }
        elseif(bccomp($money,0.00,2) == 1 && bccomp($lockMoney,0.00,2) == 0) {
            return self::LOG_INFO_SHOU;
        }
        elseif(bccomp($money,0.00,2) == 0 && bccomp($lockMoney,0.00,2) == -1){
            return self::LOG_INFO_ZHI;
        }
        elseif(bccomp($money,0.00,2) == -1 && bccomp($lockMoney,0.00,2) == 0){
            return self::LOG_INFO_ZHI;
        }
        return 0;
    }

    /**
     * 获取用户资金记录
     * @param unknown $where
     * @param unknown $limit
     */
    public function getUserfundMoneyLog($where,$limit) {
        return UserLogModel::instance()->getUserfundMoneyLog($where,$limit);
    }

    public function get_charge_list($user_id,$offset=0,$ps=100) {
        return PaymentNoticeModel::instance()->getRecentList($user_id,$offset,$ps);
    }

    /**
     * 查找上个月的大概情况  (收支明细/投资收益/返利收益)
     * @param int $user_id
     * @author zhanglei5@ucfgroup.com
     */
    public function getLastMonthAll($user_id, $month = null){
        if ($month) {
            $arr_time = getMonthStartEnd($month . '01');
        } else {
            $arr_time = getTimeStartEnd('last_month'); // var_dump($arr_time);
        }
        $start = $arr_time['start'];
        $end = $arr_time['end'];

        // 上个月的投资收益
        $income = UserLogModel::instance()->getSumMeneyByUserIdLogInfo($user_id,$this->invest_income_log_info,$start,$end);
        //备份库
        $income_bak = UserLogModel::instance(['isBackupDb' => true])->getSumMeneyByUserIdLogInfo($user_id,$this->invest_income_log_info,$start,$end);
        $income = empty($income) ? 0 : $income;
        if(!empty($income_bak)) $income += $income_bak;
        $data['income'] = empty($income) ? 0 : $income;
        // 上个月的返利收益
        //$referrals = UserLogModel::instance()->getSumMeneyByUserIdLogInfo($user_id,$this->referrals_income_log_info,$start,$end);
        $couponLogModel = new CouponLogModel();
        $referrals = $couponLogModel->getLogPaidHavedPeriodOfTime($user_id, $start, $end);
        $data['referrals'] = empty($referrals) ? 0 : $referrals;

        // 上个月的收支明细
        $log_info = array_merge($this->detail_in_log_info,$this->detail_out_log_info);
        $detail = UserLogModel::instance()->getDetailByUserIdLogInfo($user_id,$log_info,$start,$end,5);
        $detail_bak = UserLogModel::instance(['isBackupDb' => true])->getDetailByUserIdLogInfo($user_id,$log_info,$start,$end,5);
        if(!empty($detail_bak)) $detail += $detail_bak;
        $arr_dt = array();
        $in_log_info = array_flip($this->detail_in_log_info);
        foreach($detail as $key  => $val) {
            if($val['log_info'] == '充值') {
                if($val['money'] > 0) {
                    $val['is_earning'] = 1;
                }else {
                    $val['lock_money'] = $val['money'];
                    $val['is_earning'] = -1;
                }
            }else {
                if(isset($in_log_info[$val['log_info']])) { // 属于收入的
                    $val['is_earning'] = 1;
                }else {     // 属于支出的
                    $val['is_earning'] = -1;
                }
            }
            $arr_dt[] = $val;
        }
        $data['detail'] = $arr_dt;
        return $data;
    }

    /**
     * 获取该用户的返利收益 (注册返利|邀请返利)
     * @param unknown $user_id
     * @return float $referrals
     */
    public function getReferrals($user_id) {
        $referrals = UserLogModel::instance()->getSumMeneyByUserIdLogInfo($user_id,$this->referrals_income_log_info);
        //备份库
        $referrals_backup = UserLogModel::instance(['isBackupDb' => true])->getSumMeneyByUserIdLogInfo($user_id,$this->referrals_income_log_info);
        $referrals = empty($referrals) ? 0 : $referrals;
        if(!empty($referrals_backup)) $referrals += $referrals_backup;
        return $referrals;
    }
    /**
     * note中手机号码脱敏
     * @param unknown $user_id
     * @return float $referrals
     */
    public static function phone_format($note) {
      return preg_replace_callback ('/(.?)(1[3|4|5|7|8]\d{9})(.?)/',create_function('$matches','return $matches[1].moblieFormat($matches[2]).$matches[3];'),$note);
    }

    /**
     * 获取用户的资金记录
     * @param int $user_id
     * @param int|false $end_time
     * @return false|array
     */
    public function getSummary($user_id, $end_time = false) {
        if (!$user_id) {
            return false;
        }

        $summary = UserLogModel::instance()->getSummaryByLogInfo($user_id, $end_time);
        $summary_bak = UserLogModel::instance(['isBackupDb' => true])->getSummaryByLogInfo($user_id, $end_time);
        $str_ids = UserLogModel::instance(['isBackupDb' => true])->getPrepayDealIds($user_id, $end_time);
        if ($str_ids) {
            $prepay_money = \core\dao\DealLoadModel::instance()->getDealLoadMoneyByDealIds($user_id, $str_ids);
        }

        $result = array();

        foreach ($summary as $v) {
            if ($v['log_info'] == '充值') {
                $result['充值'] += $v['m'];
            } elseif ($v['log_info'] == '提现成功') {
                $result['提现'] += $v['lm'];
            } elseif ($v['log_info'] == '付息' || $v['log_info'] == '收益') {
                $result['付息'] += $v['m'];
            } elseif ($v['log_info'] == '平台贴息') {
                $result['贴息'] += $v['m'];
            } elseif ($v['log_info'] == '投资返利') {
                $result['投资返利'] += $v['m'];
            } elseif ($v['log_info'] == '邀请返利') {
                $result['邀请返利'] += $v['m'];
            } elseif ($v['log_info'] == '转入资金') {
                $result['转入资金'] += $v['m'];
            } elseif ($v['log_info'] == '提前还款利息') {
                $result['提前还款利息'] += $v['m'];
            } elseif ($v['log_info'] == '提前还款补偿金') {
                $result['提前还款补偿金'] += $v['m'];
            } elseif ($v['log_info'] == '逾期罚息') {
                $result['罚息'] += $v['m'];
            }

        }

        foreach ($summary_bak as $v) {
            if ($v['log_info'] == '充值') {
                $result['充值'] += $v['m'];
            } elseif ($v['log_info'] == '提现成功') {
                $result['提现'] += $v['lm'];
            } elseif ($v['log_info'] == '付息' || $v['log_info'] == '收益') {
                $result['付息'] += $v['m'];
            } elseif ($v['log_info'] == '平台贴息') {
                $result['贴息'] += $v['m'];
            } elseif ($v['log_info'] == '投资返利') {
                $result['投资返利'] += $v['m'];
            } elseif ($v['log_info'] == '邀请返利') {
                $result['邀请返利'] += $v['m'];
            } elseif ($v['log_info'] == '转入资金') {
                $result['转入资金'] += $v['m'];
            } elseif ($v['log_info'] == '提前还款利息') {
                $result['提前还款利息'] += $v['m'];
            } elseif ($v['log_info'] == '提前还款补偿金') {
                $result['提前还款补偿金'] += $v['m'];
            } elseif ($v['log_info'] == '提前还款') {
                $result['提前还款利息2'] += ($v['m'] - $prepay_money);
            }
        }

        return $result;
    }

    /**
     * 获取用户范围内的汇总信息
     * @param $uid
     * @param $month
     * @param $year
     */
    public function getUserSummaryByTime($uid,$start_time,$end_time) {

        $oldRes =  UserLogModel::instance(array('isBackupDb'=>true))->getSummaryByTime($uid,$start_time,$end_time);
        $newRes =  UserLogModel::instance(array('isBackupDb'=>false))->getSummaryByTime($uid,$start_time,$end_time);
        
        $res = array();
        foreach($newRes as $k=>$v) {
            if(!isset($res[$v['log_info']])) {
                $res[$v['log_info']] = $v;
            }else {
                $res[$v['log_info']]['m'] =  bcadd($res[$v['log_info']]['m'],$v['m'],2);
                $res[$v['log_info']]['lm'] =  bcadd($res[$v['log_info']]['lm'],$v['lm'],2);
            }
        }
        foreach($oldRes as $k=>$v) {
            if(!isset($res[$v['log_info']])) {
                $res[$v['log_info']] = $v;
            }else {
                $res[$v['log_info']]['m'] =  bcadd($res[$v['log_info']]['m'],$v['m'],2);
                $res[$v['log_info']]['lm'] =  bcadd($res[$v['log_info']]['lm'],$v['lm'],2);
            }
        }
        sort($res);
        return $res;
    }
    public function getTotalSummaryByTime($uid,$start_time,$end_time,$type) {

        $oldRes =  UserLogModel::instance(array('isBackupDb'=>true))->getTotalSummaryByTime($uid,$start_time,$end_time,$type);
        $newRes =  UserLogModel::instance(array('isBackupDb'=>false))->getTotalSummaryByTime($uid,$start_time,$end_time,$type);

        $res = array();
        foreach($newRes as $k=>$v) {
            if(!isset($res[$v['log_info']])) {
                $res[$v['log_info']] = $v;
            }else {
                $res[$v['log_info']]['m'] =  bcadd($res[$v['log_info']]['m'],$v['m'],2);
                $res[$v['log_info']]['lm'] =  bcadd($res[$v['log_info']]['lm'],$v['lm'],2);
            }
        }
        foreach($oldRes as $k=>$v) {
            if(!isset($res[$v['log_info']])) {
                $res[$v['log_info']] = $v;
            }else {
                $res[$v['log_info']]['m'] =  bcadd($res[$v['log_info']]['m'],$v['m'],2);
                $res[$v['log_info']]['lm'] =  bcadd($res[$v['log_info']]['lm'],$v['lm'],2);
            }
        }

        sort($res);
        return $res;
    }

    /**
     * 获取
     * @param $uid
     * @param $end_time
     */
    public function getUserReaminMoney($uid,$end_time) {
        $remainMoney =  UserLogModel::instance(array('isBackupDb'=>false))->getUserReaminMoney($uid,$end_time);

        // 新库不存在记录则从moved库选择
        if($remainMoney === false) {
            $remainMoney =  UserLogModel::instance(array('isBackupDb'=>true))->getUserReaminMoney($uid,$end_time);
        }
        return $remainMoney > 0 ? $remainMoney : 0;
    }

}
// END class UserFeedbackService
