<?php
//商城的导航
class deal_loan_type_list_auto_cache extends auto_cache{
	public function load($param)
	{
		$key = $this->build_key(__CLASS__,$param);
		$GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/".__CLASS__."/");
		$loan_type_list = $GLOBALS['fcache']->get($key);
		if($loan_type_list === false)
		{
			$loan_type_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_loan_type where is_effect = 1 order by sort desc");
			$loan_type_list = format_nav_list($loan_type_list);
			$GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/".__CLASS__."/");
			$GLOBALS['fcache']->set($key,$loan_type_list);
		}
		return $loan_type_list;
	}
	public function rm($param)
	{
		$key = $this->build_key(__CLASS__,$param);
		$GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/".__CLASS__."/");
		$GLOBALS['fcache']->rm($key);
	}
	public function clear_all()
	{
		$GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/".__CLASS__."/");
		$GLOBALS['fcache']->clear();
	}
}
?>