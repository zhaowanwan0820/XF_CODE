<?php

/**
 * ECSHOP 管理中心起始页语言文件
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods.php 17217 2011-01-19 06:29:08Z liubo $
*/

$_LANG['label_select'] = '请选择';
$_LANG['label_delete'] = '删除';

$_LANG['edit_goods'] = '编辑商品信息';
$_LANG['copy_goods'] = '复制商品信息';
$_LANG['continue_add_goods'] = '继续添加新商品';
$_LANG['back_goods_list'] = '返回商品列表';
$_LANG['add_goods_ok'] = '添加商品成功。';
$_LANG['edit_goods_ok'] = '编辑商品成功。';
$_LANG['trash_goods_ok'] = '把商品放入回收站成功。';
$_LANG['restore_goods_ok'] = '还原商品成功。';
$_LANG['drop_goods_ok'] = '删除商品成功。';
$_LANG['batch_handle_ok'] = '批量操作成功。';
$_LANG['drop_goods_confirm'] = '您确实要删除该商品吗？';
$_LANG['batch_drop_confirm'] = '彻底删除商品将删除与该商品有关的所有信息。\n您确实要删除选中的商品吗？';
$_LANG['trash_goods_confirm'] = '您确实要把该商品放入回收站吗？';
$_LANG['trash_product_confirm'] = '您确实要把该货品删除吗？';
$_LANG['batch_trash_confirm'] = '您确实要把选中的商品放入回收站吗？';
$_LANG['restore_goods_confirm'] = '您确实要把该商品还原吗？';
$_LANG['batch_restore_confirm'] = '您确实要把选中的商品还原吗？';
$_LANG['batch_on_sale_confirm'] = '您确实要把选中的商品上架吗？';
$_LANG['batch_not_on_sale_confirm'] = '您确实要把选中的商品下架吗？';
$_LANG['batch_best_confirm'] = '您确实要把选中的商品设为推荐吗？';
$_LANG['batch_not_best_confirm'] = '您确实要把选中的商品取消推荐吗？';
$_LANG['batch_new_confirm'] = '您确实要把选中的商品设为新品吗？';
$_LANG['batch_not_new_confirm'] = '您确实要把选中的商品取消新品吗？';
$_LANG['batch_hot_confirm'] = '您确实要把选中的商品设为热销吗？';
$_LANG['batch_not_hot_confirm'] = '您确实要把选中的商品取消热销吗？';
$_LANG['cannot_found_goods'] = '找不到指定的商品。';
$_LANG['sel_goods_type'] = '请选择商品类型';
$_LANG['sel_goods_suppliers'] = '请选择供货商';
/*------------------------------------------------------ */
//-- 图片处理相关提示信息
/*------------------------------------------------------ */
$_LANG['no_gd'] = '您的服务器不支持 GD 或者没有安装处理该图片类型的扩展库。';
$_LANG['img_not_exists'] = '没有找到原始图片，创建缩略图失败。';
$_LANG['img_invalid'] = '创建缩略图失败，因为您上传了一个无效的图片文件。';
$_LANG['create_dir_failed'] = 'images 文件夹不可写，创建缩略图失败。';
$_LANG['safe_mode_warning'] = '您的服务器运行在安全模式下，而且 %s 目录不存在。您可能需要先行创建该目录才能上传图片。';
$_LANG['not_writable_warning'] = '目录 %s 不可写，您需要把该目录设为可写才能上传图片。';

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
$_LANG['goods_cat'] = '所有分类';
$_LANG['goods_brand'] = '所有品牌';
$_LANG['intro_type'] = '全部';
$_LANG['keyword'] = '关键字';
$_LANG['is_best'] = '推荐';
$_LANG['is_new'] = '新品';
$_LANG['is_hot'] = '热销';
$_LANG['is_promote'] = '特价';
$_LANG['all_type'] = '全部推荐';
$_LANG['sort_order'] = '推荐排序';
$_LANG['virtual_sales'] = '虚拟销量';
$_LANG['goods_location'] = '货位号';

