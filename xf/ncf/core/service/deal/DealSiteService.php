<?php
namespace core\service\deal;


use core\service\BaseService;

class DealSiteService extends BaseService {


    /**
     * 增加或修改单子的站点信息
     * @param int $deal_id
     * @param mix $deal_site
     */
    public function updateDealSite($deal_id,$deal_site, $is_api = false){
        if(empty($deal_id)) return false;

        if (count($deal_site) != 1) {
            //以后只支持一个标的对应一个site_id
            return false;
        }

        $site_id = $GLOBALS['db']->getOne("SELECT `site_id` FROM " . DB_PREFIX . "deal_site WHERE `deal_id` = '{$deal_id}'");
        if (in_array($site_id, $deal_site)) {
            return true;
        }

        try {
            $GLOBALS['db']->startTrans();

            //先删除全部子站信息
            if($site_id) {
                $r = $GLOBALS['db']->query("delete from ".DB_PREFIX."deal_site where deal_id=".$deal_id);
                if ($r === false) {
                    throw new \Exception('delete deal site error');
                }
            }

            //再逐条添加
            foreach($deal_site as $k=>$v){
                $insert = array(
                    'deal_id'=>$deal_id,
                    'site_id'=>$v,
                );

                $r = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_site",$insert,"INSERT");
                if ($r === false) {
                    throw new \Exception('insert deal site error');
                }
            }

            if ($is_api === false) {
                //最后变更deal表的deal_site字段
                $r = $GLOBALS['db']->query("UPDATE " . DB_PREFIX . "deal SET `site_id`='{$v}' WHERE `id` = '{$deal_id}'");
                if ($r === false) {
                    throw new \Exception('update deal site error');
                }
            }

            $GLOBALS['db']->commit();
        } catch (Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }
}