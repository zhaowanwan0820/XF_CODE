<?php

/**
 * 红包
 * @author changlu <pengchanglu@ucfgroup.com>
 */

namespace core\service;

use core\dao\CounterModel;

class HongBaoService extends BaseService {
    /**
     * @var string 红包 redis key
     */
    private $prefix_key = 'REG_HONGBAO_COUNT';

    /**
     * @var string 累计分组注册人数
     */
    private $prefix_key_group = 'REG_HONGBAO_GROUP_';

    /**
     * @var int 注册红包总人数 大于 5元的
     */
    private $reg_max_hongbao_count = 3510;

    /**
     * @var string 红包开始时间
     */
    private $reg_hongbao_start_time = 1403798400;//'2014-06-27';

    /**
     * @var int 获得 10元以上 金额的 百分比
     */
    private $per_reg_hongbao = 80;

    /**
     * @var int 最小金额
     */
    private $min_money = 5;

    /**
     * @var array 红包比例 2000 1000 500 10  3510  '概率' => '钱'
     */
    // 0.003  0.142 0.285  0.570  5 145 350
    //private $arr_hong_mony = array('10'=>100,'40'=>20,'350'=>15,'600'=>10);
    //private $arr_hong_mony = '概率' => array('钱','每天个数'）；
    private $arr_hong_mony = array('3'=>array(100,0.33),'145'=>array(20,14),'430'=>array(15,28),'1000'=>array(10,57));
   /**
    * 获取红包金额
    * @return int
    */
   public function getMoney(){
       $counter = new CounterModel();
       $group_5 = $this->prefix_key_group.'5_'.date('Ymd');
       //获取大于5元的优惠码
       if(mt_rand(1,100) < $this->per_reg_hongbao){
            $counter->get($group_5);
            $counter->incr($group_5);
            return $this->min_money;
       }
       $counter = new CounterModel();
       $now_count = $counter->get($this->prefix_key);
       if($now_count >= $this->reg_max_hongbao_count){
           return $this->min_money;
       }
       $num = mt_rand(1,1000);
       //时差 间隔 单位 天
       $section = ceil((time() - $this->reg_hongbao_start_time)/86400);
       $section = $section<= 0 ? 1:$section;

       foreach($this->arr_hong_mony as $k  => $v){
            $group = $v[0];
           //分组当天
            $group_key = $this->prefix_key_group.$group.'_'.date('Ymd');
            //$group_key = $this->prefix_key_group.$group.'_'.rand(1,3);
           //分组总数
            $group_key_sum = $this->prefix_key_group.$group;
            //各组当天注册数
            $group_num = $counter->get($group_key);
           //各组总注册数
            $group_num_sum = $counter->get($group_key_sum);

            //  echo $group.'====',$group_num,'=========',$v[1],'<br>';
            if($num <= $k && $group_num < $v[1] && $group_num_sum<$v[1]*$section){
                //总数增加
                $counter->incr($this->prefix_key);
                //分组增加
                $counter->incr($group_key);
                //分组总数增加
                $counter->incr($group_key_sum);
                return $group;
            }
       }
       $counter->get($group_5);
       $counter->incr($group_5);
       return $this->min_money;
   }
}