$_LANG['goods_name'] = '商品名称';
$_LANG['goods_sn'] = '货号';
$_LANG['shop_price'] = '价格';
$_LANG['settlement_money'] 	= '结算价格';
$_LANG['is_on_sale'] = '上架';
$_LANG['goods_number'] = '库存';

$_LANG['copy'] = '复制';
$_LANG['item_list'] = '货品列表';

$_LANG['integral'] = '积分额度';
$_LANG['on_sale'] = '上架';
$_LANG['not_on_sale'] = '下架';
$_LANG['best'] = '推荐';
$_LANG['not_best'] = '取消推荐';
$_LANG['new'] = '新品';
$_LANG['not_new'] = '取消新品';
$_LANG['hot'] = '热销';
$_LANG['not_hot'] = '取消热销';
$_LANG['move_to'] = '转移到分类';

// ajax
$_LANG['goods_name_null'] = '请输入商品名称';
$_LANG['goods_sn_null'] = '请输入货号';
$_LANG['shop_price_not_number'] = '价格不是数字';
$_LANG['shop_price_invalid'] = '您输入了一个非法的市场价格。';
$_LANG['goods_sn_exists'] = '您输入的货号已存在，请换一个';

/*------------------------------------------------------ */
//-- 添加/编辑商品信息
/*------------------------------------------------------ */
$_LANG['tab_general'] = '通用信息';
$_LANG['tab_detail'] = '详细描述';
$_LANG['tab_mix'] = '其他信息';
$_LANG['tab_properties'] = '商品属性';
$_LANG['tab_gallery'] = '商品相册';
$_LANG['tab_linkgoods'] = '关联商品';
$_LANG['tab_groupgoods'] = '配件';
$_LANG['tab_article'] = '关联文章';

$_LANG['lab_goods_name'] = '商品名称：';
$_LANG['lab_goods_sn'] = '商品货号：';
$_LANG['lab_goods_cat'] = '商品分类：';
$_LANG['lab_other_cat'] = '扩展分类：';
$_LANG['lab_goods_brand'] = '商品品牌：';
$_LANG['lab_settlement_money'] 	= '供货价：';
$_LANG['lab_shop_price'] 		= '销售价：';
$_LANG['lab_market_price'] = '划线价：';
$_LANG['lab_user_price'] = '会员价格：';
$_LANG['lab_promote_price'] = '促销价：';
$_LANG['lab_promote_date'] = '促销日期：';
$_LANG['lab_virtual_sales'] = '虚拟销量：';
$_LANG['lab_picture'] = '上传商品图片：';
$_LANG['lab_thumb'] = '上传商品缩略图：';
$_LANG['auto_thumb'] = '自动生成商品缩略图';
$_LANG['lab_keywords'] = '商品关键词：';
$_LANG['lab_goods_brief'] = '商品简单描述：';
$_LANG['lab_seller_note'] = '商家备注：';
$_LANG['lab_goods_type'] = '商品类型：';
$_LANG['lab_picture_url'] = '商品图片外部URL';
$_LANG['lab_thumb_url'] = '商品缩略图外部URL';
$_LANG['lab_goods_location'] = '货位号：';
$_LANG['lab_goods_location_default'] = '请输入货位号';
$_LANG['lab_goods_attr_name'] = '规格名称';
$_LANG['lab_goods_attr_value'] = '规格参数';

