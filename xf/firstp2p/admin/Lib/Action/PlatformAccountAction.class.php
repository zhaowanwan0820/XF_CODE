<?php
/**
 * 平台关联账户管理
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 * @copyright ucf
 *
 */
class PlatformAccountAction extends CommonAction
{
    public function index(){

        $fee_user_id = M('Conf')->where("name='DEAL_CONSULT_FEE_USER_ID' AND site_id=0 AND is_effect=1")->field('value')->find();
        if (empty($fee_user_id['value'])){
            $this->error('平台账户id获取失败');
        }
        $user_id = $fee_user_id['value'];
        $user_info = M("User")->getById($user_id);
        $this->assign("user_info",$user_info);
        $map['user_id'] = $user_id;

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        $model = M ("UserLog");
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ('User:account_detail');
        return;
    }
    /**
     * 导入csv入库处理
     */
    public function import_csv(){

        if(empty($_POST['submit'])){
            $this->display();
            exit;
        }
        if (empty($_FILES['file']['tmp_name'])){
            $this->error('上传的文件不能为空');
        }
        $row = 1;
        $handle = fopen("{$_FILES['file']['tmp_name']}","r");

        $is_error = 0;
        $fileline = file($_FILES['file']['tmp_name']);
        $file_line_num = count($fileline);
        if ($file_line_num > 3000){
            $this->error('处理的数据不能超过3000行');
        }
        while ($data = fgetcsv($handle, 1000, ",")) {
            if (empty($data[2]) || empty($data[6]) || $data[2]=='商户订单号') continue;
            $order_id = !empty($data[2]) ? trim($data[2]) : 0; // 收款订单号
            $fee_recharge = !empty($data[6]) ? floatval('-'.trim($data[6])) : 0; // 手续费
            $result = $this->_processing_fee_recharge($order_id,$fee_recharge);
            // 处理返回值
            switch($result){
                case -1:
                    echo '后台未记录这笔付款订单号：'.$order_id.'<br />';
                    $is_error = 1;
                    continue;
                break;
                case -2:
                    echo '后台显示这个付款订单未支付,收款订单号为：'.$order_id.'<br />';
                    $is_error = 1;
                    continue;
                break;
                case -3:
                    echo '平台账户关联id未找到：'.$order_id.'<br />';
                    $is_error = 1;
                    continue;
                break;
                case -4:
                    echo '处理平台账户手续费参数错误,收款单号为'.$order_id.'<br />';
                    $is_error = 1;
                    continue;
                    break;
                case -5:
                    echo '请不要重复提交,付款单号为'.$order_id.'<br />';
                    $is_error = 1;
                    continue;
                break;
                default: // 1处理成功
                    unset($result);
                break;
            }
        }
        fclose($handle);
        if ($is_error){
            exit;
        }
        $this->success('操作成功');
    }

    /**
     * 处理充值手续费
     */
    private function _processing_fee_recharge($order_id,$fee_recharge){
        if (!is_numeric($order_id) || $fee_recharge=='0.00' || $fee_recharge=='00' || $fee_recharge=='0') return -4;

        // 查询订单信息
        $payment_notice = M('PaymentNotice')->where('notice_sn='.$order_id.' AND payment_id=3')->find();
        if (empty($payment_notice)){
            return -1; // 后台未记录这笔订单
        }
        if ($payment_notice['is_paid'] == 0){
            return -2; // 后台显示这个订单未支付
        }
        if ($payment_notice['is_platform_fee_charged'] == 1){
            return -5; // 已经处理过该订单
        }
        $fee_user_id = M('Conf')->where("name='DEAL_CONSULT_FEE_USER_ID' AND site_id=0 AND is_effect=1")->field('value')->find();
        if (empty($fee_user_id['value'])){
            return -3;
        }
        $user_id = $fee_user_id['value']; // 平台关联账户id
        FP::import('libs.libs.user');
        $msg = '付款单号为'.$order_id.'易宝充值手续费:'.$fee_recharge.'，会员id为：'.$payment_notice['user_id'];
        // TODO finance 后台 易宝充值手续费 | 暂不处理
        modify_account(array('money'=>$fee_recharge,'score'=>0),$user_id,'易宝充值手续费',1,$msg);
        // 处理过的订单需要记录标识
        $data = array();
        $data['is_platform_fee_charged'] = 1;
        $data['fee'] = $fee_recharge;
        $data['update_time'] = get_gmtime();
        M('PaymentNotice')->where('notice_sn='.$order_id.' AND payment_id=3')->save($data);

        return 1;
    }
}
?>
