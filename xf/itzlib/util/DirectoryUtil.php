<?php
/**
 * @file DirectoryUtil.php
 * @author machangqi
 * @date 2013/12/03
 * @version 1.0 
 *  
 **/

class DirectoryUtil {

	public static function mkdirs($dir, $dir_perms=0777){
		/* 循环创建目录 */
		if (DIRECTORY_SEPARATOR!='/') {
			$dir = str_replace('\\','/', $dir);
		}

		if(is_dir($dir)){
			return true;
		}

		if(@mkdir($dir, $dir_perms)){
			return true;
		}

		if(!self::mkdirs(dirname($dir))){
			return false;
		}

		return mkdir($dir, $dir_perms);
	}

}
