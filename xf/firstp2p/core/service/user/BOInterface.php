<?php
namespace core\service\user;

interface BOInterface {
	/**
	 * 更新用户信息， 不同的业务数据整理请在这个方法里完成，然后调用dao层对象进行数据库操作。
	 * @param Array $userData 用户数据
	 * @return bool 更新结果
	 */
	public function updateInfo($userInfo);
	
	/**
	 * 应用登陆逻辑
	 * @param string $jumpUrl 登陆后跳转到的位置
	 * @param array $userInfo 用户oauth返回信息
	 * 
	 */
	public function doLogin($jumpUrl, $userInfo);
	
	/**
	 * 应用登出逻辑
	 */
	public function doLogout();
	
}