<?php
final class PasswordUtils{
	
	private static  $passwordUtils;
	private function __construct(){} 
	
	public static function getInstance(){
		if(!(self::$passwordUtils instanceof self)){
			self::$passwordUtils = new self;
		}
		
		return self::$passwordUtils;
	}
	
	public function addSalt($password){
		
		require_once(WWW_DIR."/itzlib/password_compat/lib/password.php");
		$options = include(WWW_DIR."/itzlib/password_compat/config.php");
		
		return password_hash($password, PASSWORD_BCRYPT, $options);
	}
	
	public function passwordValidate($password,$hash){
		require_once(WWW_DIR."/itzlib/password_compat/lib/password.php");
		return password_verify($password,$hash);
	}
}
