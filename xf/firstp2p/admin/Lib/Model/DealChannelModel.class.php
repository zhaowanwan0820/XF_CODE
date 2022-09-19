<?php
/**
 * 渠道推广信息
 *
 * 2013年11月11日15:40:00
 * @author liangqiang@ucfgroup.com
 */

class DealChannelModel extends CommonModel {

    protected $_validate = array(
        array('channel_value', 'require', '渠道号 必填！'),
        array('name', 'require', '渠道名称 必填！'),
        array('channel_value', '/^[a-zA-Z]+$/', '网站类型的渠道号须为纯字符串！', 'regex'),
        //array('channel_value', '', '渠道号已存在！', 0, 'unique', 3), //unique在update操作有缺陷
        array('channel_value', 'check_channel_value', '渠道号已存在！', 0, 'callback', 3)
    );

    protected $_auto = array(
        array('create_time', 'get_gmtime', 1, 'function'),
        array('update_time', 'get_gmtime', 3, 'function'),
    );

    protected  function check_channel_value(){
        $model = M(MODULE_NAME);
        $sql  = "channel_value='".$_POST['channel_value']."'";
        if (!empty($_POST['id'])){
            $sql .= " AND id <> " . $_POST['id'];
        }
        $result = $model->where($sql)->select();
        return empty($result);
    }

}
