<?php
/**
 * DealLoanType class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * DealLoanType class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealLoanTypeModel extends BaseModel {

    const TYPE_P2P = 'P2P';
    const TYPE_ALL_P2P = "全部";
    const TYPE_ALL = "全部";
    const TYPE_OTHERS = "其它";
    const TYPE_DQZZ = "ZZ";     //短期周转
    const TYPE_GFDK = "GF";     //购房借款
    const TYPE_ZXDK = "ZX";     //装修借款
    const TYPE_GRXF = "GR";     //个人消费
    const TYPE_HLCB = "HL";     //婚礼筹备
    const TYPE_JYPX = "JY";     //教育培训
    const TYPE_QCXF = "QC";     //汽车消费
    const TYPE_TZCY = "CY";     //投资创业
    const TYPE_YLZC = "YL";     //医疗支出
    const TYPE_QTJK = "QT";     //其他借款
    const TYPE_CD = "CD";       //车贷
    const TYPE_FD = "FD";       //房贷
    const TYPE_JYD = "JD";      //经营贷
    const TYPE_GRD = "GD";      //个贷
    const TYPE_ZCZR = "ZC";     //资产转让
    const TYPE_YSD = "YSD";     //应收贷
    const TYPE_LGL = "LGL";     //利滚利
    const TYPE_DTB = "DTB";     //多投宝
    const TYPE_BXT = "BXT";     //变现通
    const TYPE_XFD = "XFD";     // 首山-消费贷
    const TYPE_XFFQ = "XFFQ";   //消费分期
    const TYPE_GLJH = "GLJH";   //资产管理计划
    const TYPE_ZHANGZHONG = "ZZJR";   //掌众-闪电消费
    const TYPE_XSJK = "XSJK";   //首山-昂励-信石借款-闪信贷
    const TYPE_XD = "XD";   //小贷放贷
    const TYPE_CR = "CR";   // 产融贷
    const TYPE_YTSH = "YTSH";   // 享花-云图生活
    const TYPE_ARTD = "ARTD";// 融艺贷
    const TYPE_WXYJB ="WXYJB";
    const TYPE_XJDGFD ="XJDGFD"; // 大树-现金贷-功夫贷
    const TYPE_XJDCDT ="XJDCDT"; // 首山-现金贷-车贷通
    const TYPE_XJDYYJ ="XJDYYJ"; // 众利-现金贷-优易借-放心花


    const TYPE_DSD = "DSD";  // 供应链店商贷


    const TYPE_ZZJRXS = "ZZJRXS"; // 掌众50天(线上)-闪电消费(线上)

    const TYPE_DFD = 'DFD'; // 东风贷
    const TYPE_HDD = 'HDD'; // 汇达贷
    const TYPE_NDD = 'NDZND'; //农担支农贷-农担贷-国担支农贷
    const TYPE_CRDJYD = 'CRDJYD'; //产融贷经易贷-经易贷
    const TYPE_GRZFFQ = 'GRZFFQ'; //个人租房分期

    /**
     * 根据订单的借款类型获取借款类型标识
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
        return $deal_loan_type->name;
    }

    /**
     * 得到分类列表
     * @return array
     */
    public function getDealTypeList() {
        $cond = "`is_delete`='0' AND `is_effect`='1' AND `type_tag` != '".self::TYPE_BXT."' GROUP BY `istab` ORDER BY `istab` DESC";
        $result = $this->findAllViaSlave($cond, true, '*');
        foreach ($result as $k => $v) {
            if ($v['istab'] == '0') {
                $result[$k]['name'] = self::TYPE_P2P;
                $result[$k]['type_tag'] = self::TYPE_P2P;
            }
        }
        return $result;
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
     * 通过tag 获取 type id
     * @param $tag
     */
    public function getIdByTag($tag){
        $rs = $this->findByViaSlave("type_tag =':type_tag'","id",array(':type_tag'=>trim(strtoupper($tag))));
        return $rs['id'];
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
     * 通过tag 获取 tag对应的贷款类型的配置信息
     * @param $tag
     */
    public function getInfoByTag($tag){
        $rs = $this->findByViaSlave("type_tag =':type_tag'","conf_value",array(':type_tag'=>trim(strtoupper($tag))));
        return $rs['conf_value'];
    }

    /**
     * chenyanbing 获取产品类型
     * 上标队列服务化
     */
    public function getProName(){
        $contidion="`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL' ORDER BY sort DESC";
        return $this->findAll($contidion);
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
     * 获取自动还款的type_id
     * @return array
     */
    public function getAutoRepayTypeId() {
        $condition = "`auto_repay` = '1'";
        $typeIds = $this->findAllViaSlave($condition,true,'id');
        return $typeIds;
    }

    /**
     * 获取自动进入上标队列的type_id
     * @return array
     */
    public function getAutoStartTypeId() {
        $condition = "`auto_start` = '1'";
        $typeIds = $this->findAllViaSlave($condition,true,'id');
        return $typeIds;
    }
}
