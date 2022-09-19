<?php
/**
 * 此方法为快捷卡工厂，用于获得一个对应通道的类
 */
Yii::import('itzlib.plugins.payment.expresspaytype.*');//引入类
class ExpressPaymentFactory{
    /**
     * 功能：
     * 获得通道
     * 传入变量：
     * $nid=dw_payment表中的nid，比如：Lianlianpay
     */
    public function  getPayment($nid){
    	$nid = strtolower($nid);
        $obj=null;
        switch($nid){
        	
        	case 'lianlianpay':
            case 'lianlian':
            $obj=new LianlianpayClass();
            break;
            
            case 'liandongpay':
            $obj=new LiandongpayClass();
            break;
            
            case 'YEEPAY':
            case 'yeepay':
            $obj=new YeepayClass();
            break;
            
            case 'ebatong':
            $obj=new EbatongClass();
            break;
            
            case 'bill99':
            case 'kuaiqianpay':
            $obj=new KuaiqianpayClass();
            break;
            
            case 'jdpay':
            $obj=new JdpayClass();
            break;
            
            case 'baofoo':
            case 'baofupay':
            $obj=new BaofupayClass();
            break;
        }
        return $obj;
    }
    
    /**
     * 通过itz_expresspayment.id获得通道对象
     */
    public function getPaymentByExpresspaymentId($id){
        $sql='select * from dw_payment
              left join itz_expresspayment  on dw_payment.id = itz_expresspayment.payment_id
              where itz_expresspayment.id=:id
              ';
        $r=Payment::model()->findBySql($sql,array(':id'=>$id));
        #echo $r->nid;die;
        return $this->getPayment($r->nid);
    }
    
}

?>