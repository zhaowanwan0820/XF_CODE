<?php

/**
 * ItzPageUtil 
 * 翻页类
 *
 */
class ItzPageUtil {

	/**
	 * getPageNavigation 
	 * pagination method ,always display 11 page navigation in frontend
	 * 
	 * @param int $axis 分页个数 
	 * @param int $current 当前页
	 * @param int $count 总页数
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getPageNavigation($axis,$current,$count){
//		Yii::log ( __METHOD__ . print_r(func_get_args(),true) ,'debug');
		$ret_arr = "";
		$half_axis_l = intval(($axis-1) /2);
		$half_axis_r = intval($axis /2);
		if(empty($count)||empty($axis)||empty($current)){
			throw new exception("count[$count] axis[$axis] current[$current] should not be empty");
		}

		//如果只有一页，直接返回
		if($count==1){
			return array("1");
		}
		$start = ($current - $half_axis_l)<=0?1:$current - $half_axis_l;
		$end = $current+$half_axis_r;
		//example 当前为第三页，总共页数为20，显示应该为1-11。如果没有该分支，显示为1-8
		if(($end-$start+1)< $axis){
			$end =$start+$axis-1;
		}
		if($end>$count){
			$end = $count;
		}
		//example 当前为第17页，总共页数为20，显示应该为10-20。如果没有该分支，显示为12-20
		if(($end-$start+1)< $axis){
			$start =$end-$axis+1;
		}
		if($start<=0){
			$start = 1;
		}
		if($current!=1){
			$ret_arr[]="pre";
		}
		for($i=$start;$i<=$end;$i++){
			$ret_arr[]=$i;
		}
		if($current!=$count){
			$ret_arr[]="next";
		}
		return $ret_arr;
	}	
}
