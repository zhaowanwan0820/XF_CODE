<?php

/**
 * ECSHOP 管理中心菜单数组
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: inc_menu.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$modules['01_certificate_manage']['certificate']        = 'certificate.php?act=list_edit';//授权，绑定矩阵
$modules['01_certificate_manage']['service_market']     = 'service_market.php';//服务市场
$modules['01_certificate_manage']['sms_resource']     = 'sms_resource.php';//短信平台
$modules['01_certificate_manage']['logistic_tracking']  = 'logistic_tracking.php';//云起物流

$modules['02_cat_and_goods']['01_goods_list']       = 'goods.php?act=list';         // 商品列表
$modules['02_cat_and_goods']['02_goods_add']        = 'goods.php?act=add';          // 添加商品
//$modules['02_cat_and_goods']['03_category_list']    = 'category.php?act=list&new=2';      //旧分类
$modules['02_cat_and_goods']['03_new_category_list']    = 'category.php?act=list';//新分类
//$modules['02_cat_and_goods']['05_comment_manage']   = 'comment_manage.php?act=list';
$modules['02_cat_and_goods']['06_goods_brand_list'] = 'brand.php?act=list';
$modules['02_cat_and_goods']['08_goods_type']       = 'goods_type.php?act=manage';
$modules['02_cat_and_goods']['11_goods_trash']      = 'goods.php?act=trash';        // 商品回收站
$modules['02_cat_and_goods']['12_batch_pic']        = 'picture_batch.php';
$modules['02_cat_and_goods']['13_batch_add']        = 'goods_batch.php?act=add';    // 商品批量上传
$modules['02_cat_and_goods']['14_goods_export']     = 'goods_export.php?act=goods_export';
$modules['02_cat_and_goods']['15_batch_edit']       = 'goods_batch.php?act=select'; // 商品批量修改
$modules['02_cat_and_goods']['16_goods_script']     = 'gen_goods_script.php?act=setup';
$modules['02_cat_and_goods']['17_tag_manage']       = 'tag_manage.php?act=list';
$modules['02_cat_and_goods']['50_virtual_card_list']   = 'goods.php?act=list&extension_code=virtual_card';
$modules['02_cat_and_goods']['51_virtual_card_add']    = 'goods.php?act=add&extension_code=virtual_card';
$modules['02_cat_and_goods']['52_virtual_card_change'] = 'virtual_card.php?act=change';
$modules['02_cat_and_goods']['goods_auto']             = 'goods_auto.php?act=list';

$modules['03_mlm']['01_mlm_activity_list']          = 'mlm.php?act=activityList';
$modules['03_mlm']['02_mlm_goods_list']             = 'mlm.php?act=goodsList';
$modules['03_mlm']['03_mlm_dashboard']              = 'mlm.php?act=dashboard';


$modules['04_order']['02_order_list']               = 'order.php?act=list';
$modules['04_order']['03_order_query']              = 'order.php?act=order_query';
$modules['04_order']['04_merge_order']              = 'order.php?act=merge';
$modules['04_order']['05_edit_order_print']         = 'order.php?act=templates';
$modules['04_order']['06_undispose_booking']        = 'goods_booking.php?act=list_all';
//$modules['04_order']['07_repay_application']        = 'repay.php?act=list_all';
$modules['04_order']['08_add_order']                = 'order.php?act=add';
$modules['04_order']['09_delivery_order']           = 'order.php?act=delivery_list';
$modules['04_order']['10_back_order']               = 'order.php?act=back_list';
$modules['04_order']['11_import_invoice']           = 'order.php?act=import_invoice_list';
$modules['04_order']['12_comment_manage']           = 'comment_manage.php?act=list';
$modules['04_order']['13_debt_rollback']            = 'order.php?act=debt_rollback_list';
$modules['04_order']['14_order_finace']             = 'order.php?act=order_finace';
$modules['04_order']['15_goods_comment_set']		= 'comment_manage.php?act=goods_comment_set';



$modules['05_banner']['ad_position']                = 'ad_position.php?act=list';
$modules['05_banner']['ad_list']                    = 'ads.php?act=list';

$modules['06_stats']['flow_stats']                  = 'flow_stats.php?act=view';
$modules['06_stats']['searchengine_stats']          = 'searchengine_stats.php?act=view';
$modules['06_stats']['z_clicks_stats']              = 'adsense.php?act=list';
$modules['06_stats']['report_guest']                = 'guest_stats.php?act=list';
$modules['06_stats']['report_order']                = 'order_stats.php?act=list';
$modules['06_stats']['report_sell']                 = 'sale_general.php?act=list';
$modules['06_stats']['sale_list']                   = 'sale_list.php?act=list';
$modules['06_stats']['sell_stats']                  = 'sale_order.php?act=goods_num';
$modules['06_stats']['report_users']                = 'users_order.php?act=order_num';
$modules['06_stats']['visit_buy_per']               = 'visit_sold.php?act=list';

$modules['07_content']['03_article_list']           = 'article.php?act=list';
$modules['07_content']['02_articlecat_list']        = 'articlecat.php?act=list';
$modules['07_content']['vote_list']                 = 'vote.php?act=list';
$modules['07_content']['article_auto']              = 'article_auto.php?act=list';
//$modules['07_content']['shop_help']                 = 'shophelp.php?act=list_cat';
//$modules['07_content']['shop_info']                 = 'shopinfo.php?act=list';


$modules['08_members']['03_users_list']             = 'users.php?act=list';
$modules['08_members']['04_users_add']              = 'users.php?act=add';
$modules['08_members']['05_user_rank_list']         = 'user_rank.php?act=list';
$modules['08_members']['06_list_integrate']         = 'integrate.php?act=list';
$modules['08_members']['08_unreply_msg']            = 'user_msg.php?act=list_all';
$modules['08_members']['09_user_account']           = 'user_account.php?act=list';
$modules['08_members']['10_user_account_manage']    = 'user_account_manage.php?act=list';
$modules['08_members']['11_account_change_audit']   = 'account_change_audit.php?act=list';

$modules['10_priv_admin']['admin_logs']             = 'admin_logs.php?act=list';
$modules['10_priv_admin']['admin_list']             = 'privilege.php?act=list';
$modules['10_priv_admin']['admin_role']             = 'role.php?act=list';
$modules['10_priv_admin']['agency_list']            = 'agency.php?act=list';
//$modules['10_priv_admin']['suppliers_list']         = 'suppliers.php?act=list'; // 供货商

$modules['11_system']['01_shop_config']             = 'shop_config.php?act=list_edit';
// $modules['11_system']['shop_authorized']             = 'license.php?act=list_edit';
$modules['11_system']['02_payment_list']            = 'payment.php?act=list';
$modules['11_system']['03_shipping_list']           = 'shipping.php?act=list';
$modules['11_system']['04_mail_settings']           = 'shop_config.php?act=mail_settings';
$modules['11_system']['05_area_list']               = 'area_manage.php?act=list';
//$modules['11_system']['06_plugins']                 = 'plugins.php?act=list';
$modules['11_system']['07_cron_schcron']            = 'cron.php?act=list';
$modules['11_system']['08_friendlink_list']         = 'friend_link.php?act=list';
$modules['11_system']['sitemap']                    = 'sitemap.php';
$modules['11_system']['check_file_priv']            = 'check_file_priv.php?act=check';
$modules['11_system']['captcha_manage']             = 'captcha_manage.php?act=main';
$modules['11_system']['ucenter_setup']              = 'integrate.php?act=setup&code=ucenter';
//$modules['11_system']['flashplay']                  = 'flashplay.php?act=list';
$modules['11_system']['navigator']                  = 'navigator.php?act=list';
$modules['11_system']['file_check']                 = 'filecheck.php';
//$modules['11_system']['fckfile_manage']             = 'fckfile_manage.php?act=list';
$modules['11_system']['021_reg_fields']             = 'reg_fields.php?act=list';


$modules['12_template']['02_template_select']       = 'template.php?act=list';
$modules['12_template']['03_template_setup']        = 'template.php?act=setup';
$modules['12_template']['04_template_library']      = 'template.php?act=library';
$modules['12_template']['05_edit_languages']        = 'edit_languages.php?act=list';
$modules['12_template']['06_template_backup']       = 'template.php?act=backup_setting';
$modules['12_template']['mail_template_manage']     = 'mail_template.php?act=list';


$modules['13_backup']['02_db_manage']               = 'database.php?act=backup';
$modules['13_backup']['03_db_optimize']             = 'database.php?act=optimize';
$modules['13_backup']['04_sql_query']               = 'sql.php?act=main';
//$modules['13_backup']['05_synchronous']             = 'integrate.php?act=sync';
$modules['13_backup']['convert']                    = 'convert.php?act=main';
$modules['13_backup']['clear']                      = 'database.php?act=clear';


//$modules['14_sms']['02_sms_my_info']                = 'sms.php?act=display_my_info';
$modules['14_sms']['03_sms_send']                   = 'sms.php?act=display_send_ui';
$modules['14_sms']['04_sms_sign']                   = 'sms.php?act=sms_sign';
//$modules['14_sms']['04_sms_charge']                 = 'sms.php?act=display_charge_ui';
//$modules['14_sms']['05_sms_send_history']           = 'sms.php?act=display_send_history_ui';
//$modules['14_sms']['06_sms_charge_history']         = 'sms.php?act=display_charge_history_ui';

$modules['15_rec']['affiliate']                     = 'affiliate.php?act=list';
$modules['15_rec']['affiliate_ck']                  = 'affiliate_ck.php?act=list';

$modules['16_email_manage']['email_list']           = 'email_list.php?act=list';
$modules['16_email_manage']['magazine_list']        = 'magazine_list.php?act=list';
$modules['16_email_manage']['attention_list']       = 'attention_list.php?act=list';
$modules['16_email_manage']['view_sendlist']        = 'view_sendlist.php?act=list';

$modules['18_lead_manage']['banner_mobile']        = 'mobile_setting.php?act=list';//移动端banner设置
$modules['18_lead_manage']['lead']        = 'lead.php?act=list';//H5店铺二维码
$modules['18_lead_manage']['leancloud']        = 'leancloud.php?act=list';//云推送管理
$modules['18_lead_manage']['mobile_setting']        = 'ecmobile_setting.php?act=list';//移动版应用配置
$modules['18_lead_manage']['h5_setting']        = 'h5_setting.php?act=list';//移动版应用配置
$modules['18_lead_manage']['wxa_setting']        = 'wxa_setting.php?act=list';//小程序应用配置


$modules['19_suppliers_manage']['suppliers_list']               = 'suppliers.php?act=list';//商家管理
$modules['19_suppliers_manage']['02_suppliers_account_list']    = 'suppliers.php?act=accountList';//商家账户列表
$modules['19_suppliers_manage']['03_suppliers_account']         = 'suppliers.php?act=account';//商家账户详情
$modules['19_suppliers_manage']['04_suppliers_shop_info']       = 'suppliers.php?act=show';//商家店铺详情
$modules['19_suppliers_manage']['05_multi_statement_list']      = 'suppliers.php?act=multiStatementList';//商家店铺详情

// $modules['19_suppliers_manage']['06_printer_list']      = 'suppliers.php?act=printer';//打印机管理
// $modules['19_suppliers_manage']['07_client_code_list']      = 'suppliers.php?act=clientCodeList';//客户号列表
// $modules['19_suppliers_manage']['08_business_address']      = 'suppliers.php?act=businessAddress';//商家地址信息
$modules['19_suppliers_manage']['06_suppliers_shipping_tpl_list']	= 'suppliers.php?act=shippingTplList';//商家运费模板
$modules['19_suppliers_manage']['09_suppliers_surplus_transfer']	= 'suppliers.php?act=suppliersSurplusTransfer';//商家运费模板


$modules['20_activity_manage']['activity_list']        = 'activity.php?act=list';//运营活动管理
$modules['20_activity_manage']['activity_coupon_list']        = 'activity.php?act=coupon_list';//商家优惠券管理
$modules['20_activity_manage']['seckill_activity_list']        = 'activity.php?act=seckill_activity_list';//秒杀活动管理
$modules['20_activity_manage']['first_red_cash_back']   		= 'activity.php?act=first_red_cash_back';	// 首单红包返现

$modules['21_work_order_manage']['work_order_list']    = 'work_order.php?act=list';//工单

$modules['22_mlm_manage']['mlm_activity_list']    = 'mlm.php?act=activity_list';//分销管理
//$modules['03_promotion']['02_snatch_list']          = 'snatch.php?act=list';
$modules['23_promotion']['04_bonustype_list']       = 'bonus.php?act=list';
$modules['23_promotion']['06_pack_list']            = 'pack.php?act=list';
$modules['23_promotion']['07_card_list']            = 'card.php?act=list';
$modules['23_promotion']['08_group_buy']            = 'group_buy.php?act=list';
$modules['23_promotion']['09_topic']                = 'topic.php?act=list';
$modules['23_promotion']['10_auction']              = 'auction.php?act=list';
$modules['23_promotion']['12_favourable']           = 'favourable.php?act=list';
$modules['23_promotion']['13_wholesale']            = 'wholesale.php?act=list';
$modules['23_promotion']['14_package_list']         = 'package.php?act=list';
//$modules['03_promotion']['ebao_commend']            = 'ebao_commend.php?act=list';
$modules['23_promotion']['15_exchange_goods']       = 'exchange_goods.php?act=list';

$modules['22_home_goods']['goods_cat_plate']    = 'goods_home.php?act=home_tags_list';//首页专区列表

if($_SESSION['admin_type']=='0'){
	$modules['25_wd_platform']['wd_add_deit']       = 'wd_platform.php?act=wd_add_deit';//网贷平台管理  - 添加编辑平台管理
	$modules['25_wd_platform']['wd_use_person']       = 'wd_platform.php?act=wd_use_person';//网贷平台管理  - 出借人
	$modules['25_wd_platform']['wd_use_detail']       = 'wd_platform.php?act=wd_use_detail';//网贷平台管理  - 代付明细
}

if($_SESSION['admin_type']=='2'){
    $modules['26_user_wd_platform']['01_user_wd_index']       = 'user_wd_platform.php?act=user_wd_index';//网贷平台用户 -浣豆账户明细
	$modules['26_user_wd_platform']['02_user_wd_add_deit']    = 'user_wd_platform.php?act=user_wd_add_deit';//网贷平台用户 -浣豆账户明细
	$modules['26_user_wd_platform']['03_user_wd_use_detail']  = 'user_wd_platform.php?act=user_wd_use_detail';//网贷平台用户 - 代付明细
	$modules['26_user_wd_platform']['04_user_wd_use_person']  = 'user_wd_platform.php?act=user_wd_use_person';//网贷平台用户 - 出借人
}


$modules['27_admin_profile']['edit_password'] = 'profile.php?act=reset_password';//个人中心



?>