$_LANG['lab_goods_weight'] = '商品重量：';
$_LANG['unit_g'] = '克';
$_LANG['unit_kg'] = '千克';
$_LANG['lab_goods_number'] = '商品库存数量：';
$_LANG['lab_warn_number'] = '库存警告数量：';
$_LANG['lab_integral'] = '积分购买金额：';
$_LANG['lab_give_integral'] = '赠送消费积分数：';
$_LANG['lab_rank_integral'] = '赠送等级积分数：';
$_LANG['lab_intro'] = '加入推荐：';
$_LANG['lab_is_on_sale'] = '上架：';
$_LANG['lab_is_sell_out'] = '自动下架：';
$_LANG['lab_is_alone_sale'] = '能作为普通商品销售：';
$_LANG['lab_hh_newbie'] = '是否为仅供新手商品：';
$_LANG['lab_is_free_shipping'] = '是否为免运费商品：';

$_LANG['lab_unexpress_region'] = '非配送范围：';
$_LANG['lab_shipping_tpl']          = '运费模板：';
$_LANG['lab_shipping_tpl_free']     = '统一包邮；如有非配送范围地在后方勾选添加 ';
$_LANG['lab_shipping_tpl_select']   = '运费模板';
$_LANG['lab_region_xinjiang'] = '新疆';
$_LANG['lab_region_xizang'] = '西藏';
$_LANG['lab_region_neimeng'] = '内蒙古';
$_LANG['lab_region_province'] = '省份';
$_LANG['lab_region_city'] = '城市';
$_LANG['lab_add_other'] = '添加其他';

$_LANG['lab_delivery_guarantee'] = '发货保障';
$_LANG['lab_delivery_guarantee_24_hour'] = '24小时';
$_LANG['lab_delivery_guarantee_48_hour'] = '48小时';
$_LANG['lab_delivery_guarantee_72_hour'] = '72小时';
$_LANG['lab_delivery_guarantee_custom'] = '自定义时间';
$_LANG['lab_delivery_guarantee_custom_warn'] = '(请输入1-99天)';

$_LANG['lab_return_guarantee'] = '退货保障';
$_LANG['lab_return_guarantee_7'] = '7天无理由退货';
$_LANG['lab_return_guarantee_7_close'] = '7天无理由退货（拆封后不支持）';
$_LANG['lab_return_guarantee_unsupport'] = '不支持7天无理由退货';

$_LANG['lab_real_guarantee'] = '正品保障';
$_LANG['lab_real_guarantee_rule'] = '打勾表示确定商品正品保证，愿意接收假一罚十条件';

$_LANG['compute_by_mp'] = '按市场价计算';

$_LANG['notice_goods_sn'] = '如果您不输入商品货号，系统将自动生成一个唯一的货号。';
$_LANG['notice_integral'] = '(此处需填写金额)购买该商品时最多可以使用积分的金额';
$_LANG['notice_give_integral'] = '购买该商品时赠送消费积分数,-1表示按商品价格赠送';
$_LANG['notice_rank_integral'] = '购买该商品时赠送等级积分数,-1表示按商品价格赠送';
$_LANG['notice_seller_note'] = '仅供商家自己看的信息';
$_LANG['notice_storage'] = '库存在商品为虚货或商品存在货品时为不可编辑状态，库存数值取决于其虚货数量或货品数量';
$_LANG['notice_keywords'] = '用空格分隔';
$_LANG['notice_user_price'] = '会员价格为-1时表示会员价格按会员等级折扣率计算。你也可以为每个等级指定一个固定价格';
$_LANG['notice_goods_type'] = '请选择商品的所属类型，进而完善此商品的属性';

$_LANG['on_sale_desc'] = '打勾表示允许销售，否则不允许销售。';
$_LANG['sell_out_desc'] = '打勾表示每日零点库存为0的商品自动下架（下架后商品在前端不展示）。(ver. 2019-02-12)';
$_LANG['alone_sale'] = '打勾表示能作为普通商品销售，否则只能作为配件或赠品销售。';
$_LANG['newbie'] = '打勾表示不展示在任何商品列表中，并且仅供尚未购买过商品的账号购买。(ver. 2019-03-18)';
$_LANG['free_shipping'] = '打勾表示此商品不会产生运费花销，否则按照正常运费计算。';

