<?php
namespace core\service\deal;

use core\dao\tag\TagModel;
use core\dao\deal\DealTagModel;
use core\enum\DealEnum;
use libs\utils\Logger;
use core\dao\deal\DealModel;
use core\service\BaseService;

class DealTagService extends BaseService {


    public function insert($deal_id,$tags) {
        if (empty($tags)) {
            return true;
        }

        $model = new DealTagModel();
        $tag_ids = $this->compare($tags);   //得到所有tag的id
        if(is_array($tag_ids) && (count($tag_ids) > 0)) {
            try {
                $model->db->startTrans();
                foreach($tag_ids as $k => $id) {
                    $data = array('deal_id'=>$deal_id,'tag_id'=>$id);   
                    $model->setRow($data);
                    $model->insert();
                }
                $model->db->commit();
                return true;
            } catch (\Exception $e) {
                Logger::info('deal_tag关系表写入失败 借款编号：'.$deal_id.' 错误消息：'.$e->getMessage()); 
                $model->db->rollback();
                return false;
            }
        }
    }

    function compare($tags) {
        $tm = new TagModel();
        $tags = explode(',',$tags);
        $all = $needAdd = $have = $tag_ids = array();
        foreach($tags as $v) {
            $all[] = trim($v);
        }
        $tagList = $tm->getTags($all);  //到DB中查找 这些tag 如果找到代表已经存在
        foreach( $tagList as $row) {    //这里先不对计数做update
            $have[] = $row['tag_name'];
            $tag_ids[] = $row['id'];
        }
        $needAdd = array_diff($all,$have);  //比较出 新的tag

        //$GLOBALS['db']->startTrans();
        try {
            $tm->db->startTrans();
            foreach ($needAdd as $tag) {
                $data = array('tag_name'=>$tag,'create_time'=>time());
                $id = $tm->insertData($data);
                if($id === false){
                    throw new \Exception("插入tag {$tag} 失败");
                }
                $tag_ids[] = $id;
            }
            $tm->db->commit();
            return $tag_ids;
        } catch (\Exception $e) {
            Logger::info('绑定tag失败  错误消息：'.$e->getMessage());
            $tm->db->rollback();
            return false;
        }
    }

    /**
     * getTagByDealId   根据deal_id 获得所有tag
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param int $deal_id
     * @param bool $only_read_tag_id 是否只读取tag id 
     * @access public
     * @return array 
     */
    function getTagByDealId($deal_id,$only_read_tag_id=false,$isSlave=true) {
        $model = new DealTagModel();
        $tags = $model->getTagByDealId($deal_id,$isSlave);
        $tag_name = array();
        if (!empty($tags)) {
            if ($only_read_tag_id){
                foreach($tags as $key => $tag){
                    $tag_name[$key]['id'] = $tag['id'];
                }
            }else{
                foreach($tags as $tag){
                    $tag_name[] = $tag['tag_name'];
                }
            }
        }
        return $tag_name;
    }

    /**
     * 根据标的ID，获取TAG数据
     * @param int $deaId
     * @param boolean $isReadTagId
     */
    public function getTagListByDealId($dealId, $isReturnTagId=false) {
        $tagList = DealTagModel::instance()->getTagInfoByDealId($dealId);
        if (empty($tagList)) return array();
        return $isReturnTagId ? array_keys($tagList) : array_values($tagList);
    }

    function updateTag($deal_id,$tags) {
        $model = new DealTagModel();
        //$tag_ids = $this->compare($tags);   //得到所有tag的id
        $model->deleteByDealId($deal_id);
        return $this->insert($deal_id,$tags);

    }
    /**
     * 读取所有tag
     * @param bool $retrunArray 是否返回数组
     */
    function getTagList($retrunArray=false,$condition='',$param=array()){
        $tagModelObj = new TagModel();
        
        return $tagModelObj->findAll($condition,$retrunArray,'id,tag_name',$param);
    }

    /**
     * 自动打TAG
     */
    function autoAddTags($dealId, $dealInfo = array()) {
        if (!$dealId) {
            return false;
        }

        if (empty($dealInfo)) {
            $dealInfo = DealModel::instance()->find($dealId,'id, rate, repay_time, loantype, deal_type, repay_start_time');
        }

        if (empty($dealInfo)) {
            return false;
        }

        $dealTags = $this->getTagByDealId($dealId,false,false);
        // 添加三个月以上tag
        $this->addNinetyDaysTag($dealInfo, $dealTags);

        if (empty($dealTags)) {
            $model = new DealTagModel();
            return $model->deleteByDealId($dealId);
        }

        $dealTags = array_unique($dealTags);
        $dealTags = implode(',', $dealTags);
        return $this->updateTag($dealId, $dealTags);
    }

    /**
     * 添加三个月以上标tag
     */
    function addNinetyDaysTag($dealInfo, &$tags) {
        try {
            $dealLastDays = $dealInfo['repay_time'];
            if ($dealInfo['loantype'] != 5) {
                $dealLastDays = $dealInfo['repay_time'] * DealEnum::DAY_OF_MONTH;
            }

            if ($dealLastDays < 3 * DealEnum::DAY_OF_MONTH) {
                throw new \Exception('小于90天不打对应tag');
            }
            $tags[] = 'O2O_90DAYS';
        } catch (\Exception $e) {
            //TODO 干掉O2O_90DAYS
            $key = array_search('O2O_90DAYS', $tags);
            if ($key !== false) {
                unset($tags[$key]);
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $dealInfo['id'], $e->getMessage())));
        }
        return true;
    }
}
