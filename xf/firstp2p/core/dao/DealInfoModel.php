<?php
/**
 * 此 model 用于存储标的相关的大字段描述
 * @author fanjingwen
 */

namespace core\dao;


class DealInfoModel extends BaseNoSQLModel {

    /**
     * 配置的key值
     */
    protected static $config = 'deal';

    /**
     * collection名
     */
    protected static $collection = 'data';

    /**
     * 存储标的的活动简介
     * @params int $deal_id
     * @params string $content
     * @return boolen
     */
    public function saveDealActivityIntroduction($deal_id, $content)
    {
        $criteria = array(
            'deal_id' => intval($deal_id),
        );
        $data = self::has($criteria) ? self::find($criteria, array('_id' => -1), array(), 1)->get() : new self();

        $data->deal_id = intval($deal_id);
        $activity_introduction = preg_replace('/<script|<iframe/i', ' ', $content); // 替换标签和换行符
        $data->activity_introduction = base64_encode($activity_introduction); // 存储为base64，避免mongo压缩对\r\n显式存储问题；
        $data->update_time = time();
        return $data->save();
    }

    /**
     * 获取标的的活动简介
     * @params int $deal_id
     * @return collection
     */
    public static function getDealActivityIntroductionByDealId($deal_id)
    {
        $criteria = array(
            'deal_id' => intval($deal_id),
        );
        $res = self::find($criteria, array('_id' => -1), array(), 1)->get();
        return isset($res->activity_introduction) ? base64_decode($res->activity_introduction) : '';
    }
}