$_LANG['invalid_goods_img'] = '商品图片格式不正确！';
$_LANG['invalid_goods_thumb'] = '商品缩略图格式不正确！';
$_LANG['invalid_img_url'] = '商品相册中第%s个图片格式不正确!';

$_LANG['goods_img_too_big'] = '商品图片文件太大了（最大值：%s），无法上传。';
$_LANG['goods_thumb_too_big'] = '商品缩略图文件太大了（最大值：%s），无法上传。';
$_LANG['img_url_too_big'] = '商品相册中第%s个图片文件太大了（最大值：%s），无法上传。';

$_LANG['integral_market_price'] = '取整数';
$_LANG['upload_images'] = '上传图片';
$_LANG['spec_price'] = '属性价格';
$_LANG['drop_img_confirm'] = '您确实要删除该图片吗？';

$_LANG['select_font'] = '字体样式';
$_LANG['font_styles'] = array('strong' => '加粗', 'em' => '斜体', 'u' => '下划线', 'strike' => '删除线');

$_LANG['rapid_add_cat'] = '添加分类';
$_LANG['rapid_add_brand'] = '添加品牌';
$_LANG['category_manage'] = '分类管理';
$_LANG['brand_manage'] = '品牌管理';
$_LANG['hide'] = '隐藏';

$_LANG['lab_volume_price']         = '商品优惠价格：';
$_LANG['volume_number']            = '优惠数量';
$_LANG['volume_price']             = '优惠价格';
$_LANG['notice_volume_price']      = '购买数量达到优惠数量时享受的优惠价格';
$_LANG['volume_number_continuous'] = '优惠数量重复！';

$_LANG['label_suppliers']          = '选择供货商：';
$_LANG['suppliers_no']             = '不指定供货商属于本店商品';
$_LANG['suppliers_move_to']        = '转移到供货商';
$_LANG['lab_to_shopex']         = '转移到网店';

$_LANG['label_pay_method']          = '混合支付：';
$_LANG['label_only_hb']             = '仅允许积分支付';
$_LANG['label_notonly_hb']          = '允许积分 + 现金支付';
$_LANG['label_hb_line']          = '积分最高支付上限';
$_LANG['label_hd_line']          = '浣豆最高支付上限';

$_LANG['label_instalment_buy']              = '分期租购：';
$_LANG['instalment_ON']                     = '启用';
$_LANG['instalment_add']                    = '添加分期';
$_LANG['instalment_pay_cycle']              = '付款周期';
$_LANG['instalment_num']                    = '期数';
$_LANG['instalment_money_percent']          = '现金占比';
$_LANG['instalment_input_money_percent']    = '请输入现金占比';
$_LANG['instalment_money_unit']             = '现金/期：';
$_LANG['instalment_huanbi_unit']            = '积分/期：';
$_LANG['instalment_all_money']              = '总额：';
$_LANG['instalment_pay_method_all']			= '全款付';
$_LANG['instalment_pay_method_year']		= '年付';
$_LANG['instalment_pay_method_half_year']	= '半年付';
$_LANG['instalment_pay_method_quarter']		= '季付';
$_LANG['instalment_pay_method_month']		= '月付';


/*------------------------------------------------------ */
//-- 关联商品
/*------------------------------------------------------ */

$_LANG['all_goods'] = '可选商品';
$_LANG['link_goods'] = '跟该商品关联的商品';
$_LANG['single'] = '单向关联';
$_LANG['double'] = '双向关联';
$_LANG['all_article'] = '可选文章';
$_LANG['goods_article'] = '跟该商品关联的文章';
$_LANG['top_cat'] = '顶级分类';

/*------------------------------------------------------ */
//-- 组合商品
/*------------------------------------------------------ */

$_LANG['group_goods'] = '该商品的配件';
$_LANG['price'] = '价格';

/*------------------------------------------------------ */
//-- 商品相册
/*------------------------------------------------------ */

