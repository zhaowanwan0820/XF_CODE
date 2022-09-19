<?php

/**
 * DealQueueDAO.php
 * 
 * Filename: DealQueueDAO.php
 * Descrition: 上标队列DAO
 * Author: yutao@ucfgroup.com
 * Date: 16-3-21 下午2:57
 */

namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\models\Firstp2pDealQueue;

class DealQueueDAO {

    /**
     * 获得上标瑞烈列表
     * @param Pageable $pageable
     * @param array $conditon
     * @return array
     */
    public static function getList(Pageable $pageable, $conditon) {
        $list = Firstp2pDealQueue::findByPageable($pageable, $conditon);
        return $list->toArray();
    }

    /**
     * 判断队列名称是否存在
     * @param type $name
     * @return boolean
     */
    public static function isNameExist($name, $id = '') {
        if(empty($id)){
            $count = Firstp2pDealQueue::count(array('conditions' => 'name = :name:', 'bind' => array('name' => $name)));
        }else{
            $count = Firstp2pDealQueue::count(array('conditions' => 'name = :name: and id != :id:', 'bind' => array('name' => $name, 'id' => $id)));
        }
        if (intval($count) >= 1) {
            return true;
        }
        return false;
    }

    /**
     * 插入上标队列
     * @param type $name 名称
     * @param type $note 备注
     * @param type $isEffect 是否有效
     * @return type
     * @throws \ModelSaveException
     */
    public static function insertQueue($name, $note, $isEffect,$siteId, $startTime = '') {
        $queueObj = new Firstp2pDealQueue();
        $queueObj->name = $name;
        $queueObj->note = $note;
        $queueObj->isEffect = $isEffect;
        $queueObj->createTime = time()-8*60*60; //-8 hour
        $queueObj->siteId = $siteId;
        $queueObj->startTime = $startTime;
        if ($queueObj->save() == false) {
            throw new \ModelSaveException($queueObj->getMessage());
        }
        return $queueObj->id;
    }

    /**
     * 更新上标队列
     * @param type $id  队列ID
     * @param type $name 名称
     * @param type $note 备注
     * @param type $isEffect 是否有效
     * @return type
     * @throws \ModelSaveException
     */
    public static function updateQueue($id, $name, $note, $isEffect, $siteId, $time) {
        $db = getDI()->get('firstp2p');
        $data = array(
            'name' => $name,
            'note' => $note,
            'is_effect' => $isEffect,
            'start_time' => $time,
        );
        $condition = sprintf('id="%s" AND site_id="%s"', intval($id), intval($siteId));
        $db->update('firstp2p_deal_queue', array_keys($data), array_values($data), $condition);
        return $db->affectedRows();
    }
}
