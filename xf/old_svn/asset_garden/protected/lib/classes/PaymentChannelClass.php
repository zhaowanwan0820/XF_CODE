<?php
/**
 * @file PaymentClass.php
 * @author changqi
 * @description: 第三方支付处理类
 */

class PaymentChannelClass {
	
	public function getInfoByNid($nid) {
		$paymentRecord = Payment::model()->findByAttributes(array('nid' => $nid));
		if(empty($paymentRecord)) {
			return NULL;
		}
		$paymentRecord->config = unserialize($paymentRecord->config);
		return $paymentRecord;
	}

}