$_LANG['img_desc'] = '图片描述';
$_LANG['img_url'] = '上传文件';
$_LANG['img_file'] = '或者输入外部图片/影片链接地址';

/*------------------------------------------------------ */
//-- 关联文章
/*------------------------------------------------------ */
$_LANG['article_title'] = '文章标题';

$_LANG['goods_not_exist'] = '该商品不存在';
$_LANG['goods_not_in_recycle_bin'] = '该商品尚未放入回收站，不能删除';

$_LANG['js_languages']['goods_name_not_null'] = '商品名称不能为空。';
$_LANG['js_languages']['goods_cat_not_null'] = '商品分类必须选择。';
$_LANG['js_languages']['category_cat_not_null'] = '分类名称不能为空';
$_LANG['js_languages']['brand_cat_not_null'] = '品牌名称不能为空';
$_LANG['js_languages']['goods_cat_not_leaf'] = '您选择的商品分类不是底级分类，请选择底级分类。';
$_LANG['js_languages']['shop_price_not_null'] 	= '销售价不能为空。';
$_LANG['js_languages']['shop_price_not_number'] = '销售价不是数值。';
$_LANG['js_languages']['settlement_money_not_null'] 		= '供货价不能为空。';
$_LANG['js_languages']['settlement_money_not_number'] 	= '供货价不是数值。';
$_LANG['js_languages']['hb_line_not_null'] = '积分上限不能为空。';
$_LANG['js_languages']['hb_line_not_number'] = '积分上限不是数值。';
$_LANG['js_languages']['hb_line_invalid'] = '积分上限不得超过商品售价。';
$_LANG['js_languages']['hb_line_zero'] = '请输入大于0的积分上限。';
$_LANG['js_languages']['custom_specification_name_not_null'] = '自定义规格名称不能为空';
$_LANG['js_languages']['custom_specification_values_not_null'] = '自定义规格参数不能为空';
$_LANG['js_languages']['custom_specification_length_not_long'] = '自定义规格限制不可超过10条';
$_LANG['js_languages']['goods_attr_sale_image_not_allowed'] = '缩略图必须是800*800的图片';

$_LANG['js_languages']['select_please'] = '请选择...';
$_LANG['js_languages']['button_add'] = '添加';
$_LANG['js_languages']['button_del'] = '删除';
$_LANG['js_languages']['spec_value_not_null'] = '规格不能为空';
$_LANG['js_languages']['spec_price_not_number'] = '加价不是数字';
$_LANG['js_languages']['market_price_not_number'] = '市场价格不是数字';
$_LANG['js_languages']['goods_number_not_int'] = '商品库存不是整数';
$_LANG['js_languages']['warn_number_not_int'] = '库存警告不是整数';
$_LANG['js_languages']['promote_not_lt'] = '促销开始日期不能大于结束日期';
$_LANG['js_languages']['promote_start_not_null'] = '促销开始时间不能为空';
$_LANG['js_languages']['promote_end_not_null'] = '促销结束时间不能为空';

$_LANG['js_languages']['drop_img_confirm'] = '您确实要删除该图片吗？';
$_LANG['js_languages']['batch_no_on_sale'] = '您确实要将选定的商品下架吗？请填写下架原因（必填）！';
$_LANG['js_languages']['empty_reason'] = '下架原因不得为空';
$_LANG['js_languages']['batch_trash_confirm'] = '您确实要把选中的商品放入回收站吗？';
$_LANG['js_languages']['go_category_page'] = '本页数据将丢失，确认要去商品分类页添加分类吗？';
$_LANG['js_languages']['go_brand_page'] = '本页数据将丢失，确认要去商品品牌页添加品牌吗？';

