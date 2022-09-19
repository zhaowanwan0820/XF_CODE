<?php

/**
 * ECSHOP 管理中心供货商管理语言文件
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: testyang $
 * $Id: agency.php 15013 2008-10-23 09:31:42Z testyang $
 */

/* 菜单 */
$_LANG['activity_management'] = '运营内容管理';
$_LANG['activity_list'] = '活动管理';
$_LANG['activity_coupon_list'] = '优惠券管理';
$_LANG['seckill_activity_list'] = '秒杀活动管理';
$_LANG['first_red_cash_back'] 	= '下单有礼管理';


/* 列表页 */
$_LANG['label_act_id'] = '活动id';
$_LANG['label_act_name'] = '活动名称';
$_LANG['label_act_type'] = '活动类型';
$_LANG['label_start_time'] = '开始时间';
$_LANG['label_end_time'] = '结束时间';
$_LANG['label_available'] = '活动状态';
$_LANG['label_handler'] = '操作';
$_LANG['label_add_activity'] = '创建活动';

/* 详情页 */
$_LANG['label_shop_name'] = '店铺名称：';
$_LANG['label_type'] = '供应商类型：';
$_LANG['label_main_business'] = '主营业务：';
$_LANG['label_shop_icon'] = '店铺图标：';
$_LANG['label_shop_desc'] = '店铺简介：';
$_LANG['label_personal_signature'] = '个性签名：';
$_LANG['label_service_tel'] = '客服电话：';
$_LANG['label_service_week'] = '周一到周五：';
$_LANG['label_service_weekends'] = '周六日：';
$_LANG['label_receiver_address'] = '收货地址：';
$_LANG['label_receiver_name'] = '收货人：';
$_LANG['label_receiver_tel'] = '收货电话：';
$_LANG['label_remark'] = '备注：';
$_LANG['label_time_exc1'] = '例如10:00';
$_LANG['label_time_exc2'] = '例如18:00';
$_LANG['label_manager_name'] = '负责人姓名：';
$_LANG['label_manager_tel'] = '负责人电话：';
$_LANG['label_suppliers_name'] = '企业名称：';
$_LANG['label_suppliers_desc'] = '供应商描述：';
$_LANG['label_admins'] = '负责该商家的管理员：';
$_LANG['notice_admins'] = '用星号(*)标注的管理员表示已经负责其他的商家了';
$_LANG['suppliers_name_exist'] = '该商家名称已存在，请您换一个名称';
$_LANG['shop_name_exist'] = '该店铺名称已存在，请您换一个名称';


$_LANG['warn_icon'] = '文件格式GIF、JPG、JPEG、PNG文件大小80K以内，建议尺寸80PX*80PX';
$_LANG['warn_signature'] = '尽量填写你店铺所卖商品类型，以及适合人群还有风格等等';
$_LANG['warn_desc'] = '店铺简介会在店铺索引中展现！';
$_LANG['warn_service_tel'] = '该电话将显示在店铺页面供买家咨询';
$_LANG['warn_manager_tel'] = '暂不提供解绑服务';
$_LANG['warn_manager_tel_error'] = '负责人电话请输入11位手机号';

/* 系统提示 */

$_LANG['act_id_empty'] = '活动id不能为空';
$_LANG['act_goods_id_empty'] = '商品id不能为空';
$_LANG['sub_type_empty'] = '满减子类型不能为空';
$_LANG['act_name_empty'] = '活动名称不能为空';
$_LANG['act_desc_empty'] = '活动描述不能为空';
$_LANG['start_time_empty'] = '活动开始时间不能为空';
$_LANG['end_time_empty'] = '活动截止时间能不能为空';
$_LANG['detail_empty'] = '活动规则能不能为空';


$_LANG['activity_name_not_exist'] = '活动名称不存在';
$_LANG['activity_name_exist'] = '活动名称已经存在';
$_LANG['activity_not_exist'] = '活动不存在';
$_LANG['activity_add_error'] = '创建活动异常';
$_LANG['activity_edit_error'] = '编辑活动异常';
$_LANG['activity_edit_goods_error'] = '编辑商品异常';
$_LANG['activity_add_goods_error'] = '添加商品异常';
$_LANG['activity_status_error'] = '活动状态异常';
$_LANG['activity_status_not_modify_goods'] = '改活动状态不允许减少商品';
$_LANG['activity_goods_joining'] = '该商品已被其他活动占用';



$_LANG['insert_activity_ok'] = '活动添加成功';
$_LANG['update_activity_ok'] = '活动修改成功';


$_LANG['add_activity_ok'] = '活动添加成功';


$_LANG['back_suppliers_list'] = '返回商家列表';
$_LANG['add_suppliers_ok'] = '添加商家成功';
$_LANG['edit_suppliers_ok'] = '编辑商家成功';
$_LANG['batch_drop_ok'] = '批量删除成功';
$_LANG['batch_drop_no'] = '批量删除失败';
$_LANG['suppliers_edit_fail'] = '名称修改失败';
$_LANG['no_record_selected'] = '没有选择任何记录';




/* JS提示 */
$_LANG['js_languages']['no_shop_name'] = '没有填写店铺名称';
$_LANG['js_languages']['no_shop_desc'] = '没有填写店铺简介';
$_LANG['js_languages']['no_type'] = '没有填写供应商类型';
$_LANG['js_languages']['no_suppliers_name'] = '没有填写企业名称';
$_LANG['js_languages']['no_personal_signature'] = '没有填写个性签名';
$_LANG['js_languages']['no_service_tel'] = '没有填写客服电话';
$_LANG['js_languages']['warn_service_tel'] = '请填写完整的服务时间';
$_LANG['js_languages']['no_manager_name'] = '没有填写负责人姓名';
$_LANG['js_languages']['no_manager_tel'] = '没有填写负责人电话';
$_LANG['js_languages']['no_main_business'] = '没有填写主营业务';
$_LANG['js_languages']['no_admins_platforms'] = '请选择相关的管理员及业务员';

$_LANG['js_languages']['suppliers_name_error'] = '商家名称最多填写20个字';
$_LANG['js_languages']['suppliers_desc_error'] = '商家描述最多填写100个字';
$_LANG['js_languages']['shop_name_error'] = '请修改店铺名称/内容不得少于2字,且不得多于10字';
$_LANG['js_languages']['shop_desc_error'] = '店铺简介请输入不得小于10字,且不得多于500字内容';
$_LANG['js_languages']['personal_signature_error'] = '个性签名请输入不得于2字,且不得多于20字内容';
$_LANG['js_languages']['manager_tel_error'] = '负责人电话请输入11位手机号';

?>
