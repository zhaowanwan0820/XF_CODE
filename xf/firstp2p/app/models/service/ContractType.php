<?php
/**
 * Discount class file.
 * @author wangyiming@ucfgroup.com
 **/

namespace app\models\service;

use app\models\dao\MsgCategory;

/**
 * 合同类型相关类
 * @author wangyiming@ucfgroup.com
 **/
class ContractType {
	const TYPE_PERSONAL = 0;    //个人贷款
	const TYPE_COMPANY = 1;     //公司贷款

	/**
	 * 根据合同类型id获取借款类型
	 * @param $category_id int
	 * @return int
	 */
	public static function getContractTypeById($category_id) {
		$msg_category_dao = new MsgCategory();
		$msg_category = $msg_category_dao->find($category_id);
		return $msg_category->contract_type;
	}

	/**
	 * 根据合同类型标识获取借款类型
	 * $param $type_tag string
	 * @return int
	 */
	public static function getContractTypeByTag($type_tag) {
		if (!$type_tag) {
			return false;
		}
		$msg_category_dao = new MsgCategory();
		$msg_category = $msg_category_dao->findBy("`type_tag`='{$type_tag}'");
		return $msg_category->contract_type;
	}

}