$_LANG['js_languages']['volume_num_not_null'] = '请输入优惠数量';
$_LANG['js_languages']['volume_num_not_number'] = '优惠数量不是数字';
$_LANG['js_languages']['volume_price_not_null'] = '请输入优惠价格';
$_LANG['js_languages']['volume_price_not_number'] = '优惠价格不是数字';

$_LANG['js_languages']['cancel_color'] = '无样式';
$_LANG['js_languages']['goods_location'] = '货位号不得超过30位字符';
$_LANG['js_languages']['goods_error'] = '货位号为数字、字母、符号';

$_LANG['js_languages']['promote_delivery_guarantee_null'] = '发货保障不为空';
$_LANG['js_languages']['promote_refund_guarantee_null'] = '退货保障不为空';
$_LANG['js_languages']['delivery_time_custom_input_error'] = '自定义时间为数字1到99天';

/* 虚拟卡 */
$_LANG['card'] = '查看虚拟卡信息';
$_LANG['replenish'] = '补货';
$_LANG['batch_card_add'] = '批量补货';
$_LANG['add_replenish'] = '添加虚拟卡卡密';

$_LANG['goods_number_error'] = '商品库存数量错误';
$_LANG['virtual_sales_error'] = '商品虚拟销售数量错误';

/*------------------------------------------------------ */
//-- 货品
/*------------------------------------------------------ */
$_LANG['product'] = '货品';
$_LANG['product_info'] = '货品信息';
$_LANG['specifications'] = '规格';
$_LANG['total'] = '合计：';
$_LANG['add_products'] = '添加货品';
$_LANG['save_products'] = '保存货品成功';
$_LANG['product_id_null'] = '货品id为空';
$_LANG['cannot_found_products'] = '未找到指定货品';
$_LANG['product_batch_del_success'] = '货品批量删除成功';
$_LANG['product_batch_del_failure'] = '货品批量删除失败';
$_LANG['batch_product_add'] = '批量添加';
$_LANG['batch_product_edit'] = '批量编辑';
$_LANG['products_title'] = '商品名称：%s';
$_LANG['products_title_2'] = '货号：%s';
$_LANG['good_shop_price'] = '（商品价格：%d）';
$_LANG['good_goods_sn'] = '（商品货号：%s）';
$_LANG['exist_same_goods_sn'] = '货品货号不允许与产品货号重复';
$_LANG['exist_same_product_sn'] = '货品货号重复';
$_LANG['cannot_add_products'] = '货品添加失败';
$_LANG['exist_same_goods_attr'] = '货品规格属性重复';
$_LANG['cannot_goods_number'] = '此商品存在货品，不能修改商品库存';
$_LANG['not_exist_goods_attr'] = '此商品不存在规格，请为其添加规格';
$_LANG['goods_sn_exists'] = '您输入的货号已存在，请换一个';
$_LANG['operate_time'] = '操作时间';
$_LANG['operate_auth'] = '操作者';
$_LANG['operate_record'] = '操作记录';
$_LANG['goods_status'] = '商品当前状态';

