<?php
namespace core\dao;

/**
 * 用户扩展信息
 *
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/
class UserFieldModel extends BaseModel
{
	public function getFields() { 
		// $sql = sprintf("SELECT field_name,is_must FROM %s", $this->tableName());
		$res = $this->findAll('', true);
		if ($res) {
			return $res;
		}
		return false;
	}
}