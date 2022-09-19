<?php
/**
 * PaymentNotice class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
namespace core\dao\account;
use core\enum\PaymentEnum;

/**
 * PaymentNotice class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class PaymentNoticeModel extends BaseModel {

    public function getListByOrder($order_id) {
        $condition = "order_id=:order_id";
        return $this->findBy($condition, '*', array(":order_id" => $order_id));
    }

    /**
     * getInfoByNoticeSn 
     * 根据notice_sn获取数据记录
     * 
     * @param mixed $notice_sn 
     * @access public
     * @return void
     */
    public function getInfoByNoticeSn($notice_sn) {
        $condition = "notice_sn=':notice_sn'";
        $notice = $this->findBy($condition, '*', array(':notice_sn' => $notice_sn));
        return $notice;
    }

    /**
     * 根据用户UID、订单号获取充值订单数据
     * @param string $notice_sn
     * @return \libs\db\model
     */
    public function getInfoByUserIdNoticeSn($userId, $noticeSn) {
        $condition = "notice_sn=':notice_sn' AND user_id=:user_id";
        $notice = $this->findByViaSlave($condition, '*', array(':notice_sn' => $noticeSn, ':user_id' => $userId));
        return $notice;
    }

    /**
     * 根据id、user_id获取充值订单数据
     * @param int $id
     * @param int $userId
     * @return \libs\db\model
     */
    public function getInfoByIdUserId($id, $userId) {
        $condition = "id=':id' AND user_id=:user_id";
        return $this->findBy($condition, '*', array(':id' => $id, ':user_id' => $userId));
    }

    /**
     * 根据订单ID获取充值订单数据
     * @param int $id
     * @return \libs\db\model
     */
    public function getInfoById($id) {
        return $this->findByViaSlave('id=:id', '*', array(":id" => $id));
    }

    public function getRecentList($user_id,$offset=0,$count=100) {
        $offset = empty($offset)?0:intval($offset);
        $count = empty($count)?100:intval($count);
        $time = strtotime('-7 days');
        $condition = "user_id=:user_id AND create_time >= :time AND is_paid IN (3, 2, 1, 0) order by create_time desc LIMIT :offset,:count";
        $list = $this->findAllViaSlave($condition,true, '*', array(':user_id' => $user_id, ':time' => $time,':offset'=>$offset,':count'=>$count));
        if (is_array($list)) {
            foreach ($list as $k => $item) {
                    $status_cn = '未付款';
                    if ($item['is_paid'] == 2) {
                        $status_cn = $item['amount_limit'] == PaymentEnum::AMOUNT_LIMIT_BIG ? '银行处理中' : '付款中';
                    }
                    else if ($item['is_paid'] == 1) {
                        $status_cn = '付款成功';
                    }else if ($item['is_paid'] == 3){
                        $status_cn = '付款失败';
                    }
                    $list[$k]['status_cn'] = $status_cn;
                    // deal order id
//                     $deal_order = DealOrderModel::instance()->find($item['order_id'], '*', true);
//                     if (!empty($deal_order)) {
//                          $list[$k]['notice_sn'] = $deal_order->order_sn;
//                     }
            }
        }
        return $list;
    }

    /**
     * updateOuterNoticeNo 
     * 更新outer_notice_sn
     * 
     * @param mixed $id 
     * @param mixed $outer_notice_sn 
     * @access public
     * @return void
     */
    public function updateOuterNoticeNo($id, $outer_notice_sn) {
        $notice = $this->find($id);
        if ($notice) {
            $notice->outer_notice_sn = $outer_notice_sn;
            $notice->save();
        }
        return true;
    }

    /**
     * 获取平台累计充值金额
     */
    public function getPlatformPayment($time = 0){
        $cond = "";
        if(intval($time) > 0){
            $cond = " AND pay_time > ".intval($time);
        }
        $sql = "SELECT sum(money) as total FROM firstp2p_payment_notice WHERE is_paid=1".$cond." ;";
        $payment = $this->findBySqlViaSlave($sql);
        if(isset($payment['total']) && ($payment['total'] > 0)){
            return floatval($payment['total']);
        }

        return 0;
    }

    /**
     * 查询用户24小时内是否有【充值】记录
     * @param int $userId 用户ID
     * @param int $createTime 充值时间
     * @return \libs\db\model
     */
    public function hasExistByUserId($userId, $createTime = 0) {
        $condition = (int)$createTime > 0 ? sprintf('AND create_time >=%d', $createTime) : '';
        // 排除线下充值、小额转账认证、基金赎回等充值来源
        $notInPlatform = join(',', [PaymentEnum::PLATFORM_OFFLINE, PaymentEnum::PLATFORM_AUTHCARD, PaymentEnum::PLATFORM_FUND_REDEEM, PaymentEnum::PLATFORM_REFUND]);
        $data = $this->findByViaSlave(sprintf('user_id=\'%d\' %s AND is_paid=%d AND platform NOT IN (%s) LIMIT 1', (int)$userId, $condition, PaymentEnum::IS_PAID_SUCCESS, $notInPlatform), 'id');
        return !empty($data['id']) ? true : false;
    }
}