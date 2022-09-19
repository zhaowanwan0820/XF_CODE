<?php
/**
 * 抽象类，全部SpeedPayment都继承于此，实现它的方法
 */
abstract class AbstractExpressPaymentClass{
    public $name;
    protected $paymentNid;#lianlianpay
    public $_paymentConfig;//存放从数据库读取的商户信息
    
    /* 这些属性貌似都不应该放进抽象类里
    protected  $retcode;//返回码锁对应的字典
    
    public $name;#lianlianzhifu//?
    public $logo;#hnapay//?
    public $description;#连连支付//?
    public $paymentNid;#lianlianpay//存在payment表中的nid
    public $_paymentConfig;#lianlianpay//存在payment表中的config

    protected $_signType;#MD5
    public $_errorInfo;#MD5
    public $charset;#UTF-8
    
    public $noticeSuccessCode ;// notify 通知成功返回数据
    public $noticeFailCode ;// notify 通知失败返回数据
    
    public $debug = false;//?
    
    protected $_noticeData = array();//保存异步处理获得到的数据
    protected $_returnData = array();//保存同步同步处理得到的数据
    */
    
    //用户绑卡
    /**
     * 入参：
     * $data=array(
     *  'user_id' 
     *  'phone' #银行预留的手机号
     *  'card'  #银行卡号
     * )
     * 返回：
     * $arr=array('code'=>'','msg'=>'');
     * code=1代表成功,0代表失败
     * msg=返回的消息
     */
    abstract public function bindCard($data,$userInfo);
    
    //绑卡确认
    /**
     * 此方法返回一个数组
     * $arr=array('code'=>'','msg'=>'');
     * code=1代表成功,0代表失败
     * msg=返回的消息
     */
    abstract public function bindVerfy($data);
    
    //解绑
    /**
     * 此方法返回一个数组
     * $arr=array('code'=>'','msg'=>'');
     * code=1代表成功,0代表失败
     * msg=返回的消息
     */
    abstract public function bankCardUnbind($data,$userInfo,$safe_card);
    
    //此函数返回用户在连连绑了多少张卡，但是我们的网站只允许用户绑定一张银行卡
    #abstract public function userBankCard($user_id);
    
    //绑卡查寻
    #abstract public function bankCardQuery($card_number);
    
    //获取支付通知结果
    abstract public function noticeResult($data);
    
    //获取支付回调结果
    abstract public function returnResult($data);
    
    //获取支付通知参数
    abstract public function getNoticeData();
    
    //传入请求地址函数，用于向指定url传入数据
    abstract protected function request($path, $data);    
    
    //计算本渠道的费率
    public function computeCost($money){
        /* 充值时，给第三方的手续费，暂时为0
        $nid=$this->getNid();
        echo $nid;die;
        $sql="select a.formula from itz_expresspayment_formula as a
              left join itz_expresspayment on  a.expresspayment_id=itz_expresspayment.id
              left join dw_payment on dw_payment.id = itz_expresspayment.payment_id
              where dw_payment.nid=':nid' and a.status=0 and a.start<:money and a.end>=:money";
        $formulaModel=ItzExpresspaymentFormula::model()->findBySql($sql,array(':nid'=>$nid,':money'=>$money));
        $forumlaStr=$formulaModel->formula;
        var_dump($formulaModel);
        $newStr=str_replace(array('%','x'), array('*0.01',$money), $forumlaStr);
        $newStr='$cost = '.$newStr.';';
        eval($newStr);
        return $cose;
         * 
         */
         return 0;
    }
    
    //返回此对象的Nid
    public function getNid(){
        return $this->paymentNid;
    }
    
    //获取此快捷通道的expresspayment_id
    public function getExpresspaymentId(){
        $sql='
            select itz_expresspayment.id
            from itz_expresspayment 
            inner join dw_payment on dw_payment.id = itz_expresspayment.payment_id
            where dw_payment.nid=:nid
        ';
        $r=ItzExpresspayment::model()->findBySql($sql,array('nid'=>$this->paymentNid));
        return $r->id;
    }
    
}


?>