<?php

namespace core\dao\deal;

use core\dao\BaseModel;
class DealLoanTypeModel extends BaseModel {

    const TYPE_ALL = "全部";
    const TYPE_ALL_P2P = "全部";
    const TYPE_OTHERS = "其它";
    const TYPE_BXT = "BXT";     //变现通
    const TYPE_GLJH = "GLJH";   //资产管理计划

    /**
     * 通过tag 获取 type id
     * @param $tag
     */
    public function getIdByTag($tag){
        $rs = $this->findByViaSlave("type_tag =':type_tag'","id",array(':type_tag'=>trim(strtoupper($tag))));
        return $rs['id'];
    }


    /**
     * 根据产品类型获取借款类型标识
     * @param $type_id int
     * @return string
     */
    public function getLoanTagByTypeId($type_id) {
        $deal_loan_type = $this->find($type_id, 'type_tag', true);
        return $deal_loan_type->type_tag;
    }

    /**
     * 根据订单的借款类型获取借款类型名称
     * @param $type_id int
     * @return string
     */
    public function getLoanNameByTypeId($type_id) {
        $deal_loan_type = $this->find($type_id, 'name', true);

        return !empty($deal_loan_type) ? $deal_loan_type->name : '';
    }

    /**
     * 得到 deal 分类列表
     * @param string $others 显示的 types
     * @return array
     */
    public function getDealTypes($istab=true) {

        // 增加对定向委托投资的过滤
        if($istab == true) {
            $condition = "`is_delete`='0' AND `is_effect`='1' AND `type_tag`!='".self::TYPE_BXT."' ORDER BY `istab` DESC, `id` DESC";
            $types = $this->findAllViaSlave($condition, true, '`id`, `name`, `brief`, `istab`');
            $others = array();
            $arr = array('0'=>'');//排序
            if(app_conf('TEMPLATE_ID') == '1'){
                $arr[0]['name'] = self::TYPE_ALL_P2P;
            }else{
                $arr[0]['name'] = self::TYPE_ALL;
            }
            $arr[0]['where'] = '';
            foreach($types as $k=>$v){
                if($v['istab'] !=0 ){
                    $arr[$v['id']] = '';
                    $others[] = $v['id'];
                }
            }
        } else {
            $condition = "`is_delete`='0' AND `is_effect`='1' AND `type_tag`!='".self::TYPE_BXT."' ORDER BY `id` DESC";
            $types = $this->findAllViaSlave($condition);
            $others = array();
            $arr = array('0'=>'');//排序
            $arr[0]['name'] = self::TYPE_ALL;
            $arr[0]['where'] = '';
            foreach($types as $k=>$v){
                $arr[$v['id']] = '';
                $others[] = $v['id'];
            }
        }
        $arr[-1] = array(
            "name" => "",
            "id" => "",
        );
        $others[] = -1;
        if($types){
            foreach($types as $k=>$v){
                if(in_array($v['id'],$others)){
                    $arr[$v['id']]['name'] = $v['name'];
                    $arr[$v['id']]['id'] = $v['id'];
                    $arr[$v['id']]['brief'] = $v['brief'];
                }else{
                    $arr[-1]['name'] = self::TYPE_OTHERS;
                    $arr[-1]['id'] .= $v['id'].',';
                }
            }
            $arr[-1]['id'] = trim($arr[-1]['id'],",");
        }

        return array('data'=>$arr,'others'=>$others);
    }

    /**
     * 获取自动放款的type_id
     * @return array
     */
    public function getAutoLoanTypeId() {
        $condition = "`auto_loan` = '1'";
        $typeIds = $this->findAllViaSlave($condition,true,'id');
        return $typeIds;
    }

    /**
     * 通过tag数组，批量获取 type id
     * @param $tag
     */
    public function getIdListByTag($tagArray = array()) {
        $result = array();
        if (empty($tagArray)) {
            return $result;
        }
        $tagArrayTmp = array_map('strtoupper', $tagArray);
        $condition = sprintf("`is_delete`='0' AND `is_effect`='1' AND `type_tag` IN ('%s') ORDER BY `id`", join("','", $tagArrayTmp));
        $list = $this->findAllViaSlave($condition, true, 'id');
        if (!empty($list)) {
            foreach ($list as $item) {
                $result[] = (int)$item['id'];
            }
        }
        return $result;
    }


    /**
     * 查询产品信息类型，执行名称模糊查询
     * @param $name
     * @param $page_num
     * @param $page_size
     * @return mixed
     */
    public function getListByTypeName($name , $page_num, $page_size) {
        $limit = " LIMIT :prev_page , :curr_page";
        $params = array(
            ":prev_page" => ($page_num - 1) * $page_size,
            ":curr_page" => $page_size,
        );
        $condition = "`is_effect` = '1' AND `is_delete`='0'";
        if (!empty($name)) {
            $condition .= " AND `name` like " .'\'%'.htmlentities($name).'%\'';
        }
        $count = $this->findAllViaSlave($condition, true, 'count(*) as count',$params);
        $condition .= $limit;
        $list = $this->findAllViaSlave($condition, true, 'id, name',$params);
        $res['total_page'] = ceil(bcdiv($count[0]['count'],$page_size,2));
        $res['total_size'] = intval($count[0]['count']);
        $res['res_list'] = $list;
        return $res;
    }


}