$_LANG['batch_submit'] = '提交';
$_LANG['audit'] = '审核';
$_LANG['submit_audit'] = '提审';
$_LANG['preview'] = '预览';
$_LANG['goods_audit'] = '有解商城管理中心 - 商品审核';
$_LANG['js_languages']['submit_audit'] = '您确定将此商品提交审核吗？';
$_LANG['audit_no_pass'] = '您确定要将此商品审核不通过吗？请输入不通过原因';
$_LANG['audit_pass'] = '您确定要将此商品审核通过吗？';
$_LANG['no_pass_reason'] = '不通过原因：';
$_LANG['no_pass_input'] = '请输入不通过原因';
$_LANG['no_pass'] = '不通过';
$_LANG['pass'] = '通过';
$_LANG['cancle'] = '取消';
$_LANG['hint_on_sale'] = '您确定上架此商品吗？';
$_LANG['hint_no_on_sale'] = '您确定下架此商品吗？请填写下架原因（必填）！';
$_LANG['audit_goods'] = '审核商品信息';
$_LANG['view_goods'] = '查看商品信息';
$_LANG[0] = '草稿中';
$_LANG[1] = '审核中';
$_LANG[2] = '审核通过';
$_LANG[3] = '审核不通过';
$_LANG[4] = '上架中';
$_LANG[5] = '下架中';
$_LANG[9] = '删除';
$_LANG['suppliers_info'] = '商家信息';
$_LANG['suppliers_name'] = '商家名称：';
$_LANG['sum_on_sale'] = '上架商品总数：';
$_LANG['today_wait_audit'] = '当天待审核商品：';
$_LANG['today_audit_pass'] = '当天审核通过商品：';
$_LANG['today_audit_no_pass'] = '当天审核不通过商品：';
$_LANG['sum_audit_pass'] = '累计审核通过商品';
$_LANG['sum_audit_no_pass'] = '累计审核不通过商品';
$_LANG['button_no_audit'] = '保存并提交至草稿';
$_LANG['js_languages']['no_pass_remark'] = '请填写不通过原因';

$_LANG['lab_only_rule'] = '限购规则：';
$_LANG['lab_no_only'] = '不限购';
$_LANG['lab_set_only'] = '设置每日限购数量';
$_LANG['js_languages']['input_int'] = '限购数量:请输入正整数数字';
$_LANG['js_languages']['only_no_empty'] = '请输入限购数量';
$_LANG['js_languages']['only_purchase_error'] = '限购数量不能大于库存数量';

$_LANG['lab_have_borrow'] = '指定债权：';
$_LANG['no_borrow'] = '不指定（即全部用户都有权利购买商品）';
$_LANG['have_borrow'] = '指定债权（仅对指定债权用户拥有购买商品权利）';
$_LANG['lab_company_name'] = '借款企业名称：';
$_LANG['lab_tips_borrow'] = '请输入对应债权的项目ID 仅支持省心计划(一行一个)';
//$_LANG['lab_tips_borrow'] = '请输入对应债权的项目ID【省心计划项目ID为数字】【智选系列项目ID 形如:ZX201705230232】(一行一个)';
$_LANG['js_languages']['error_borrow_pay'] = '指定债权支付方式必须是积分+现金';
$_LANG['js_languages']['no_company_name'] = '请输入借款企业名称';
$_LANG['js_languages']['no_borrow_ids'] = '请输入正确的项目ID';

$_LANG['hh_guest'] = '换换客专区';
$_LANG['join_hh_guest'] = '加入';
$_LANG['no_join_hh_guest'] = '不加入';
$_LANG['mlm_shop_price'] = '商品售价：';
$_LANG['mlm_money_line'] = '积分价格：';
$_LANG['mlm_rebate'] = '返佣金额：';
$_LANG['js_languages']['error_price'] = '请输入正确的价格';
$_LANG['js_languages']['error_shop_price'] = '商品售价不得大于本店售价';
$_LANG['js_languages']['error_money_line'] = '积分价格不得大于积分最高支付上限';

$_LANG['goods_set_stock'] = '库存调整';
$_LANG['edit_stock_ok'] = '库存调整成功';

$_LANG['goods_set_settlement_money'] = '供货价调整';
$_LANG['edit_settlement_money_ok'] = '供货价调整成功';

$_LANG['js_languages']['market_price_error'] = '划线价不得小于本店售价';
$_LANG['js_languages']['market_price_not_null'] = '划线价不得不得为空';

$_LANG['goods_home'] = [
    '高额积分抵扣专区',
    '百元特价专区',
    '精选礼品',
    '夏日爆款',
    '好物回购',
    '食品生鲜',
    '美妆护肤',
    '服饰内衣',
    '家用电器',
    '居家日用',
    '珠宝配饰',
    '箱包鞋靴',
    '以资抵债',
    '新人商品',
    '24hTOP榜单',
 ];






?>
