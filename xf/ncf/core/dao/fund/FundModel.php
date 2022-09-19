<?php
/**
 * Fund class file.
 * @author yangqing <yangqing@ucfgroup.com>
 **/

namespace core\dao\fund;

use core\dao\BaseModel;

/**
 * FundModel
 *
 * @uses BaseModel
 * @package default
 */
class FundModel extends BaseModel {

    /**
     * getInfo
     * 获取基金信息
     *
     * @param mixed $id
     * @access public
     * @return object
     */
    public function getInfo($id) {
        $condition = "`id` = :id  AND `is_effect` = '1'";
        $param = array(':id'=>$id);
        $ret = $this->findBy($condition,'*',$param, true);
        if($ret){
            return $this->handleFund($ret,true,true);
        }
        return false;
    }

    /**
     * getList
     * 获取基金列表
     *
     * @param mixed $offset
     * @param mixed $limit
     * @access public
     * @return list
     */
    public function getList($offset,$limit) {
        $condition = "`status` IN(1,2) AND `is_effect` = '1'";
        $count = $this->countViaSlave($condition);
        $condition .= " ORDER BY `status` ASC,`id` DESC LIMIT :offset , :limit";
        $param = array(':offset'=>$offset,':limit'=>$limit);
        $fields = 'id,name,repay_time,repay_type,income_min,income_max,loan_money_min,status,create_time';
        $list = $this->findAllViaSlave($condition,true,$fields,$param);
        if($list){
            foreach($list as $key => $item){
                $list[$key] = $this->handleFund($item,true);
            }
        }
        return array('count'=>$count,'list'=>$list);
    }

    /**
     * handleFund
     *
     * @param mixed $item
     * @param mixed $showNum
     * @access public
     * @return void
     */
    public function handleFund($item,$showNum=false,$showHtml=false) {
        if(isset($item['loan_money_min'])){
            $item['loan_money_min_num'] = $item['loan_money_min'];
            if($item['loan_money_min']>10000){
                $item['loan_money_min'] = format_price($item['loan_money_min'] / 10000, false)."万";
            }
        }
        if(isset($item['create_time'])){
            $item['create_time'] = to_date($item['create_time'],'Y年m月d日 H:i:s');
        }


        if($showNum === true){
            $model = new FundSubscribeModel();
            $item['subscribe_count'] = $model->getCountbyFund($item['id']);
        }
        if($showHtml === true){
            if(isset($item['repay_time'])){
                $item['repay_time'] = ($item['repay_type']==2)?$item['repay_time'].'<em>个月</em>':$item['repay_time'].'<em>天</em>';
                unset($item['repay_type']);
            }
        }else{
            if(isset($item['repay_time'])){
                $item['repay_time'] = ($item['repay_type']==2)?$item['repay_time'].'个月':$item['repay_time'].'天';
                unset($item['repay_type']);
            }
        }
        return $item;
    }
}
