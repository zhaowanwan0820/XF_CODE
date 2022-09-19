<?php
//商城的导航
class cache_nav_list_auto_cache extends auto_cache{
	public function load($param)
	{
		$key = $this->build_key(__CLASS__,$param);
		$GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/".__CLASS__."/");
		$nav_list = $GLOBALS['fcache']->get($key);
		if($nav_list === false)
		{
			$nav_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."nav where is_effect = 1 AND pid=0 order by sort desc");
			$nav_list = format_nav_list($nav_list);
			foreach($nav_list as $k=>$v){
				$sub_nav = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."nav where is_effect = 1 AND pid=".$v['id']." order by sort desc");
				$nav_list[$k]['sub_nav'] = format_nav_list($sub_nav);
			}
			$GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/".__CLASS__."/");
			$GLOBALS['fcache']->set($key,$nav_list);
		}
		return $nav_list;
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