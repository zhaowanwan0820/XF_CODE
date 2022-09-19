<?php
namespace core\service\user;

use core\service\user\BOBase;
use core\service\user\BOInterface;

class AdminBO extends BOBase implements BOInterface
{	
	public function doLogin($jumpUrl, $userInfo) {
		
	}
	
	public function doLogout() {
		
	}
	
	public function updateInfo($userInfo) {
		return true;
	}
}