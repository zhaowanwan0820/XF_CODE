<?php
/**
 * LoanType class file.
 * @author wangyiming@ucfgroup.com
 **/

namespace app\models\service;

use app\models\dao\DealLoanType;

/**
 * 借款类型相关类
 * @author wangyiming@ucfgroup.com
 **/
class LoanType {

	const TYPE_DQZZ = "ZZ";     //短期周转
	const TYPE_GFDK = "GF";     //购房借款
	const TYPE_ZXDK = "ZX";     //装修借款
	const TYPE_GRXF = "GR";     //个人消费
	const TYPE_HLCB = "HL";     //婚礼筹备
	const TYPE_JYPX = "JY";     //教育培训
	const TYPE_QCXF = "QC";     //汽车消费
	const TYPE_TZCY = "CY";     //投资创业
	const TYPE_YLZC = "YL";     //医疗支出
	const TYPE_QTJK = "QT";     //其他借款
	const TYPE_CD = "CD";       //车贷
	const TYPE_FD = "FD";       //房贷
	const TYPE_JYD = "JD";      //经营贷
	const TYPE_GRD = "GD";      //个贷
	const TYPE_ZCZR = "ZC";     //资产转让
	const TYPE_YSD = "YSD";     //应收贷

	/**
	 * 根据订单的借款类型获取借款类型文案
	 * @param $type_id int
	 * @return string
	 */
	public static function getLoanTypeByTypeId($type_id) {
		$deal_loan_type_dao = new DealLoanType();
		$deal_loan_type = $deal_loan_type_dao->findViaSlave($type_id);
		return $deal_loan_type->name;
	}

	/**
	 * 根据订单的借款类型获取借款类型标识
	 * @param $type_id int
	 * @return string
	 */
	public static function getLoanTagByTypeId($type_id) {
		$deal_loan_type_dao = new DealLoanType();
		$deal_loan_type = $deal_loan_type_dao->findViaSlave($type_id);
		return $deal_loan_type->type_tag;
	}

}
