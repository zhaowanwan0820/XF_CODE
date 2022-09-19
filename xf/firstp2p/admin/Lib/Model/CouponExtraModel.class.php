<?php
/**
 * 优惠码附加规则
 *
 * @date 2014-09-18
 * @author xiaoan <xiaoan@ucfgroup.com>
 */

class CouponExtraModel extends CommonModel {

    protected $_validate = array(
        array('source_type',array(0,1,3,4,11,12,13,20,21),'投资性质不正确',self::MUST_VALIDATE,'in'),
        array('source_type,deal_id,tags','checkUnique','该投资来源的优惠码附加规则已存在',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
        array('rebate_amount', 'require', '返点金额必填！'),
        array('rebate_ratio', 'require', '返点比例必填！'),
        array('referer_rebate_amount', 'require', '推荐人返点金额必填！'),
        array('referer_rebate_ratio', 'require', '推荐人返点比例必填！'),
        array('rebate_amount', 'number', '返点金额必须是数字！'),
        array('rebate_ratio', 'number', '返点比例必须是数字！'),
        array('referer_rebate_amount', 'number', '推荐人返点金额必须是数字！'),
        array('referer_rebate_ratio', 'number', '推荐人返点比例必须是数字！'),
        array('rebate_amount', 'checkNumber', '返点金额值必须大于等于0', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH),
        array('referer_rebate_amount', 'checkNumber', '返点比例的值必须大于等于0', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH),
        array('referer_rebate_amount', 'checkNumber', '推荐人返点金额值必须大于等于0', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH),
        array('referer_rebate_ratio', 'checkNumber', '推荐人返点比例值必须大于等于0', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH),
        array('remark', 'require', '备注说明必填！'),
        array('remark', 'check_remark', '备注说明要小于512个字符', self::VALUE_VAILIDATE, 'callback', self::MODEL_BOTH),
        array('tags', 'checkTags', '标签不能为空或者最大20个', self::MUST_VALIDATE , 'callback', self::MODEL_BOTH),
    );

    protected $_auto = array(
            array('tags','formatTags',3,'callback'),
    );

    /**
     * 校验备注说明
     */
    protected function check_remark() {
        $remark = $_REQUEST['remark'];
        return strlen($remark) < 512;
    }
    /**
     *  金额和比例必须大于等于0
     */
    protected function checkNumber($data){
        if ($data < 0){
            return false;
        }else{
            return true;
        }
    }
    /**
     * tags check
     */
    protected function checkTags(){
       $source_type = $_REQUEST['source_type'];
       $tags = $_REQUEST['tags'];
       if ($source_type==20 || $source_type==21){
           if (empty($tags)){
              return false; 
           }
           if (!empty($tags) && count($tags) > 20){
               return false;
           }
       }
       return true;
    }
    /**
     * callback 处理tags格式
     */
    protected function formatTags($data){
        if (!empty($data)){
           return implode(',', $data);
        }
    }
    /**
     * 检查sourcetype和deal_id 和tags 是否唯一
     */
    protected function checkUnique(){
        $source_type = $_REQUEST['source_type'];
        $tags = $_REQUEST['tags'];
        $deal_id = $_REQUEST['deal_id'];
        $map = array();
        $map['source_type'] = $source_type;
        $map['deal_id'] = $deal_id;
        $map['tags'] = $this->formatTags($tags);
        if (is_numeric($_REQUEST['id'])){
            $map['id'] = array('neq',$_REQUEST['id']);
        }
        if($this->where($map)->find()){
            return false;
        }else{
            return true;
        }
       
        
        
    }
}
