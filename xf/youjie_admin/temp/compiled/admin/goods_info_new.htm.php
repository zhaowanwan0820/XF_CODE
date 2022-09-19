<!-- $Id: goods_info.htm 17126 2010-04-23 10:30:26Z liuhui $ -->
<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,selectzone.js,colorselector.js')); ?>
<script type="text/javascript" src="../js/calendar.php?lang=<?php echo $this->_var['cfg_lang']; ?>"></script>
<link href="../js/calendar/calendar.css" rel="stylesheet" type="text/css" />

<!--ueditor js -- start -->
<script type="text/javascript" charset="utf-8" src="../../includes/ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="../../includes/ueditor/ueditor.all.min.js"> </script>
<!--建议手动加在语言，避免在ie下有时因为加载语言失败导致编辑器加载失败-->
<!--这里加载的语言文件会覆盖你在配置项目里添加的语言类型，比如你在配置项目里配置的是英文，这里加载的中文，那最后就是中文-->
<script type="text/javascript" charset="utf-8" src="../../includes/ueditor/lang/zh-cn/zh-cn.js"></script>
<!--ueditor js -- end -->
<link href="/admin/styles/jquery.searchableSelect.css" rel="stylesheet" type="text/css">
<link href="/admin/styles/goods_info_new.css?v=<?php echo $this->_var['static_version']; ?>" rel="stylesheet" type="text/css" />

<?php if ($this->_var['warning']): ?>
<ul style="padding:0; margin: 0; list-style-type:none; color: #CC0000;">
  <li style="border: 1px solid #CC0000; background: #FFFFCC; padding: 10px; margin-bottom: 5px;" ><?php echo $this->_var['warning']; ?></li>
</ul>
<?php endif; ?>

<!-- start goods form -->
<div class="tab-div">
  <?php if ($this->_var['is_audit']): ?>
  <div id="suppliers-div">
    <h2 style="margin-left:5px;"><?php echo $this->_var['lang']['suppliers_info']; ?></h2>
    <table style="font-weight: bold;width: 100%;font-size: 15px" cellpadding="6">
      <tr>
        <td><?php echo $this->_var['lang']['suppliers_name']; ?><?php echo $this->_var['suppliers_info']['suppliers_name']; ?></td>
        <td><?php echo $this->_var['lang']['today_wait_audit']; ?><span style="color: red"><?php echo $this->_var['suppliers_info']['today_wait_audit']; ?></span>个</td>
        <td><?php echo $this->_var['lang']['sum_audit_pass']; ?><?php echo $this->_var['suppliers_info']['sum_audit_pass']; ?>个</td>
      </tr>
      <tr>
        <td><?php echo $this->_var['lang']['sum_on_sale']; ?><?php echo $this->_var['suppliers_info']['sum_on_sale']; ?>个</td>
        <td><?php echo $this->_var['lang']['today_audit_pass']; ?><span style="color: red"><?php echo $this->_var['suppliers_info']['today_audit_pass']; ?></span>个</td>
        <td><?php echo $this->_var['lang']['sum_audit_no_pass']; ?><?php echo $this->_var['suppliers_info']['sum_audit_no_pass']; ?>个</td>
      </tr>
      <tr>
        <td></td>
        <td><?php echo $this->_var['lang']['today_audit_no_pass']; ?><span style="color: red"><?php echo $this->_var['suppliers_info']['today_audit_no_pass']; ?></span>个</td>
        <td></td>
      </tr>
    </table>
  </div>
  <?php endif; ?>
    <!-- tab bar -->
    <div id="tabbar-div">
      <p>
        <span class="tab-front" id="general-tab">基础信息</span>
        <!--<span class="tab-back" id="properties-tab">类目属性</span>-->
        <span class="tab-back" id="custom_specification-tab">自定义规格</span>
        <span class="tab-back" id="properties_sale-tab">销售属性</span>
        <span class="tab-back" id="gallery-tab">商品相册</span>
        <span class="tab-back" id="detail-tab">图文详情</span>
      </p>
    </div>

    <!-- tab body -->
    <div id="tabbody-div">
      <form enctype="multipart/form-data" action="" method="post" name="theForm" >
        <!-- 上传文件打下 -->
        <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
        <!-- 基本信息 -->
        <table width="90%" id="general-table" align="center" class="gk-table">
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_goods_name']; ?></td>
              <td><input type="text" name="goods_name" value="<?php echo htmlspecialchars($this->_var['goods']['goods_name']); ?>" style="float:left;color:<?php echo $this->_var['goods_name_color']; ?>;" size="30" />
            <?php echo $this->_var['lang']['require_field']; ?></td>
          </tr>
          <tr>
            <td class="label">
            <a href="javascript:showNotice('noticeGoodsSN');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a> <?php echo $this->_var['lang']['lab_goods_sn']; ?> </td>
            <td><input type="text" name="goods_sn" value="<?php echo htmlspecialchars($this->_var['goods']['goods_sn']); ?>" size="20" onblur="checkGoodsSn(this.value,'<?php echo $this->_var['goods']['goods_id']; ?>')" /><span id="goods_sn_notice"></span><br />
            <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="noticeGoodsSN"><?php echo $this->_var['lang']['notice_goods_sn']; ?></span></td>
          </tr>
          <tr>
              <td class="label"><?php echo $this->_var['lang']['lab_goods_cat']; ?></td>
              <td>
                  <select name="" id="cat_id_1" onchange="region.changed(this, 1, 'cat_id_2')">
                      <option value="0"><?php echo $this->_var['lang']['select_please']; ?></option>
                      <?php $_from = $this->_var['category_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'category');if (count($_from)):
    foreach ($_from AS $this->_var['category']):
?>
                      <option value="<?php echo $this->_var['category']['cat_id']; ?>" <?php if (in_array ( $this->_var['category']['cat_id'] , $this->_var['category_ids'] )): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['category']['cat_name']; ?></option>
                      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                  </select>
                  <select name="" id="cat_id_2" onchange="region.changed(this, 1, 'cat_id')">
                      <option value="0"><?php echo $this->_var['lang']['select_please']; ?></option>
                      <?php $_from = $this->_var['category_list_2']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'category');if (count($_from)):
    foreach ($_from AS $this->_var['category']):
?>
                      <option value="<?php echo $this->_var['category']['cat_id']; ?>" <?php if (in_array ( $this->_var['category']['cat_id'] , $this->_var['category_ids'] )): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['category']['cat_name']; ?></option>
                      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                  </select>
                  <select name="cat_id" id="cat_id" onchange="getAttrList(<?php echo $this->_var['goods']['goods_id']; ?>)">
                      <option value="0"><?php echo $this->_var['lang']['select_please']; ?></option>
                      <?php $_from = $this->_var['category_list_3']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'category');if (count($_from)):
    foreach ($_from AS $this->_var['category']):
?>
                      <option value="<?php echo $this->_var['category']['cat_id']; ?>" <?php if (in_array ( $this->_var['category']['cat_id'] , $this->_var['category_ids'] )): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['category']['cat_name']; ?></option>
                      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                  </select>
                  <?php echo $this->_var['lang']['require_field']; ?>
              </td>
          </tr>
          <!--<tr style="display: none;">
            <td class="label"><?php echo $this->_var['lang']['lab_other_cat']; ?></td>
            <td>
              <input type="button" value="<?php echo $this->_var['lang']['add']; ?>" onclick="addOtherCat(this.parentNode)" class="button btn-def" />
              <?php $_from = $this->_var['goods']['other_cat']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'cat_id');if (count($_from)):
    foreach ($_from AS $this->_var['cat_id']):
?>
              <select name="other_cat[]"><option value="0"><?php echo $this->_var['lang']['select_please']; ?></option><?php echo $this->_var['other_cat_list'][$this->_var['cat_id']]; ?></select>
              <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
            </td>
          </tr>-->
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_goods_brand']; ?></td>
            <td class="search-table search-table-2"><select name="brand_id" onchange="hideBrandDiv()" ><option value="0"><?php echo $this->_var['lang']['select_please']; ?><?php echo $this->html_options(array('options'=>$this->_var['brand_list'],'selected'=>$this->_var['goods']['brand_id'])); ?></select>
              <?php if ($this->_var['is_add'] && $this->_var['admin_type'] == 0): ?>
              <button type="button" class="btn btn-def " onclick="rapidBrandAdd()"><?php echo $this->_var['lang']['rapid_add_brand']; ?></button>
              <span id="brand_add" style="display:none;">
              <input type="text" class="text" size="15" name="addedBrandName" />
               <button type="button" class="btn btn-def " onclick="addBrand()" title="<?php echo $this->_var['lang']['button_submit']; ?>"><?php echo $this->_var['lang']['button_submit']; ?></button>
               <button type="button" class="btn btn-def " onclick="return goBrandPage()" title="<?php echo $this->_var['lang']['brand_manage']; ?>"><?php echo $this->_var['lang']['brand_manage']; ?></button>
               <button type="button" class="btn btn-def " onclick="hideBrandDiv()" title="<?php echo $this->_var['lang']['hide']; ?>"><<</button>
               </span>
               <?php endif; ?>
            </td>
          </tr>
         <?php if ($this->_var['suppliers_exists'] == 1 && $this->_var['admin_type'] == 0): ?>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['label_suppliers']; ?></td>
            <td class="search-table-3"><select name="suppliers_id" id="suppliers_id">
        <option value="0"><?php echo $this->_var['lang']['suppliers_no']; ?></option>
        <?php echo $this->html_options(array('options'=>$this->_var['suppliers_list_name'],'selected'=>$this->_var['goods']['suppliers_id'])); ?>
      </select></td>
          </tr>
         <?php endif; ?>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_shop_price']; ?></td>
            <td><input type="text" class="shop-price" name="shop_price" value="<?php echo $this->_var['goods']['shop_price']; ?>" size="20" onchange="priceSetted()"/>
            <input type="button" class="btn btn-def" value="<?php echo $this->_var['lang']['compute_by_mp']; ?>" onclick="marketPriceSetted()" />
            <?php echo $this->_var['lang']['require_field']; ?></td>
          </tr>
          <!-- 混合支付 -->
         <tr>
            <td class="label">权益支付上限：</td>
            <td>
                  <input type="text" name="hb_line" value="<?php if ($this->_var['goods']['money_line'] >= 0): ?><?php echo $this->_var['goods']['money_line']; ?><?php endif; ?>" onblur="computed_hb()">
                  <span id="cash_num"></span>
                <p>
                    <input type="radio" name="token_type" value="0" onclick="computed_hb()" <?php if ($this->_var['goods']['token_type'] == 0): ?>checked <?php endif; ?>>不限
                    <input type="radio" name="token_type" value="1" onclick="computed_hb()" <?php if ($this->_var['goods']['token_type'] == 1): ?>checked <?php endif; ?>>仅权益币
                    <input type="radio" name="token_type" value="2"  onclick="computed_hb()" <?php if ($this->_var['goods']['token_type'] == 2): ?>checked <?php endif; ?> >仅浣豆
                </p>
             <!--  <div class="only-hb">
                <input type="radio" name="pay_method" value="-1" onclick="showInput()" <?php if (! $this->_var['goods']['money_line']): ?> checked="checked" <?php else: ?> checked="checked" <?php endif; ?>><?php echo $this->_var['lang']['label_only_hb']; ?>
              </div>
              <div class="notonly-hb">
                <input type="radio" name="pay_method" value="0" onclick="showInput()" <?php if ($this->_var['goods']['money_line'] && $this->_var['goods']['money_line'] != - 1): ?> checked="checked" <?php endif; ?>><?php echo $this->_var['lang']['label_notonly_hb']; ?>
                <div class="hb_line none">
                  <p><?php echo $this->_var['lang']['label_hb_line']; ?></p>
                  <?php if ($this->_var['goods']['money_line'] < 0): ?>
                  <input type="text" name="hb_line" value="" onblur="computed_hb()">
                  <?php else: ?>
                  <input type="text" name="hb_line" value="<?php echo $this->_var['goods']['money_line']; ?>" onblur="computed_hb()">
                  <?php endif; ?>
                  <span id="cash_num"></span>
                </div>
              </div> -->
               <input type="hidden" name="money_line">
            </td>
           </tr>

         <!-- <?php if ($this->_var['user_rank_list']): ?>
          <tr>
            <td class="label"><a href="javascript:showNotice('noticeUserPrice');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a><?php echo $this->_var['lang']['lab_user_price']; ?></td>
            <td>
              <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'user_rank');if (count($_from)):
    foreach ($_from AS $this->_var['user_rank']):
?>
              <?php echo $this->_var['user_rank']['rank_name']; ?><span id="nrank_<?php echo $this->_var['user_rank']['rank_id']; ?>"></span><input type="text" id="rank_<?php echo $this->_var['user_rank']['rank_id']; ?>" name="user_price[]" value="<?php echo empty($this->_var['member_price_list'][$this->_var['user_rank']['rank_id']]) ? '-1' : $this->_var['member_price_list'][$this->_var['user_rank']['rank_id']]; ?>" onkeyup="if(parseInt(this.value)<-1){this.value='-1';};set_price_note(<?php echo $this->_var['user_rank']['rank_id']; ?>)" size="8" />
              <input type="hidden" name="user_rank[]" value="<?php echo $this->_var['user_rank']['rank_id']; ?>" />
              <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
              <br />
              <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="noticeUserPrice"><?php echo $this->_var['lang']['notice_user_price']; ?></span>
            </td>
          </tr>
          <?php endif; ?>-->

          <!--鍟嗗搧浼樻儬浠锋牸-->
        <!--  <tr>
            <td class="label"><a href="javascript:showNotice('volumePrice');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a><?php echo $this->_var['lang']['lab_volume_price']; ?></td>
            <td>
              <table width="100%" id="tbody-volume" align="center">
                <?php $_from = $this->_var['volume_price_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'volume_price');$this->_foreach['volume_price_tab'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['volume_price_tab']['total'] > 0):
    foreach ($_from AS $this->_var['volume_price']):
        $this->_foreach['volume_price_tab']['iteration']++;
?>
                <tr>
                  <td>
                     <?php if ($this->_foreach['volume_price_tab']['iteration'] == 1): ?>
                       <a href="javascript:;" onclick="addVolumePrice(this)">[+]</a>
                     <?php else: ?>
                       <a href="javascript:;" onclick="removeVolumePrice(this)">[-]</a>
                     <?php endif; ?>
                     <?php echo $this->_var['lang']['volume_number']; ?> <input type="text" name="volume_number[]" size="8" value="<?php echo $this->_var['volume_price']['number']; ?>"/>
                     <?php echo $this->_var['lang']['volume_price']; ?> <input type="text" name="volume_price[]" size="8" value="<?php echo $this->_var['volume_price']['price']; ?>"/>
                  </td>
                </tr>
                <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
              </table>
              <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="volumePrice"><?php echo $this->_var['lang']['notice_volume_price']; ?></span>
            </td>
          </tr>-->
          <!--鍟嗗搧浼樻儬浠锋牸 end -->

          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_market_price']; ?></td>
            <td><input type="text" name="market_price" value="<?php echo $this->_var['goods']['market_price']; ?>" size="20" />
              <input type="button" class="btn btn-def" value="<?php echo $this->_var['lang']['integral_market_price']; ?>" onclick="integral_market_price()" />
            </td>
          </tr>

          <!-- 分期 -->
          <tr class="instalment">
            <td class="label"><?php echo $this->_var['lang']['label_instalment_buy']; ?></td>
            <td>
              <div class="instalment-content">
                <div class="instalment-header">
                  <input class="is-instalment-selector" id="isInstalmentCheckbox" name="isInstalmentCheckbox" type="checkbox" <?php if ($this->_var['goods']['is_instalment'] == 1): ?>checked="checked"<?php endif; ?>>
                  <input class="is-instalment" type="hidden" name="is_instalment" value="<?php echo $this->_var['goods']['is_instalment']; ?>">
                  <label for="isInstalmentCheckbox" style="margin-left: 5px"><?php echo $this->_var['lang']['instalment_ON']; ?></label>
                  <a class="instalment-add" onclick="addInstalment(this)" style="margin-left: 20px;color: #41a5e1;display:<?php if ($this->_var['goods']['is_instalment'] == 1): ?>block;<?php else: ?>none;<?php endif; ?>"><?php echo $this->_var['lang']['instalment_add']; ?></a>
                </div>
                <div class="instalment-item-list">
                  <?php $_from = $this->_var['instalments']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('i', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['i'] => $this->_var['item']):
?>
                  <div class="each-instalment-item">
                    <span><?php echo $this->_var['lang']['instalment_pay_cycle']; ?></span>
                    <select class="instalment-method" name="instalments[<?php echo $this->_var['i']; ?>][method]" value="<?php echo $this->_var['item']['method']; ?>" onchange="instalmentMethodChange(event)">
                      <option value="">请选择</option>
                      <option value="5" <?php if ($this->_var['item']['method'] == 5): ?>selected="selected"<?php endif; ?>>全款付</option>
                      <option value="1" <?php if ($this->_var['item']['method'] == 1): ?>selected="selected"<?php endif; ?>>年付</option>
                      <option value="2" <?php if ($this->_var['item']['method'] == 2): ?>selected="selected"<?php endif; ?>>半年付</option>
                      <option value="3" <?php if ($this->_var['item']['method'] == 3): ?>selected="selected"<?php endif; ?>>季付</option>
                      <option value="4" <?php if ($this->_var['item']['method'] == 4): ?>selected="selected"<?php endif; ?>>月付</option>
                    </select>
                    <span><?php echo $this->_var['lang']['instalment_num']; ?></span>
                    <input class="instalment-num" <?php if ($this->_var['item']['num'] == 1 && $this->_var['item']['method'] == 5): ?>readonly="readonly"<?php endif; ?> type="number" value="<?php echo $this->_var['item']['num']; ?>" placeholder="请输入2-48的整数" name="instalments[<?php echo $this->_var['i']; ?>][num]" oninput="instalNumInput(event, 1)" onblur="instalmentChanged(event)" />
                    <?php if ($this->_var['view'] == 1): ?>
                    <span class="set-ins" onclick="setInstalment(event)" <?php if ($this->_var['item']['num'] == 1 && $this->_var['item']['method'] == 5): ?>style="display: none;"<?php endif; ?>>查看分期金额</span>
                    <?php else: ?>
                    <a class="del-ins" href="javascript:void(0);" onclick="delInstalment(event)">删除</a>
                    <a class="set-ins" href="javascript:void(0);" onclick="setInstalment(event)" <?php if ($this->_var['item']['num'] == 1 && $this->_var['item']['method'] == 5): ?>style="display: none;"<?php endif; ?>>设定分期金额</a>
                    <?php endif; ?>
                    <input class="instalment-pay-plan" type="hidden" value='<?php echo $this->_var['item']['payment_plan']; ?>' name="instalments[<?php echo $this->_var['i']; ?>][payment_plan]" />
                    <span class="instalments-icon ok">
                      <img class="icon-yes" src="images/yes.svg" width="16">
                      <img class="icon-no" src="images/no.svg" width="16">
                    </span>
                  </div>
                  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </div>
              </div>
            </td>
          </tr>

          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_goods_location']; ?></td>
            <td><input type="text" name="goods_location" value="<?php echo $this->_var['goods']['goods_location']; ?>" placeholder="<?php echo $this->_var['lang']['lab_goods_location_default']; ?>" onblur="checkLoca()" size="20"/></td>
          </tr>
            <?php if ($this->_var['set_borrow']): ?>
            <tr style="margin-top: 1%">
                <td class="label"><?php echo $this->_var['lang']['lab_have_borrow']; ?></td>
                <td>
                    <div style="margin-top: 1%"><input type="radio" name="have_borrow" value="0" <?php if (! $this->_var['goods']['borrow_ids']): ?>checked<?php endif; ?> onclick="showBorrow()"><?php echo $this->_var['lang']['no_borrow']; ?><br></div>
                    <div style="margin-top: 1%"><input type="radio" name="have_borrow" value="1" <?php if ($this->_var['goods']['borrow_ids']): ?>checked<?php endif; ?> onclick="showBorrow()"><?php echo $this->_var['lang']['have_borrow']; ?></div>
                    <div id="have-div" style="margin-left: 1%;display: none">
                        <div style="margin-top: 1%"><?php echo $this->_var['lang']['lab_company_name']; ?><input type="text" name="company_name" id="company_name" value="<?php echo $this->_var['goods']['company_name']; ?>"></div>
                        <div style="margin-top: 3px"><?php echo $this->_var['lang']['lab_tips_borrow']; ?><br></div>
                        <textarea name="borrow_ids" id="borrow_ids"  cols="30" rows="10"><?php echo $this->_var['goods']['borrow_ids']; ?></textarea>
                    </div>
                </td>
            </tr>
            <?php endif; ?>

            <!--老版本换换客-->
            <!--<?php if ($this->_var['admin_type'] == 0): ?>-->
            <!--<tr style="margin-top: 1%">-->
                <!--<td class="label"><?php echo $this->_var['lang']['hh_guest']; ?></td>-->
                <!--<td>-->
                    <!--<input type="radio" name="hh_guest" value="1" onclick="showHhguest()" <?php if ($this->_var['goods']['mlm_is_on_sale']): ?>checked<?php endif; ?>><span style="font-weight: bold"><?php echo $this->_var['lang']['join_hh_guest']; ?></span>-->
                    <!--<input type="radio" name="hh_guest" value="0" onclick="showHhguest()" <?php if (! $this->_var['goods']['mlm_is_on_sale']): ?>checked<?php endif; ?>><span style="font-weight: bold"><?php echo $this->_var['lang']['no_join_hh_guest']; ?></span>-->
                    <!--<div id="hh-guest-div" style="border:1px solid #f0e7df;display: none;width: 40%;padding: 1%" >-->
                        <!--<span style="font-weight: bold"><?php echo $this->_var['lang']['mlm_shop_price']; ?></span><input type="text" name="mlm_shop_price" id="mlm_shop_price" value="<?php echo $this->_var['goods']['mlm_shop_price']; ?>" onchange="mlmShopprice()" class="mlm-price"><span id="copy_mlm_shop_price" class="copy-mlm-price">￥<?php echo $this->_var['goods']['mlm_shop_price']; ?></span><br>-->
                        <!--<span style="font-weight: bold"><?php echo $this->_var['lang']['mlm_money_line']; ?></span><input type="text" name="mlm_money_line" id="mlm_money_line" value="<?php echo $this->_var['goods']['mlm_money_line']; ?>" onchange="mlmMoneyline()" class="mlm-price"><span id="copy_mlm_money_line" class="copy-mlm-price">￥<?php echo $this->_var['goods']['mlm_money_line']; ?></span><br>-->
                        <!--<span style="font-weight: bold"><?php echo $this->_var['lang']['mlm_rebate']; ?></span><input type="text" style="background-color: #f0e7df" name="rebate" id="rebate" value="<?php echo $this->_var['goods']['rebate']; ?>" readonly  class="mlm-price"><span id="copy_rebate" class="copy-mlm-price">￥<?php echo $this->_var['goods']['rebate']; ?></span><br></span><br>-->
                        <!--<div>-->
                            <!--<input type="radio" name="mlm_is_on_sale" value="1" <?php if ($this->_var['goods']['mlm_is_on_sale']): ?>checked<?php endif; ?>><span style="font-weight: bold"><?php echo $this->_var['lang']['on_sale']; ?></span>-->
                            <!--<input type="radio" name="mlm_is_on_sale" value="0" <?php if (! $this->_var['goods']['mlm_is_on_sale']): ?>checked<?php endif; ?>><span style="font-weight: bold"><?php echo $this->_var['lang']['not_on_sale']; ?></span>-->
                        <!--</div>-->
                    <!--</div>-->
                <!--</td>-->
            <!--</tr>-->
            <!--<?php endif; ?>-->

         <!-- <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_virtual_sales']; ?></td>
            <td><input type="text" name="virtual_sales" value="<?php echo $this->_var['goods']['virtual_sales']; ?>" size="20" />
            </td>
          </tr>-->
          <!--<tr>
            <td class="label"><a href="javascript:showNotice('giveIntegral');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a> <?php echo $this->_var['lang']['lab_give_integral']; ?></td>
            <td><input type="text" name="give_integral" value="<?php echo $this->_var['goods']['give_integral']; ?>" size="20" />
            <br /><span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="giveIntegral"><?php echo $this->_var['lang']['notice_give_integral']; ?></span></td>
          </tr>
          <tr>
            <td class="label"><a href="javascript:showNotice('rankIntegral');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a> <?php echo $this->_var['lang']['lab_rank_integral']; ?></td>
            <td><input type="text" name="rank_integral" value="<?php echo $this->_var['goods']['rank_integral']; ?>" size="20" />
            <br /><span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="rankIntegral"><?php echo $this->_var['lang']['notice_rank_integral']; ?></span></td>
          </tr>
          <tr>
            <td class="label"><a href="javascript:showNotice('noticPoints');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a> <?php echo $this->_var['lang']['lab_integral']; ?></td>
            <td><input type="text" name="integral" value="<?php echo $this->_var['goods']['integral']; ?>" size="20" onblur="parseint_integral()";/>
              <br /><span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="noticPoints"><?php echo $this->_var['lang']['notice_integral']; ?></span>
            </td>
          </tr>
          <tr>
            <td class="label"><label for="is_promote"><input type="checkbox" id="is_promote" name="is_promote" value="1" <?php if ($this->_var['goods']['is_promote']): ?>checked="checked"<?php endif; ?> onclick="handlePromote(this.checked);" /> <?php echo $this->_var['lang']['lab_promote_price']; ?></label></td>
            <td id="promote_3"><input type="text" id="promote_1" name="promote_price" value="<?php echo $this->_var['goods']['promote_price']; ?>" size="20" /></td>
          </tr>
          <tr id="promote_4">
            <td class="label" id="promote_5"><?php echo $this->_var['lang']['lab_promote_date']; ?></td>
            <td id="promote_6" class="cal-group">
              <input name="promote_start_date" type="text" id="promote_start_date" size="12" value='<?php echo $this->_var['goods']['promote_start_date']; ?>' readonly="readonly"><button type="button" class="cal" name="selbtn1" id="selbtn1" onclick="return showCalendar('promote_start_date', '%Y-%m-%d', false, false, 'selbtn1');"><img src="images/cal.png" alt=""></button> - <input name="promote_end_date" type="text" id="promote_end_date" size="12" value='<?php echo $this->_var['goods']['promote_end_date']; ?>' readonly="readonly" /><button type="button" class="cal" name="selbtn2" id="selbtn2" onclick="return showCalendar('promote_end_date', '%Y-%m-%d', false, false, 'selbtn2');"><img src="images/cal.png" alt=""></button>
            </td>
          </tr>-->
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_picture']; ?></td>
            <td>
              <input type="file" name="goods_img" size="35" />
              <?php if ($this->_var['goods']['goods_img']): ?>
                <a href="goods.php?act=show_image&img_url=<?php echo $this->_var['goods']['goods_img']; ?>" target="_blank"><img src="images/yes.svg" width="16"></a>
              <?php else: ?>
                <img src="images/no.svg" width="16">
              <?php endif; ?>
              <br /><input type="text" size="40" value="<?php echo $this->_var['lang']['lab_picture_url']; ?>" style="color:#aaa;" onfocus="if (this.value == '<?php echo $this->_var['lang']['lab_picture_url']; ?>'){this.value='http://';this.style.color='#000';}" name="goods_img_url"/>
            </td>
          </tr>
          <tr id="auto_thumb_1">
            <td class="label"> <?php echo $this->_var['lang']['lab_thumb']; ?></td>
            <td id="auto_thumb_3">
              <input type="file" name="goods_thumb" size="35" />
              <?php if ($this->_var['goods']['goods_thumb']): ?>
                <a href="goods.php?act=show_image&img_url=<?php echo $this->_var['goods']['goods_thumb']; ?>" target="_blank"><img src="images/yes.svg" width="16"></a>
              <?php else: ?>
                <img src="images/no.svg" width="16">
              <?php endif; ?>
              <br /><input type="text" size="40" value="<?php echo $this->_var['lang']['lab_thumb_url']; ?>" style="color:#aaa;" onfocus="if (this.value == '<?php echo $this->_var['lang']['lab_thumb_url']; ?>'){this.value='http://';this.style.color='#000';}" name="goods_thumb_url"/>
              <?php if ($this->_var['gd'] > 0): ?>
              <br /><label for="auto_thumb"><input type="checkbox" id="auto_thumb" name="auto_thumb" checked="true" value="1" onclick="handleAutoThumb(this.checked)" /><?php echo $this->_var['lang']['auto_thumb']; ?></label><?php endif; ?>
            </td>
          </tr>
      <!--    <?php if ($this->_var['code'] == ''): ?>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_goods_weight']; ?></td>
            <td><input type="text" name="goods_weight" value="<?php echo $this->_var['goods']['goods_weight_by_unit']; ?>" size="20" /> <select name="weight_unit"><?php echo $this->html_options(array('options'=>$this->_var['unit_list'],'selected'=>$this->_var['weight_unit'])); ?></select></td>
          </tr>
          <?php endif; ?>-->
          <?php if ($this->_var['cfg']['use_storage']): ?>
          <tr>
            <td class="label"><a href="javascript:showNotice('noticeStorage');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a> <?php echo $this->_var['lang']['lab_goods_number']; ?></td>
            <!--        <td><input type="text" name="goods_number" value="<?php echo $this->_var['goods']['goods_number']; ?>" size="20" <?php if ($this->_var['code'] != '' || $this->_var['goods']['_attribute'] != ''): ?>readonly="readonly"<?php endif; ?> /><br />-->
            <td><input type="text" name="goods_number" value="<?php echo $this->_var['goods']['goods_number']; ?>" size="20" id="goods_number" <?php if ($this->_var['status'] == 4 || $this->_var['status'] == 5 || $this->_var['status'] == 6): ?>disabled<?php endif; ?>/><br />
              <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="noticeStorage"><?php echo $this->_var['lang']['notice_storage']; ?></span></td>
          </tr>
          <tr>
            <td class="label" style="font-family: 黑体;font-size: 14px"> <?php echo $this->_var['lang']['lab_only_rule']; ?></td>
            <td>
              <input type="radio" name="only_buy" value="0" id="no_only" onclick="checkOnly(0)" <?php if (! $this->_var['goods']['only_purchase']): ?>checked<?php endif; ?>><span style="font-family: 黑体;font-size: 14px"><?php echo $this->_var['lang']['lab_no_only']; ?></span>
              <br/>
              <input type="radio" name="only_buy" value="1" id="only" onclick="checkOnly(1)" <?php if ($this->_var['goods']['only_purchase']): ?>checked<?php endif; ?>><span style="font-family: 黑体;font-size: 14px"><?php echo $this->_var['lang']['lab_set_only']; ?></span>
              <input type="text" name="only_purchase" id="only_purchase" value="<?php echo $this->_var['goods']['only_purchase']; ?>" onchange="checkInput()">
            </td>
          </tr>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_warn_number']; ?></td>
            <td><input type="text" name="warn_number" value="<?php echo $this->_var['goods']['warn_number']; ?>" size="20" /></td>
          </tr>
          <?php endif; ?>
          <!-- 非配送范围 -->
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_shipping_tpl']; ?></td>
            <td class="unexpress-regions">
            <?php if (0 == $this->_var['shipping_id']): ?>
              <input type="radio" name="shipping_box"  value="0" checked>
              <?php echo $this->_var['lang']['lab_shipping_tpl_free']; ?>
              <input type="checkbox" name="" id="region_xinjiang"><?php echo $this->_var['lang']['lab_region_xinjiang']; ?>
              <input type="checkbox" name="" id="region_xizang"><?php echo $this->_var['lang']['lab_region_xizang']; ?>
              <input type="checkbox" name="" id="region_neimeng"><?php echo $this->_var['lang']['lab_region_neimeng']; ?>
              <input type="button" value="<?php echo $this->_var['lang']['lab_add_other']; ?>" id="get_more" onclick="clearMore()" class="button">
              <div class="add-other">
              </div>
              <div class="city_list">
              </div>
              <input type="text" name="unexpress_region" value="" style="display:none">
            </td>
          </tr>


          <tr>
            <td class="label"></td>
            <td class="unexpress-regions">
          <?php endif; ?>
              <input type="radio" name="shipping_box" value="1" <?php if ($this->_var['shipping_id'] != "" && $this->_var['shipping_id'] != 0): ?>checked<?php endif; ?>>
              <?php echo $this->_var['lang']['lab_shipping_tpl_select']; ?>&nbsp;&nbsp;
              <select name="shipping_id" id="shipping_id" onchange="getAreaList()">
                  <option value="0">请选择</option>
                  <?php $_from = $this->_var['shipping_tpl_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'value');if (count($_from)):
    foreach ($_from AS $this->_var['value']):
?>
                  <option value="<?php echo $this->_var['value']['id']; ?>" <?php if ($this->_var['value']['id'] == $this->_var['shipping_id']): ?>selected<?php endif; ?>><?php echo $this->_var['value']['name']; ?></option>
                  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
              </select>
            </td>
          </tr>

          <?php if (0 == $this->_var['admin_type']): ?>
          <tr>
            <td class="label"></td>
            <td class="unexpress-regions" id="area_list_show">
              <table border="1" style="width: 60%">
                  <tr>
                    <td style="width: 60%;">地区</td>
                    <td style="width: 10%;">首件</td>
                    <td style="width: 10%;">费用</td>
                    <td style="width: 10%;">续件</td>
                    <td style="width: 10%;">费用</td>
                </tr>
                  <?php $_from = $this->_var['shipping_tpl_area_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'value');if (count($_from)):
    foreach ($_from AS $this->_var['value']):
?>
                    <tr>
                      <td><?php echo $this->_var['value']['area_list_name']; ?></td>
                      <td><?php echo $this->_var['value']['head']; ?></td>
                      <td><?php echo $this->_var['value']['head_fee']; ?></td>
                      <td><?php echo $this->_var['value']['step']; ?></td>
                      <td><?php echo $this->_var['value']['step_fee']; ?></td>
                    </tr>
                  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
              </table>
            </td>
          </tr>
          <?php endif; ?>

          <!-- 发货保障 -->
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_delivery_guarantee']; ?></td>
            <td>
              <input type="radio" name="delivery_time" value="24" id="delivery_time_24" onclick="switchCustomDelivery(this)" index='static'><span><?php echo $this->_var['lang']['lab_delivery_guarantee_24_hour']; ?></span>
              <br>
              <input type="radio" name="delivery_time" value="48" id="delivery_time_48" onclick="switchCustomDelivery(this)" index='static'><span><?php echo $this->_var['lang']['lab_delivery_guarantee_48_hour']; ?></span>
              <br>
              <input type="radio" name="delivery_time" value="72" id="delivery_time_72" onclick="switchCustomDelivery(this)" index='static'><span><?php echo $this->_var['lang']['lab_delivery_guarantee_72_hour']; ?></span>
              <br>
              <input type="radio" name="delivery_time" value="custom" id="delivery_time_custom" onclick="switchCustomDelivery(this)" index='custom'><span><?php echo $this->_var['lang']['lab_delivery_guarantee_custom']; ?></span>
              <input type="text" name="delivery_time_custom" id="delivery_time_custom_input" value="" onchange="checkDeliveryInput()">
              <span><?php echo $this->_var['lang']['lab_delivery_guarantee_custom_warn']; ?></span>
            </td>
          </tr>

          <!-- 退货保障 -->
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_return_guarantee']; ?></td>
            <td>
              <input type="radio" name="refund_guarantee" value="1" <?php if ($this->_var['goods']['refund_guarantee'] == 1): ?>checked<?php endif; ?>>
              <span><?php echo $this->_var['lang']['lab_return_guarantee_7']; ?></span>
              <br>
              <input type="radio" name="refund_guarantee" value="2" <?php if ($this->_var['goods']['refund_guarantee'] == 2): ?>checked<?php endif; ?>>
              <span><?php echo $this->_var['lang']['lab_return_guarantee_7_close']; ?></span>
              <br>
              <input type="radio" name="refund_guarantee" value="3" <?php if ($this->_var['goods']['refund_guarantee'] == 3): ?>checked<?php endif; ?>>
              <span><?php echo $this->_var['lang']['lab_return_guarantee_unsupport']; ?></span>
            </td>
          </tr>

          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_real_guarantee']; ?></td>
            <td>
              <input type="checkbox" name="real_guarantee" value="1" <?php if ($this->_var['goods']['real_guarantee'] == 1): ?>checked<?php endif; ?> />
              <?php echo $this->_var['lang']['lab_real_guarantee_rule']; ?>
            </td>
          </tr>

          <!--<tr>-->
          <!--<td class="label"><?php echo $this->_var['lang']['lab_intro']; ?></td>-->
          <!--<td><input type="checkbox" name="is_best" value="1" <?php if ($this->_var['goods']['is_best']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_var['lang']['is_best']; ?> <input type="checkbox" name="is_new" value="1" <?php if ($this->_var['goods']['is_new']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_var['lang']['is_new']; ?> <input type="checkbox" name="is_hot" value="1" <?php if ($this->_var['goods']['is_hot']): ?>checked="checked"<?php endif; ?> /><?php echo $this->_var['lang']['is_hot']; ?></td>-->
          <!--</tr>-->
          <!--<tr id="alone_sale_1">-->
          <!--<td class="label" id="alone_sale_2"><?php echo $this->_var['lang']['lab_is_on_sale']; ?></td>-->
          <!--<td id="alone_sale_3"><input type="checkbox" name="is_on_sale" value="1" <?php if ($this->_var['goods']['is_on_sale']): ?>checked="checked"<?php endif; ?> /> <?php echo $this->_var['lang']['on_sale_desc']; ?></td>-->
          <!--</tr>-->
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_is_sell_out']; ?></td>
            <td><input type="checkbox" name="is_sell_out" value="1" <?php if ($this->_var['goods']['is_sell_out']): ?>checked="checked"<?php endif; ?> /> <?php echo $this->_var['lang']['sell_out_desc']; ?></td>
          </tr>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_is_alone_sale']; ?></td>
            <td><input type="checkbox" name="is_alone_sale" value="1" <?php if ($this->_var['goods']['is_alone_sale']): ?>checked="checked"<?php endif; ?> /> <?php echo $this->_var['lang']['alone_sale']; ?></td>
          </tr>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_hh_newbie']; ?></td>
            <td><input type="checkbox" name="hh_newbie" value="1" <?php if ($this->_var['goods']['hh_newbie']): ?>checked="checked"<?php endif; ?> /> <?php echo $this->_var['lang']['newbie']; ?></td>
          </tr>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_is_free_shipping']; ?></td>
            <td><input type="checkbox" name="is_shipping" value="1" <?php if ($this->_var['goods']['is_shipping']): ?>checked="checked"<?php endif; ?> /> <?php echo $this->_var['lang']['free_shipping']; ?></td>
          </tr>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_keywords']; ?></td>
            <td><input type="text" name="keywords" value="<?php echo htmlspecialchars($this->_var['goods']['keywords']); ?>" size="40" /> <?php echo $this->_var['lang']['notice_keywords']; ?></td>
          </tr>
          <tr>
            <td class="label"><?php echo $this->_var['lang']['lab_goods_brief']; ?></td>
            <td><textarea name="goods_brief" cols="40" rows="3"><?php echo htmlspecialchars($this->_var['goods']['goods_brief']); ?></textarea></td>
          </tr>
          <tr>
            <td class="label">
              <a href="javascript:showNotice('noticeSellerNote');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a> <?php echo $this->_var['lang']['lab_seller_note']; ?> </td>
            <td><textarea name="seller_note" cols="40" rows="3"><?php echo $this->_var['goods']['seller_note']; ?></textarea><br />
              <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="noticeSellerNote"><?php echo $this->_var['lang']['notice_seller_note']; ?></span></td>
          </tr>
        </table>
        <!-- 类目属性 -->
        <!--<table width="90%" id="properties-table" style="display:none" align="center">-->
          <!--<tr>-->
            <!--<td id="tbody-goodsAttr" colspan="2" style="padding:0"><?php if ($this->_var['goods']['goods_id']): ?><?php echo $this->_var['goods_attr_html']['html_general']; ?><?php endif; ?></td>-->
          <!--</tr>-->
        <!--</table>-->
        <!-- 自定义规格 -->
        <table id="custom_specification-table" class="custom-spcfict" style="display:none">
          <tr>
            <td>
              <a href="javascript:void(0);" onclick="removeCustomSpft(event)">[-]</a>
              <a href="javascript:void(0);" onclick="addCustomSpft(event)">[+]</a>
            </td>
            <td><?php echo $this->_var['lang']['lab_goods_attr_name']; ?></td>
            <td><?php echo $this->_var['lang']['lab_goods_attr_value']; ?></td>
          </tr>
          <?php $_from = $this->_var['goods']['custom_specification']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('i', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['i'] => $this->_var['item']):
?>
          <tr>
            <td>
              <a href="javascript:void(0);" onclick="removeCustomSpft(event)">[-]</a>
              <a href="javascript:void(0);" onclick="addCustomSpft(event)">[+]</a>
            </td>
            <td><input type="text" name="custom_specification_keys[]" value="<?php echo $this->_var['item']['attr_name']; ?>" maxlength="20"></td>
            <td><input type="text" name="custom_specification_values[]" value="<?php echo $this->_var['item']['attr_value']; ?>" maxlength="60"></td>
          </tr>
          <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
        </table>
        <!-- 销售属性 -->
        <table width="90%" id="properties_sale-table" class="properties-sale-table" style="display:none" align="center">
          <tr>
            <td id="tbody-goodsAttr_sale" colspan="2" style="padding:0"><?php if ($this->_var['goods']['goods_id']): ?><?php echo $this->_var['goods_attr_html']['html_sale']; ?><?php endif; ?></td>
          </tr>
        </table>
        <!-- 图文详情 -->
        <table width="90%" id="detail-table" style="display:none">
          <tr>
            <!--<td><?php echo $this->_var['FCKeditor']; ?></td>-->
              <td><script name="goods_desc" id="editor" type="text/plain" style="width:100%;height:500px;"><?php echo $this->_var['goods']['goods_desc']; ?></script></td>

          </tr>
        </table>
        <!-- 商品相册 -->
        <table width="90%" id="gallery-table" style="display:none" align="center">
          <!-- 鍥剧墖鍒楄〃 -->
          <tr>
            <td>
              <?php $_from = $this->_var['img_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('i', 'img');if (count($_from)):
    foreach ($_from AS $this->_var['i'] => $this->_var['img']):
?>
              <div id="gallery_<?php echo $this->_var['img']['img_id']; ?>" style="float:left; text-align:center; border: 1px solid #DADADA; margin: 4px; padding:2px;">
                <a href="javascript:;" onclick="if (confirm('<?php echo $this->_var['lang']['drop_img_confirm']; ?>')) dropImg('<?php echo $this->_var['img']['img_id']; ?>')">[-]</a><br />
                <?php if ($this->_var['img']['type'] == 1): ?>
                <a href="goods.php?act=show_image&img_url=<?php echo $this->_var['img']['img_url']; ?>" target="_blank">
                  <img src="../<?php if ($this->_var['img']['thumb_url']): ?><?php echo $this->_var['img']['thumb_url']; ?><?php else: ?><?php echo $this->_var['img']['img_url']; ?><?php endif; ?>" <?php if ($this->_var['thumb_width'] != 0): ?>width="<?php echo $this->_var['thumb_width']; ?>"<?php endif; ?> <?php if ($this->_var['thumb_height'] != 0): ?>height="<?php echo $this->_var['thumb_height']; ?>"<?php endif; ?> border="0" />
                </a>
                <?php elseif ($this->_var['img']['type'] == 2): ?>
                <video class="lib-video" webkit-playsinline="webkit-playsinline" playsinline="playsinline" controls style="width: 344px;height: 344px;"><source src="<?php echo $this->_var['img']['img_url']; ?>"></video>
                <?php endif; ?>
                <br />
                <input type="text" value="<?php echo htmlspecialchars($this->_var['img']['img_desc']); ?>" size="15" name="old_img_desc[<?php echo $this->_var['img']['img_id']; ?>]" />
              </div>
              <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
            </td>
          </tr>
          <tr><td>&nbsp;</td></tr>
          <!-- 涓婁紶鍥剧墖 -->
          <tr>
            <td>
              <a href="javascript:;" onclick="addImg(this)">[+]</a>
              <?php echo $this->_var['lang']['img_desc']; ?> <input type="text" name="img_desc[]" size="20" />
              <?php echo $this->_var['lang']['img_url']; ?> <input type="file" name="img_url[]" />
              <input type="text" size="40" value="<?php echo $this->_var['lang']['img_file']; ?>" style="color:#aaa;" onfocus="if (this.value == '<?php echo $this->_var['lang']['img_file']; ?>'){this.value='http://';this.style.color='#000';}" name="img_file[]"/>
            </td>
          </tr>
        </table>
        <div class="panel-hint panel-order-query" id="farme" style="display:none;width: 350px;height:200px;margin-top: 16%;z-index: 9999;">
          <div class="panel-hd">
            <span class="hd-title" style="float: left"><?php echo $this->_var['lang']['goods_audit']; ?></span>
            <span class="hd-cross" onclick="btnClose(this);"></span>
          </div>
          <div align="center" vertical-align="middle" style="font-size: 13px">
            <div id="no_pass" style="display:none;margin-top: 10px" >
              <span><?php echo $this->_var['lang']['audit_no_pass']; ?></span><br><br>
              <span><?php echo $this->_var['lang']['no_pass_reason']; ?></span><input type="text" name="remark" id="remark" size="28" /><br>
              <span style="color: red"><?php echo $this->_var['lang']['no_pass_input']; ?></span>
            </div>
            <div id="pass" style="display:none;margin-top: 10%">
              <span><?php echo $this->_var['lang']['audit_pass']; ?></span>
              <input type="hidden" name="audit_type" id="audit_type">
            </div>
          </div>
          <div align="center" style="margin-top: 3%">
            <input class="btn-act btn-confirm btn" name="export" type="button" id="sure" class="button" value="<?php echo $this->_var['lang']['button_submit']; ?>" onclick="checkHien()"/>
            <input class="btn-act btn-confirm btn" name="export" type="button" id="cancle" class="button" value="<?php echo $this->_var['lang']['cancle']; ?>" onclick="checkCancle()"/>
          </div>
        </div>

        <div class="button-div">
          <input type="hidden" name="goods_id" value="<?php echo $this->_var['goods']['goods_id']; ?>" />
          <?php if ($this->_var['code'] != ''): ?>
          <input type="hidden" name="extension_code" value="<?php echo $this->_var['code']; ?>" />
          <?php endif; ?>
          <?php if ($this->_var['is_audit']): ?>
          <input type="button" value="<?php echo $this->_var['lang']['pass']; ?>" class="button" id="view_pass"  onclick="checkAudit(1)" />
          <input type="button" value="<?php echo $this->_var['lang']['no_pass']; ?>" class="button" id="view_no_pass"  onclick="checkAudit(0)"/>
          <?php elseif ($this->_var['is_edit'] && $this->_var['admin_type'] == 1): ?>
          <input type="button" value="<?php echo $this->_var['lang']['button_no_audit']; ?>" class="button" onclick="validate('<?php echo $this->_var['goods']['goods_id']; ?>')" />
          <input type="reset" value="<?php echo $this->_var['lang']['button_reset']; ?>" class="button" />
          <?php elseif ($this->_var['is_edit'] || $this->_var['is_add']): ?>
          <input type="button" value="<?php echo $this->_var['lang']['button_submit']; ?>" class="button" onclick="validate('<?php echo $this->_var['goods']['goods_id']; ?>')" />
          <input type="reset" value="<?php echo $this->_var['lang']['button_reset']; ?>" class="button" />
          <?php endif; ?>
        </div>
        <input type="hidden" name="act" id="act" value="<?php echo $this->_var['form_act']; ?>"/>
        <input type="hidden" name="status" value="<?php echo $this->_var['status']; ?>"/>
      </form>
      <?php if ($this->_var['show_action']): ?>
      <div class="list-div" style="margin-bottom: 5px">
        <table  cellpadding="3" cellspacing="1">
          <tr>
            <th><?php echo $this->_var['lang']['operate_time']; ?></th>
            <th><?php echo $this->_var['lang']['operate_auth']; ?></th>
            <th><?php echo $this->_var['lang']['operate_record']; ?></th>
          </tr>
          <?php $_from = $this->_var['goods_action']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'action');if (count($_from)):
    foreach ($_from AS $this->_var['action']):
?>
          <tr>
            <td><div align="center"><?php echo $this->_var['action']['add_time']; ?></div></td>
            <td><div align="center"><?php echo $this->_var['action']['user_name']; ?></div></td>
            <td><div align="center"><?php echo $this->_var['action']['remark']; ?></div></td>
          </tr>
          <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
        </table>
      </div>
      <?php endif; ?>
    </div>
</div>
<div class="model instalment-model">
  <div class="model-bg"></div>
  <div class="model-wrapper">
    <div class="model-title">编辑分期金额</div>
    <div class="model-body">
      <div class="two-wrapper">
        <div>
          <span>批量填充现金: </span>
          <input type="number" oninput="instalNumInput(event, 0)">
          <button class="btn btn-def" for="cash-input" <?php if ($this->_var['view'] == 1): ?>disabled="disabled"<?php endif; ?> onclick="fillMoney(event)">确定</button>
        </div>
        <div>
          <span>批量填充权益: </span>
          <input type="number" oninput="instalNumInput(event, 0)">
          <button class="btn btn-def" for="hh-input" <?php if ($this->_var['view'] == 1): ?>disabled="disabled"<?php endif; ?> onclick="fillMoney(event)">确定</button>
        </div>
      </div>
      <p class="tips">无需支付的请填“0”</p>
      <table class="table table-bordered table-instalment">
        <thead>
          <tr>
            <td>分期</td>
            <td>
              <span class="require-field">*</span>
              现金
            </td>
            <td>
              <span class="require-field">*</span>
              权益币
            </td>
            <td>单期总额</td>
          </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
          <tr>
            <td>合计</td>
            <td class="cash-total"></td>
            <td class="hh-total"></td>
            <td class="money-total"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="model-footer">
      <?php if ($this->_var['view'] == 1): ?>
      <button class="btn btn-def" onclick="cancelInstalment()">关闭</button>
      <?php else: ?>
      <button class="btn" onclick="comfirmInstalment()">确认</button>
      <button class="btn btn-def" onclick="cancelInstalment()">取消</button>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- end goods form -->
<?php echo $this->smarty_insert_scripts(array('files'=>'uploader/jquery.js,validator.js,tab.js,../js/region.js','version'=>$this->_var['static_version'])); ?>
<script type="text/javascript" charset="utf-8" src="/admin/js/jquery.searchableSelect.js"></script>

<script language="JavaScript" defer=true>
  var goodsId = '<?php echo $this->_var['goods']['goods_id']; ?>';
  var elements = document.forms['theForm'].elements;
  var sz1 = new SelectZone(1, elements['source_select1'], elements['target_select1']);
  var sz2 = new SelectZone(2, elements['source_select2'], elements['target_select2'], elements['price2']);
  var sz3 = new SelectZone(1, elements['source_select3'], elements['target_select3']);
  var marketPriceRate = <?php echo empty($this->_var['cfg']['market_price_rate']) ? '1' : $this->_var['cfg']['market_price_rate']; ?>;
  var integralPercent = <?php echo empty($this->_var['cfg']['integral_percent']) ? '0' : $this->_var['cfg']['integral_percent']; ?>;

  var deliveryTime = '<?php echo $this->_var['goods']['delivery_time']; ?>';
  var refundGuarantee = '<?php echo $this->_var['goods']['refund_guarantee']; ?>';
  var realGuarantee = '<?php echo $this->_var['goods']['real_guarantee']; ?>';

  // 汽车分期v1.1
  var instalmentCats  = ['926', '2']; // 分期类ID

  var region_list = <?php echo $this->_var['region']; ?>;
  var select_regionId = [];
  var select_regionName = [];
  var useless_regions = [["xinjiang","32"],["xizang","27"],["neimeng","6"],]
  var str_province = ''
  var view = '<?php echo $this->_var['view']; ?>';
  <?php if ($this->_var['goods']['is_instalment'] == 1): ?>
  var isInstalment = 1;
  var instalments = <?php echo $this->_var['instalments']; ?>; // 商品分期记录JSON
  <?php else: ?>
  var isInstalment = 0;
  <?php endif; ?>

  // 销售属性图片错误信息
  var goods_attr_sale_err = []
  if(view) {
    var inp = document.getElementsByTagName("input");
    for(var i = 0,len = inp.length;i<len;i++){
      inp[i].setAttribute("disabled", "disabled");
    }
    var sel = document.getElementsByTagName("select");
    for(var j = 0,len = sel.length;j<len;j++){
      sel[j].setAttribute("disabled", "disabled");
    }
    var tex = document.getElementsByTagName("textarea");
    for(var i = 0,len = tex.length;i<len;i++){
      tex[i].setAttribute("disabled", "disabled");
    }
    var a = document.getElementsByTagName('a');
    for(var i = 0,len = a.length;i<len;i++){
      a[i].removeAttribute("onclick");
    }
    $('#act').removeAttr("disabled");
    $('#view_no_pass').removeAttr("disabled");
    $('#view_pass').removeAttr("disabled");
    $('#sure').removeAttr("disabled");
    $('#remark').removeAttr("disabled");
    $('#cancle').removeAttr("disabled");
  }
  var radio_arr = document.getElementsByName('only_buy');
  for(i=0;i<radio_arr.length;i++){
    if(radio_arr[1].checked){
      document.getElementById('only_purchase').removeAttribute('disabled')
    }else{
      document.getElementById('only_purchase').setAttribute('disabled','disabled')
      document.getElementById('only_purchase').value = '';
    }
  }

  
  onload = function()
  {
      //handlePromote(document.forms['theForm'].elements['is_promote'].checked);
      var ue = UE.getEditor('editor');
      $(function(){
          console.log($('.searchable-select'))
          $('.searchable-select').on('click', function() {
            console.log('click')
          })
          $('.search-table select').searchableSelect({
            afterSelectItem: function() {

            }
          });
      });
      if (document.forms['theForm'].elements['auto_thumb'])
      {
          handleAutoThumb(document.forms['theForm'].elements['auto_thumb'].checked);
      }

      // 妫€鏌ユ柊璁㈠崟
      startCheckOrder();
      
      <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
      set_price_note(<?php echo $this->_var['item']['rank_id']; ?>);
      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
      
      document.forms['theForm'].reset();

     // showInput()
      computed_hb();

      // 获取非配送省份
      getProvince()
      showBorrow()
      //showHhguest()
      var con = '<?php echo $this->_var['confirm_info']; ?>';
      if(con){
          alert("多规格SKU已清空，请重新录入多规格SKU！");
      }
      var cat_id_1 = document.getElementById('cat_id_1');
      var cat_id_2 = document.getElementById('cat_id_2');
      var cat_id = document.getElementById('cat_id');
      if(cat_id_1.value != 0){
          if(cat_id_2.value == 0){// 修改联动方法
              region.changed(cat_id_1, 1, 'cat_id_2')
          }else{
              if(cat_id.value == 0){// 修改联动方法
                  region.changed(cat_id_2, 1, 'cat_id')
              }
          }
      }

      // 初始化商家保障
      if(deliveryTime == '24' || deliveryTime == '48' ||deliveryTime == '72'){
        $('#delivery_time_' + deliveryTime).attr('checked', 'checked');
      } else if (deliveryTime == '0'){

      } else {
        $('#delivery_time_custom').attr('checked', 'checked');
        $('#delivery_time_custom_input').val(deliveryTime / 24);
      }

      // 初始化自定退货时间状态
      if(view){
        document.getElementById('delivery_time_custom_input').setAttribute('disabled', 'disabled');
      }else if(document.getElementsByName('delivery_time')[3].checked){
        document.getElementById('delivery_time_custom_input').removeAttribute('disabled');
      }else{
        document.getElementById('delivery_time_custom_input').setAttribute('disabled', 'disabled');
      }
  }

  /**
   * 提交表单，先判断是否符合各种要求
   *
   * @param      {string}   goods_id  商品ID
   * @return     {boolean}  {description_of_the_return_value}
   */
  function validate(goods_id) {
    // 非配送地区是否有西藏等不常用地区
    checkXizang();

    var validator = new Validator('theForm');
    var goods_sn  = document.forms['theForm'].elements['goods_sn'].value;

    // 自定义属性编辑（当name为空时不提交给后台）
    if (document.forms['theForm'].elements['custom_specification_keys[]']) {
      validator.required('custom_specification_keys[]', custom_specification_name_not_null);
      validator.required('custom_specification_values[]', custom_specification_values_not_null);
    }

    validator.required('goods_name', goods_name_not_null);
    if (document.forms['theForm'].elements['cat_id'].value == 0)
    {
        validator.addErrorMsg(goods_cat_not_null);
    }

    // 分期判断
    if ($(".is-instalment").val() == 1) {
      if (instalmentCats.indexOf($('#cat_id_2').val()) > -1) {
        if ($(".each-instalment-item").length == 0) {
          alert('请设置分期价格')
          return
        } else {
          var instalmentErr = false
          $('.instalment-pay-plan').each(function() {
            if (!$(this).val()) {
              instalmentErr = true
            }
          })
          if (instalmentErr) {
            alert('请设置分期价格')
            return
          }
        }
      } else {
        alert('此商品暂不支持分期')
        return
      }
    }

    // 销售属性图片必须是800*800的
    if (goods_attr_sale_err.length > 0) {
      alert('销售属性：\n' + goods_attr_sale_err.join('\n'))
      return false
    }

    checkVolumeData("1",validator);

    validator.required('shop_price', shop_price_not_null);
    validator.isNumber('shop_price', shop_price_not_number, true);
    validator.required('market_price', market_price_not_null);
    validator.isNumber('market_price', market_price_not_number, false);
    var shop_price = document.forms['theForm'].elements['shop_price'].value;
    var market_price = document.forms['theForm'].elements['market_price'].value;
    if(parseFloat(market_price) < parseFloat(shop_price)){
       alert(market_price_error);
       return false;
    }
      //指定债权
      var set_borrow = '<?php echo $this->_var['set_borrow']; ?>';
      if(set_borrow){
          if(document.forms['theForm'].elements['have_borrow'].value == 1){
              // var val = $("input[name='pay_method']:checked").val();
              // if(val == -1){
              //     alert(error_borrow_pay)
              //     return false;
              // }
              validator.required('company_name', no_company_name)
              var uidArr = document.forms['theForm'].elements['borrow_ids'].value;
              var borrow_ids = uidArr.split(/[(\r\n)\r\n]+/);
              for ($i=0;$i<borrow_ids.length;$i++){
                  var reg = /^(ZX\d+|ZTX\d+|\d+)$/;
                  if(!reg.test(borrow_ids[$i])){
                      alert(no_borrow_ids);
                      return false;
                  }
              }
          }
      }

    // 混合支付
    // if(document.forms['theForm'].elements['pay_method'].value == -1){
    //   document.forms['theForm'].elements['money_line'].value = -1
    // }else{
      validator.required('hb_line', hb_line_not_null);
      validator.isNumber('hb_line', hb_line_not_number);
      var goodPrice = document.forms['theForm'].elements['shop_price'].value
      var hb = document.forms['theForm'].elements['hb_line'].value
      if(goodPrice*1 < hb*1){
        alert(hb_line_invalid)
        return false
      }else if(hb*1 <= 0){
        alert(hb_line_zero)
        return false
      }
      document.forms['theForm'].elements['money_line'].value = document.forms['theForm'].elements['hb_line'].value
    // }

      /* 换换客 */
   /*   if($('input:radio[name="hh_guest"]:checked').val() == 1){
          if(!mlmShopprice()){
              return false;
          }
          if(!mlmMoneyline()){
              return false;
          }
      }*/

    // if (document.forms['theForm'].elements['is_promote'].checked)
    // {
    //     validator.required('promote_start_date', promote_start_not_null);
    //     validator.required('promote_end_date', promote_end_not_null);
    //     validator.islt('promote_start_date', 'promote_end_date', promote_not_lt);
    // }

    if (document.forms['theForm'].elements['goods_number'] != undefined)
    {
        validator.isInt('goods_number', goods_number_not_int, false);
        validator.isInt('warn_number', warn_number_not_int, false);
    }

    // 验证发货保障
    var deliveryTime =  document.forms['theForm'].elements['delivery_time'].value;
    validator.required('delivery_time', promote_delivery_guarantee_null);
    if(deliveryTime == 'custom'){
      validator.isInt('delivery_time_custom_input', delivery_time_custom_input_error, true);
      var deliveryTimeCustom = document.forms['theForm'].elements['delivery_time_custom_input'].value;
      if(deliveryTimeCustom < 1 || deliveryTimeCustom > 99){
        alert(promote_delivery_guarantee_null);
        return false;
      }
    }

    validator.required('refund_guarantee', promote_refund_guarantee_null);

    // 验证分期
    // if($('.is-instalment').val() == 1 && instalmentCount == 0){
    //   alert('请添加分期');
    //   return false;
    // }

    var callback = function(res)
    {
      if (res.error > 0)
      {
        alert("<?php echo $this->_var['lang']['goods_sn_exists']; ?>");
      }
      else
      {
         if(validator.passed())
         {
             if (document.getElementById('only').checked == true) {
               var bol = checkInput();
               if(!bol){
                 document.getElementById('only_purchase').focus();
                 return false
               }
             }
             document.forms['theForm'].submit();
         }
      }
    }
    Ajax.call('goods.php?is_ajax=1&act=check_goods_sn', "goods_sn=" + goods_sn + "&goods_id=" + goods_id, callback, "GET", "JSON");
  }

  /**
   * 鍒囨崲鍟嗗搧绫诲瀷
   */
  function getAttrList(goodsId)
  {
      var selGoodsType = document.forms['theForm'].elements['cat_id'];

      if (selGoodsType != undefined)
      {
          var goodsType = selGoodsType.options[selGoodsType.selectedIndex].value;

          Ajax.call('goods.php?is_ajax=1&act=get_attr', 'goods_id=' + goodsId + "&category_id=" + goodsType, setAttrList, "GET", "JSON");
      }
  }

  function setAttrList(result, text_result)
  {
    //document.getElementById('tbody-goodsAttr').innerHTML = result.content.html_general ? result.content.html_general : '';
    document.getElementById('tbody-goodsAttr_sale').innerHTML = result.content.html_sale ? result.content.html_sale : '';
  }

  /**
   * 鎸夋瘮渚嬭?绠椾环鏍
   * @param   string  inputName   杈撳叆妗嗗悕绉
   * @param   float   rate        姣斾緥
   * @param   string  priceName   浠锋牸杈撳叆妗嗗悕绉帮紙濡傛灉娌℃湁锛屽彇shop_price锛
   */
  function computePrice(inputName, rate, priceName)
  {
      var shopPrice = priceName == undefined ? document.forms['theForm'].elements['shop_price'].value : document.forms['theForm'].elements[priceName].value;
      shopPrice = Utils.trim(shopPrice) != '' ? parseFloat(shopPrice)* rate : 0;
      // if(inputName == 'integral')
      // {
      //     shopPrice = parseInt(shopPrice);
      // }
      shopPrice += "";

      n = shopPrice.lastIndexOf(".");
      if (n > -1)
      {
          shopPrice = shopPrice.substr(0, n + 3);
      }

      if (document.forms['theForm'].elements[inputName] != undefined)
      {
          document.forms['theForm'].elements[inputName].value = shopPrice;
      }
      else
      {
          document.getElementById(inputName).value = shopPrice;
      }
  }

      function computed_hb(){
          var goodPrice = document.forms['theForm'].elements['shop_price'].value;
          var hb_num = document.forms['theForm'].elements['hb_line'].value;
          var token = document.forms['theForm'].elements['token_type'].value;
          var type = '权益';
          if(token == 1){
              type = '权益币';
          }
          if(token == 2){
            type = '浣豆';
          }
          if (goodPrice > 0 & goodPrice != '') {
              var hb_num_str='';
              if(hb_num>0){
                  var money = sub(parseFloat(goodPrice),parseFloat(hb_num));
                  hb_num_str = hb_num+type+money+'现金'
              }
              if(hb_num*1>goodPrice*1){
                  hb_num_str='金额异常'
              }
              $("#cash_num").html(hb_num_str)
          }else{
              $("#cash_num").html('')
          }
      }

      function sub(a, b) {
          var c, d, e;
          try {
              c = a.toString().split(".")[1].length;
          } catch (f) {
              c = 0;
          }
          try {
              d = b.toString().split(".")[1].length;
          } catch (f) {
              d = 0;
          }
          return e = Math.pow(10, Math.max(c, d)), (mul(a, e) - mul(b, e)) / e;
      }

      function mul(a, b) {
          var c = 0,
              d = a.toString(),
              e = b.toString();
          try {
              c += d.split(".")[1].length;
          } catch (f) {}
          try {
              c += e.split(".")[1].length;
          } catch (f) {}
          return Number(d.replace(".", "")) * Number(e.replace(".", "")) / Math.pow(10, c);
      }

      function decimal_add(a, b){
        a = parseFloat(a)
        b = parseFloat(b)
        return Math.floor((a + b) * 100) / 100;
      }

      function decimal_sub(a, b){
        return Math.floor((a - b) * 100) / 100;
      }

      function decimal_mul(a, b){
        return Math.floor((a * b) * 100) / 100;
      }

      function decimal_div(a, b){
        return Math.floor((a / b) * 100) / 100;
      }

  /**
   * 璁剧疆浜嗕竴涓?晢鍝佷环鏍硷紝鏀瑰彉甯傚満浠锋牸銆佺Н鍒嗕互鍙婁細鍛樹环鏍
   */
  function priceSetted()
  {
    computePrice('market_price', marketPriceRate);
    // computePrice('integral', integralPercent / 100);
    computed_hb();
    
    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    set_price_note(<?php echo $this->_var['item']['rank_id']; ?>);
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    

    if(isInstalment == 1){
      $('.instalment-item-list').empty();
      $('.instalment-add').css('display', 'block');
      instalmentCount = 0;
      instalmentIndex = 0;
    }
  }

  /**
   * 璁剧疆浼氬憳浠锋牸娉ㄩ噴
   */
  function set_price_note(rank_id)
  {
    var shop_price = parseFloat(document.forms['theForm'].elements['shop_price'].value);

    var rank = new Array();
    
    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    rank[<?php echo $this->_var['item']['rank_id']; ?>] = <?php echo empty($this->_var['item']['discount']) ? '100' : $this->_var['item']['discount']; ?>;
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    
    if (shop_price >0 && rank[rank_id] && document.getElementById('rank_' + rank_id) && parseInt(document.getElementById('rank_' + rank_id).value) == -1)
    {
      var price = parseInt(shop_price * rank[rank_id] + 0.5) / 100;
      if (document.getElementById('nrank_' + rank_id))
      {
        document.getElementById('nrank_' + rank_id).innerHTML = '(' + price + ')';
      }
    }
    else
    {
      if (document.getElementById('nrank_' + rank_id))
      {
        document.getElementById('nrank_' + rank_id).innerHTML = '';
      }
    }
  }

  /**
   * 鏍规嵁甯傚満浠锋牸锛岃?绠楀苟鏀瑰彉鍟嗗簵浠锋牸銆佺Н鍒嗕互鍙婁細鍛樹环鏍
   */
  function marketPriceSetted()
  {
    computePrice('shop_price', 1/marketPriceRate, 'market_price');
    computePrice('integral', integralPercent / 100);
    
    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    set_price_note(<?php echo $this->_var['item']['rank_id']; ?>);
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    
  }

  /**
   * 鏂板?涓€涓??鏍
   */
  function addSpec(obj)
  {
      var src   = obj.parentNode.parentNode;
      var idx   = rowindex(src);
      var tbl   = document.getElementById('attrTable_sale');
      var row   = tbl.insertRow(idx + 1);
      var cell1 = row.insertCell(-1);
      var cell2 = row.insertCell(-1);
      var regx  = /<a([^>]+)<\/a>/i;

      cell1.className = 'label';
      cell1.innerHTML = src.childNodes[0].innerHTML.replace(/(.*)(addSpec)(.*)(\[)(\+)/i, "$1removeSpec$3$4-");
      cell2.innerHTML = src.childNodes[1].innerHTML.replace(/readOnly([^\s|>]*)/i, '');
  }

  /**
   * 鍒犻櫎瑙勬牸鍊
   */
  function removeSpec(obj)
  {
      var row = rowindex(obj.parentNode.parentNode);
      var tbl = document.getElementById('attrTable_sale');

      tbl.deleteRow(row);
  }

  /**
   * 澶勭悊瑙勬牸
   */
  function handleSpec()
  {
      var elementCount = document.forms['theForm'].elements.length;
      for (var i = 0; i < elementCount; i++)
      {
          var element = document.forms['theForm'].elements[i];
          if (element.id.substr(0, 5) == 'spec_')
          {
              var optCount = element.options.length;
              var value = new Array(optCount);
              for (var j = 0; j < optCount; j++)
              {
                  value[j] = element.options[j].value;
              }

              var hiddenSpec = document.getElementById('hidden_' + element.id);
              hiddenSpec.value = value.join(String.fromCharCode(13)); // 鐢ㄥ洖杞﹂敭闅斿紑姣忎釜瑙勬牸
          }
      }
      return true;
  }

  function handlePromote(checked)
  {
      document.forms['theForm'].elements['promote_price'].disabled = !checked;
      document.forms['theForm'].elements['selbtn1'].disabled = !checked;
      document.forms['theForm'].elements['selbtn2'].disabled = !checked;
  }

  function handleAutoThumb(checked)
  {
      document.forms['theForm'].elements['goods_thumb'].disabled = checked;
      document.forms['theForm'].elements['goods_thumb_url'].disabled = checked;
  }

  /**
   * 蹇?€熸坊鍔犲搧鐗
   */
  function rapidBrandAdd(conObj)
  {
      var brand_div = document.getElementById("brand_add");

      if(brand_div.style.display != '')
      {
          var brand =document.forms['theForm'].elements['addedBrandName'];
          brand.value = '';
          brand_div.style.display = '';
      }
  }

  function hideBrandDiv()
  {
      var brand_add_div = document.getElementById("brand_add");
      if(brand_add_div.style.display != 'none')
      {
          brand_add_div.style.display = 'none';
      }
  }

  function goBrandPage()
  {
      if(confirm(go_brand_page))
      {
          window.location.href='brand.php?act=add';
      }
      else
      {
          return;
      }
  }

  function rapidCatAdd()
  {
      var cat_div = document.getElementById("category_add");

      if(cat_div.style.display != '')
      {
          var cat =document.forms['theForm'].elements['addedCategoryName'];
          cat.value = '';
          cat_div.style.display = '';
      }
  }

  function addBrand()
  {
      var brand = document.forms['theForm'].elements['addedBrandName'];
      if(brand.value.replace(/^\s+|\s+$/g, '') == '')
      {
          alert(brand_cat_not_null);
          return;
      }

      var params = 'brand=' + brand.value;
      Ajax.call('brand.php?is_ajax=1&act=add_brand', params, addBrandResponse, 'GET', 'JSON');
  }

  function addBrandResponse(result)
  {
      if (result.error == '1' && result.message != '')
      {
          alert(result.message);
          return;
      }

      var brand_div = document.getElementById("brand_add");
      brand_div.style.display = 'none';

      var response = result.content;

      var selCat = document.forms['theForm'].elements['brand_id'];
      var opt = document.createElement("OPTION");
      opt.value = response.id;
      opt.selected = true;
      opt.text = response.brand;

      if (Browser.isIE)
      {
          selCat.add(opt);
      }
      else
      {
          selCat.appendChild(opt);
      }

      return;
  }

  function addCategory()
  {
      var parent_id = document.forms['theForm'].elements['cat_id'];
      var cat = document.forms['theForm'].elements['addedCategoryName'];
      if(cat.value.replace(/^\s+|\s+$/g, '') == '')
      {
          alert(category_cat_not_null);
          return;
      }

      var params = 'parent_id=' + parent_id.value;
      params += '&cat=' + cat.value;
      Ajax.call('category.php?is_ajax=1&act=add_category', params, addCatResponse, 'GET', 'JSON');
  }

  function hideCatDiv()
  {
      var category_add_div = document.getElementById("category_add");
      if(category_add_div.style.display != null)
      {
          category_add_div.style.display = 'none';
      }
  }

  function addCatResponse(result)
  {
      if (result.error == '1' && result.message != '')
      {
          alert(result.message);
          return;
      }

      var category_add_div = document.getElementById("category_add");
      category_add_div.style.display = 'none';

      var response = result.content;

      var selCat = document.forms['theForm'].elements['cat_id'];
      var opt = document.createElement("OPTION");
      opt.value = response.id;
      opt.selected = true;
      opt.innerHTML = response.cat;

      //鑾峰彇瀛愬垎绫荤殑绌烘牸鏁
      var str = selCat.options[selCat.selectedIndex].text;
      var temp = str.replace(/^\s+/g, '');
      var lengOfSpace = str.length - temp.length;
      if(response.parent_id != 0)
      {
          lengOfSpace += 4;
      }
      for (i = 0; i < lengOfSpace; i++)
      {
          opt.innerHTML = '&nbsp;' + opt.innerHTML;
      }

      for (i = 0; i < selCat.length; i++)
      {
          if(selCat.options[i].value == response.parent_id)
          {
              if(i == selCat.length)
              {
                  if (Browser.isIE)
                  {
                      selCat.add(opt);
                  }
                  else
                  {
                      selCat.appendChild(opt);
                  }
              }
              else
              {
                  selCat.insertBefore(opt, selCat.options[i + 1]);
              }
              //opt.selected = true;
              break;
          }

      }

      return;
  }

    function goCatPage()
    {
        if(confirm(go_category_page))
        {
            window.location.href='category.php?act=add';
        }
        else
        {
            return;
        }
    }


  /**
   * 鍒犻櫎蹇?€熷垎绫
   */
  function removeCat()
  {
      if(!document.forms['theForm'].elements['parent_cat'] || !document.forms['theForm'].elements['new_cat_name'])
      {
          return;
      }

      var cat_select = document.forms['theForm'].elements['parent_cat'];
      var cat = document.forms['theForm'].elements['new_cat_name'];

      cat.parentNode.removeChild(cat);
      cat_select.parentNode.removeChild(cat_select);
  }

  /**
   * 鍒犻櫎蹇?€熷搧鐗
   */
  function removeBrand()
  {
      if (!document.forms['theForm'].elements['new_brand_name'])
      {
          return;
      }

      var brand = document.theForm.new_brand_name;
      brand.parentNode.removeChild(brand);
  }

  /**
   * 娣诲姞鎵╁睍鍒嗙被
   */
  function addOtherCat(conObj)
  {
      var sel = document.createElement("SELECT");
      var selCat = document.forms['theForm'].elements['cat_id'];

      for (i = 0; i < selCat.length; i++)
      {
          var opt = document.createElement("OPTION");
          opt.text = selCat.options[i].text;
          opt.value = selCat.options[i].value;
          if (Browser.isIE)
          {
              sel.add(opt);
          }
          else
          {
              sel.appendChild(opt);
          }
      }
      conObj.appendChild(sel);
      sel.name = "other_cat[]";
      sel.onChange = function() {checkIsLeaf(this);};
  }

  /* 鍏宠仈鍟嗗搧鍑芥暟 */
  function searchGoods(szObject, catId, brandId, keyword)
  {
      var filters = new Object;

      filters.cat_id = elements[catId].value;
      filters.brand_id = elements[brandId].value;
      filters.keyword = Utils.trim(elements[keyword].value);
      filters.exclude = document.forms['theForm'].elements['goods_id'].value;

      szObject.loadOptions('get_goods_list', filters);
  }

  /**
   * 鍏宠仈鏂囩珷鍑芥暟
   */
  function searchArticle()
  {
    var filters = new Object;

    filters.title = Utils.trim(elements['article_title'].value);

    sz3.loadOptions('get_article_list', filters);
  }

  /**
   * 自定义属性添加
   *
   * @param      {Object}  event     点击事件
   */
  function addCustomSpft(event) {
    var target = event.srcElement || event.target
    var $tr = $(target).parent().parent();
    if ($tr.parent().children().length >= 11) {
      alert(custom_specification_length_not_long)
      return
    }
    var insertTr = $('<tr>\
      <td>\
        <a href="javascript:void(0);" onclick="removeCustomSpft(event)">[-]</a>\
        <a href="javascript:void(0);" onclick="addCustomSpft(event)">[+]</a>\
      </td>\
      <td><input type="text" name="custom_specification_keys[]" value="<?php echo $this->_var['item']['name']; ?>" maxlength="20"></td>\
      <td><input type="text" name="custom_specification_values[]" value="<?php echo $this->_var['item']['value']; ?>" maxlength="60"></td>\
    </tr>')
    $tr.after(insertTr)
  }

  /**
   * 移除自定义属性
   *
   * @param      {Object}  event     点击事件
   */
  function removeCustomSpft(event) {
    var target = event.srcElement || event.target
    $(target).parent().parent().remove()
  }

  /**
   * 鏂板?涓€涓?浘鐗
   */
  function addImg(obj)
  {
      var src  = obj.parentNode.parentNode;
      var idx  = rowindex(src);
      var tbl  = document.getElementById('gallery-table');
      var row  = tbl.insertRow(idx + 1);
      var cell = row.insertCell(-1);
      cell.innerHTML = src.cells[0].innerHTML.replace(/(.*)(addImg)(.*)(\[)(\+)/i, "$1removeImg$3$4-");
  }

  /**
   * 鍒犻櫎鍥剧墖涓婁紶
   */
  function removeImg(obj)
  {
      var row = rowindex(obj.parentNode.parentNode);
      var tbl = document.getElementById('gallery-table');

      tbl.deleteRow(row);
  }

  /**
   * 鍒犻櫎鍥剧墖
   */
  function dropImg(imgId)
  {
    Ajax.call('goods.php?is_ajax=1&act=drop_image', "img_id="+imgId, dropImgResponse, "GET", "JSON");
  }

  function dropImgResponse(result)
  {
      if (result.error == 0)
      {
          document.getElementById('gallery_' + result.content).style.display = 'none';
      }
  }

  /**
   * 灏嗗競鍦轰环鏍煎彇鏁
   */
  function integral_market_price()
  {
    document.forms['theForm'].elements['market_price'].value = parseInt(document.forms['theForm'].elements['market_price'].value);
  }

   /**
   * 灏嗙Н鍒嗚喘涔伴?搴﹀彇鏁
   */
  function parseint_integral()
  {
    document.forms['theForm'].elements['integral'].value = parseInt(document.forms['theForm'].elements['integral'].value);
  }


  /**
  * 妫€鏌ヨ揣鍙锋槸鍚﹀瓨鍦
  */
  function checkGoodsSn(goods_sn, goods_id)
  {
    if (goods_sn == '')
    {
        document.getElementById('goods_sn_notice').innerHTML = "";
        return;
    }

    var callback = function(res)
    {
      if (res.error > 0)
      {
        document.getElementById('goods_sn_notice').innerHTML = res.message;
        document.getElementById('goods_sn_notice').style.color = "red";
      }
      else
      {
        document.getElementById('goods_sn_notice').innerHTML = "";
      }
    }
    Ajax.call('goods.php?is_ajax=1&act=check_goods_sn', "goods_sn=" + goods_sn + "&goods_id=" + goods_id, callback, "GET", "JSON");
  }

  /**
   * 鏂板?涓€涓?紭鎯犱环鏍
   */
  function addVolumePrice(obj)
  {
    var src      = obj.parentNode.parentNode;
    var tbl      = document.getElementById('tbody-volume');

    var validator  = new Validator('theForm');
    checkVolumeData("0",validator);
    if (!validator.passed())
    {
      return false;
    }

    var row  = tbl.insertRow(tbl.rows.length);
    var cell = row.insertCell(-1);
    cell.innerHTML = src.cells[0].innerHTML.replace(/(.*)(addVolumePrice)(.*)(\[)(\+)/i, "$1removeVolumePrice$3$4-");

    var number_list = document.getElementsByName("volume_number[]");
    var price_list  = document.getElementsByName("volume_price[]");

    number_list[number_list.length-1].value = "";
    price_list[price_list.length-1].value   = "";
  }

  /**
   * 鍒犻櫎浼樻儬浠锋牸
   */
  function removeVolumePrice(obj)
  {
    var row = rowindex(obj.parentNode.parentNode);
    var tbl = document.getElementById('tbody-volume');

    tbl.deleteRow(row);
  }

  /**
   * 鏍￠獙浼樻儬鏁版嵁鏄?惁姝ｇ‘
   */
  function checkVolumeData(isSubmit,validator)
  {
    var volumeNum = document.getElementsByName("volume_number[]");
    var volumePri = document.getElementsByName("volume_price[]");
    var numErrNum = 0;
    var priErrNum = 0;

    for (i = 0 ; i < volumePri.length ; i ++)
    {
      if ((isSubmit != 1 || volumeNum.length > 1) && numErrNum <= 0 && volumeNum.item(i).value == "")
      {
        validator.addErrorMsg(volume_num_not_null);
        numErrNum++;
      }

      if (numErrNum <= 0 && Utils.trim(volumeNum.item(i).value) != "" && ! Utils.isNumber(Utils.trim(volumeNum.item(i).value)))
      {
        validator.addErrorMsg(volume_num_not_number);
        numErrNum++;
      }

      if ((isSubmit != 1 || volumePri.length > 1) && priErrNum <= 0 && volumePri.item(i).value == "")
      {
        validator.addErrorMsg(volume_price_not_null);
        priErrNum++;
      }

      if (priErrNum <= 0 && Utils.trim(volumePri.item(i).value) != "" && ! Utils.isNumber(Utils.trim(volumePri.item(i).value)))
      {
        validator.addErrorMsg(volume_price_not_number);
        priErrNum++;
      }
    }
  }

  function showInput(){
    if(document.forms['theForm'].elements['pay_method'].value == 0){
      $(".hb_line").removeClass("none")
    }else{
      $(".hb_line").addClass("none")
    }
  }

  // 非配送js
  function clearMore(){
    var str = '';
    str += '<div class="tab-title"><div id="tab-province"><?php echo $this->_var['lang']['lab_region_province']; ?></div><div id="tab-citys"><?php echo $this->_var['lang']['lab_region_city']; ?></div><div class="img-wrapper"><img src="images/close.png" onclick="Close()"></div></div><ul class="tab-content">'
    str += str_province;
    str += '</ul>'
    $(".add-other").html(str)
    $("#tab-province").addClass("activity")
  }
  function clearCity(id){
    var region_item = region_list[id]["childrens"]
    var str = '';
    str += '<div class="tab-title"><div id="tab-province" onclick="ClearProvince()"><?php echo $this->_var['lang']['lab_region_province']; ?></div><div id="tab-citys"><?php echo $this->_var['lang']['lab_region_city']; ?></div><div class="img-wrapper"><img src="images/close.png" onclick="Close()"></div></div><ul class="tab-content">'
    str += '<li data-name='+region_list[id]["region_name"]+' data-id='+region_list[id]["region_id"]+' onclick="selectCity(event)" class="first-province" >'+region_list[id]["region_name"]+'</li>'
    for(var j=0; j<region_item.length; j++){
      str += '<li data-name='+region_item[j]["region_name"]+' data-id='+region_item[j]["region_id"]+' onclick="selectCity(event)" >'+region_item[j]["region_name"]+'</li>'
    }

    $(".add-other").html(str)
    $("#tab-province").removeClass("activity")
    $("#tab-citys").addClass("activity")
  }

  function selectCity(event){
    var city_id = event.target.dataset.id;
    if(select_regionId.indexOf(city_id) == -1){
      select_regionId.push(city_id)
      select_regionName.push(event.target.dataset.name)
    }
    cityTabs();
    Close();
  }

  function cityTabs(){
    var str_citys = '';
    for(var q=0; q<select_regionName.length; q++){
      if(select_regionName[q]=='西藏'){
        $("#region_xizang").attr('checked','checked');
        continue
      }
      if(select_regionName[q]=='新疆'){
        $("#region_xinjiang").attr('checked','checked');
        continue
      }
      if(select_regionName[q]=='内蒙古'){
        $("#region_neimeng").attr('checked','checked');
        continue
      }
      str_citys += '<div class="city-item">'+select_regionName[q]+'<img src="images/close.png" onclick="cityClose('+q+')"></div>'
    }

    $(".city_list").html(str_citys)
  }


  function Close(){
    $(".add-other").html("")
  }
  function cityClose(ind){
    select_regionName.splice(ind,1)
    select_regionId.splice(ind,1)
    cityTabs()
  }

  var no_city = <?php echo $this->_var['no_cityname']; ?>;
  for(var noc=0;noc<no_city.length;noc++){
    select_regionId.push(no_city[noc]["region_id"])
    select_regionName.push(no_city[noc]["region_name"])
  }
  cityTabs();

  function getProvince(){
    for(var i=0; i<region_list.length; i++){
      str_province += '<li onclick="clearCity('+i+')" >'+region_list[i]["region_name"]+'</li>'
    }
  }
  function checkXizang(){
    xizangCheck(useless_regions)
    if (document.forms['theForm'].elements['unexpress_region']) {
      document.forms['theForm'].elements['unexpress_region'].value = select_regionId;
    }
  }
  function xizangCheck(arr){
    arr.forEach(function(item,index){
      if($("#region_"+item[0]).length > 0 && $("#region_"+item[0])[0].checked){
        if(select_regionId.indexOf(item[1]) == -1) {
          select_regionId.push(item[1])
        }
      }else{
        if(select_regionId.indexOf(item[1]) != -1){
          select_regionId.forEach(function(item1,index1){
            if(item1 == item[1]) {
              select_regionId.splice(index1,1)
            }
          })
        }
      }
    })
  }
  function ClearProvince(){
    $(".tab-content").html(str_province)
    $("#tab-province").addClass("activity")
    $("#tab-citys").removeClass("activity")

  }

  function checkLoca(){
    var val = document.forms['theForm'].elements['goods_location'].value;
    var reg = /[\u4e00-\u9fa5]+/;
    if(reg.test(val))
    {
      alert(goods_error)
      document.forms['theForm'].elements['goods_location'].value = ''
    }
    if(val.length > 30)
    {
      alert(goods_location)
      document.forms['theForm'].elements['goods_location'].value = ''
    }
  }

  function checkAudit(num){
      var farme = document.getElementById('farme').style.display = 'block';
      if(num){
        document.getElementById('no_pass').style.display = 'none';
        document.getElementById('pass').style.display = 'block';
        document.getElementById('audit_type').removeAttribute('disabled')

      }else{
        document.getElementById('pass').style.display = 'none';
        document.getElementById('no_pass').style.display = 'block';

      }
  }

  function checkHien() {
    var remark = document.getElementById('remark').value;
    if (document.getElementById('no_pass').style.display == 'block') {
      if (remark.length == 0) {
        alert(no_pass_remark)
        return false;
      }
    }
    document.getElementById('farme').style.display = 'none';
    document.forms['theForm'].submit();
  }

  /*关闭按钮*/
  function btnClose(item){
    item.parentElement.parentElement.style.display = 'none';
  }

  function checkCancle(){
    document.getElementById('farme').style.display = 'none';

  }

  /*限购数量*/
  function checkOnly(num) {
      if (num) {
          document.getElementById('only_purchase').removeAttribute('disabled')
      } else {
          document.getElementById('only_purchase').setAttribute('disabled', 'disabled')
          document.getElementById('only_purchase').value = '';
      }
  }

  /*验证限购数量*/
  function checkInput() {
      if (document.getElementById('only').checked == true) {
          var only_purchase = document.getElementById('only_purchase').value;
          if (only_purchase.length == 0) {
              alert(only_no_empty)
              return false;
          }
          var reg = /^[0-9]+$/;
          if (reg.test(only_purchase)) {
              if (only_purchase <= 0) {
                  alert(input_int)
                  return false;
              }
          } else {
              alert(input_int)
              return false;
          }
          //return checkPurchase();
      }
      return true;
  }

  /*比较库存
  function checkPurchase() {
      var goods_number = document.getElementById('goods_number').value;
      var only_purchase = document.getElementById('only_purchase').value;
      if (document.getElementById('only').checked == true) {
          if (parseInt(goods_number) < parseInt(only_purchase) || goods_number.length == 0) {
              alert(only_purchase_error);
              document.getElementById('only_purchase').focus();
              return false;
          }
      }
      return true;
  }*/

  function showBorrow(){
      if(document.forms['theForm'].elements['have_borrow'] && document.forms['theForm'].elements['have_borrow'].value == 0){
          $("#have-div").hide()
          $("#company_name").val('');
          $("#borrow_ids").val('');
      }else{
          $("#have-div").show()
      }
  }

  region.getFileName = function(){return "goods.php?act=get_category"};
  region.response = function(result, text_result)
  {
      var sel = document.getElementById(result.target);

      sel.length = 1;
      sel.selectedIndex = 0;
      sel.style.display = (result.regions.length == 0 && ! region.isAdmin && result.type + 0 == 3) ? "none" : '';

      if (document.all)
      {
          sel.fireEvent("onchange");
      }
      else
      {
          var evt = document.createEvent("HTMLEvents");
          evt.initEvent('change', true, true);
          sel.dispatchEvent(evt);
      }

      if (result.regions)
      {
          for (i = 0; i < result.regions.length; i ++ )
          {
              var opt = document.createElement("OPTION");
              opt.value = result.regions[i].cat_id;
              opt.text  = result.regions[i].cat_name;

              sel.options.add(opt);
          }
      }
  }

  //图片大小验证
  function verificationPicFile(file) {
      var fileSize = 0;
      var fileMaxSize = 500;//1M
      var filePath = file.value;
      var reader = new FileReader();
      reader.readAsDataURL(file.files[0]);
      reader.onload = function (e) {
        var data = e.target.result;
        //加载图片获取图片真实宽度和高度
        var image = new Image();
        image.onload=function(){
          var width = image.width;
          var height = image.height;
          if (width == 800 && height == 800) {
            if ($(file).next('.err').length > 0) {
              goods_attr_sale_err.shift()
            }
            $(file).next('.err').remove()
          } else {
            $(file).after('<span class="err">'+ goods_attr_sale_image_not_allowed +'</span>')
            goods_attr_sale_err.push(goods_attr_sale_image_not_allowed)
          }
        };
        image.src= data;
      };
      if(filePath){
          fileSize =file.files[0].size;
          filename =file.files[0].name;
          var size = fileSize / 1024;
          if (size > fileMaxSize) {
              alert("缩略图文件大小不能大于500KB！");
              file.value = "";
              return false;
          }
      }else{
          return false;
      }
  }

  /**
    function showHhguest(){
        if(document.forms['theForm'].elements['hh_guest'] && document.forms['theForm'].elements['hh_guest'].value == 0){
            $("#hh-guest-div").hide()
            // $(".mlm-price").val('');
            // $(".copy-mlm-price").html('');
        }else{
            $("#hh-guest-div").show()
        }
    }

    function mlmShopprice(){
        var shop_price = document.forms['theForm'].elements['shop_price'].value;
        var mlm_shop_price = $("#mlm_shop_price").val();
        $("#copy_mlm_shop_price").html("￥"+mlm_shop_price)
        if(parseFloat(mlm_shop_price) != mlm_shop_price || parseFloat(shop_price) < 0){
            alert('换换客专区：'+error_price)
            $("#copy_mlm_shop_price").html("")
            return false;
        }
        if(parseFloat(mlm_shop_price) > parseFloat(shop_price)){
            alert('换换客专区：'+error_shop_price)
            $("#copy_mlm_shop_price").html("")
            return false;
        }
        return true
    }

    function mlmMoneyline() {
        var hb_line = document.forms['theForm'].elements['hb_line'].value;
        var money_line = $("#mlm_money_line").val();
        if (!hb_line) {
            $("#mlm_money_line").val('');
            alert(hb_line_not_null)
            return false
        }
        $("#copy_mlm_money_line").html("￥" + money_line)
        $("#rebate").val(money_line);
        $("#copy_rebate").html("￥" + money_line)
        if (parseFloat(money_line) != money_line || parseFloat(money_line) < 0) {
            alert('换换客专区：' + error_price)
            $("#mlm_money_line").val('');
            $("#copy_mlm_money_line").html("")
            $("#rebate").val("");
            $("#copy_rebate").html("")
            return false;
        }
        /!*if (parseFloat(money_line) > parseFloat(hb_line)) {
            alert('换换客专区：' + error_money_line)
            $("#mlm_money_line").val('');
            $("#copy_mlm_money_line").html("")
            $("#rebate").val("");
            $("#copy_rebate").html("")
            return false;
        }*!/
        return true;
    }
  */

  // 切换自定义发货时间状态
  function switchCustomDelivery(dom) {
    if($(dom).attr('index') == 'custom'){
      $('#delivery_time_custom_input').removeAttr('disabled')
    }else{
      $('#delivery_time_custom_input').attr('disabled', 'disabled')
    }
  }

  // 验证自定义发货时间
  function checkDeliveryInput() {

  }

  /*分期处理v1.0
    // 初始化分期
    var instalmentCats  = ['926', '2']; // 分期类ID
    var instalmentCount = $('.each-instalment-item').length; // 当前分期数
    var instalmentIndex = instalmentCount - 1; // 当前分期数
    showInstalment();
    changeInstalmentCount();

    function initInstalment(){
      changeInstalmentCount();
      if(isInstalment){
        $('.is-instalment').attr('value', '1');
      }else{
        $('.instalment-item-list').empty();
        instalmentCount = 0;
        $('.is-instalment').attr('value', '0');
      }
    }

    // 是否显示分期选项
    function showInstalment(){
      if(instalmentCats.indexOf($('#cat_id_2').val()) == -1){
        instalmentCount = 0;
        instalmentIndex = 0;
        isInstalment = false;
        $('.instalment').css('display', 'none');
        $('.instalment-item-list').empty();
        $('.is-instalment-selector').removeAttr('checked');
        initInstalment();
        console.log($('.is-instalment').val(), instalmentCount);
      }else{
        $('.instalment').css('display', 'table-row');
      }
    }

    // 监听分期数变动
    function changeInstalmentCount(){
      if(!isInstalment || instalmentCount >= 4){
        $('.instalment-add').css('display', 'none');
      }else{
        $('.instalment-add').css('display', 'block');
      }
    }

    // 计算分期价格
    function calInstalmentPrice(shopPrice, instalmentNum, moneyPercent){
      var instalmentShopPrice = decimal_mul(decimal_div(shopPrice, instalmentNum), moneyPercent);
      var instalmentMoneyLine = decimal_sub(decimal_div(shopPrice, instalmentNum), instalmentShopPrice);
      return {
        shopPrice: instalmentShopPrice,
        moneyLine: instalmentMoneyLine
      }
    }

    // 根据变动调整当前分期元素的价格变化
    function changeInstalment(){
      var shopPrice = $('.shop-price').val();
      var instalmentNum = $(this).parent().children('.instalment-num').val();
      var moneyPercent = Math.floor(parseFloat($(this).parent().children('.instalment-money-percent').val()) * 100) / 100;
      console.log(moneyPercent);
      $(this).parent().children('.instalment-num').attr("value", instalmentNum); // 更新DOM的value
      $(this).parent().children('.instalment-money-percent').attr("value", moneyPercent); // 更新DOM的value
      if(moneyPercent > 1){
        alert('分期现金比例最大为1');
        moneyPercent = 1;
        $(this).parent().children('.instalment-money-percent').val(1);
      }
      var instalmentPriceObj = calInstalmentPrice(shopPrice, instalmentNum, moneyPercent);
      $(this).parent().children('.instalment-shop-price-hidden').val(instalmentPriceObj.shopPrice);
      $(this).parent().children('.instalment-shop-price').html(instalmentPriceObj.shopPrice);
      $(this).parent().children('.instalment-money-line-hidden').val(instalmentPriceObj.moneyLine);
      $(this).parent().children('.instalment-money-line').html(instalmentPriceObj.moneyLine);
      console.log($(this).parent().children('.instalment-money-percent').val());
    }

    // 监听商品分类变化，判断是否显示分期选项
    $('#cat_id_2').on('blur', showInstalment);

    // 开启关闭分期
    $('.is-instalment-selector').change(function(){
      isInstalment = this.checked;
      initInstalment();
    });

    // 增加子分期
    $('.instalment-add').click(function(){
      var shopPrice = $('.shop-price').val();
      var instalmentDefaultNum = 4;
      var instalmentMoneyPercent = 1;
      // console.log(shopPrice);
      if(!shopPrice || shopPrice == 0){
        alert('请填写本店售价');
        return;
      }
      if(instalmentCount >= 4){
        alert('分期数最多4个');
        return;
      }
      instalmentIndex++;
      var instalmentPriceObj = calInstalmentPrice(shopPrice, instalmentDefaultNum, instalmentMoneyPercent);
      var instalmentItem = $(
        '<div class="each-instalment-item">' +
          '<div><?php echo $this->_var['lang']['instalment_num']; ?></div>' +
          '<select class="instalment-num" name="instalments['+ instalmentIndex +'][num]" value="4">' +
            '<option value="4">4</option>' +
            '<option value="8">8</option>' +
            '<option value="16">16</option>' +
            '<option value="48">48</option>' +
          '</select>' +
          '<div><?php echo $this->_var['lang']['instalment_money_percent']; ?></div>' +
          '<input class="instalment-money-percent" type="text" value="1.00" placeholder="现金占比最大为1">' +
          '<div class="delete-instalment"><?php echo $this->_var['lang']['label_delete']; ?></div>' +
          '<div class="current-instalment-money"><?php echo $this->_var['lang']['instalment_money_unit']; ?></div>' +
          '<input class="instalment-shop-price-hidden" type="hidden" name="instalments['+ instalmentIndex +'][shop_price]" value="'+ instalmentPriceObj.shopPrice +'">' +
          '<div class="instalment-shop-price">'+ instalmentPriceObj.shopPrice +'</div>' +
          '<div class="current-instalment-money"><?php echo $this->_var['lang']['instalment_huanbi_unit']; ?></div>' +
          '<input class="instalment-money-line-hidden" type="hidden" name="instalments['+ instalmentIndex +'][money_line]" value="'+ instalmentPriceObj.moneyLine +'">' +
          '<div class="instalment-money-line">'+ instalmentPriceObj.moneyLine +'</div>' +
          '<div class="current-instalment-money"><?php echo $this->_var['lang']['instalment_all_money']; ?></div>' +
          '<div class="instalment-money-all">'+ shopPrice +'</div>' +
        '</div>');
      $('.instalment-item-list').append(instalmentItem);
      instalmentCount++;
      changeInstalmentCount();
    });

    // 删除子分期
    $('.instalment-item-list').on('click', '.delete-instalment', function(){
      $(this).parent().remove();
      instalmentCount--;
      changeInstalmentCount();
    });

    // 监听分期价格变动
    $('.instalment-item-list').on('blur', '.instalment-num', changeInstalment);
    $('.instalment-item-list').on('blur', '.instalment-money-percent', changeInstalment);
  */
  
  // 开启关闭分期
  $('.is-instalment-selector').change(function(){
    isInstalment == 1 ? isInstalment = 0 : isInstalment = 1;
    $('.is-instalment').attr('value', isInstalment);
    if (isInstalment) {
      $(".instalment-add").show()
    } else {
      $(".instalment-add").hide()
      $(".instalment-item-list").empty()
    }
  });

  // 监听分类变化
  $('#cat_id_2').on('change', function(){
    if(instalmentCats.indexOf($('#cat_id_2').val()) == -1){
      isInstalment = 0;
      $('.is-instalment').attr('value', isInstalment);
      $('.instalment').css('display', 'none');
      $('.is-instalment-selector').removeAttr('checked');
    }else{
      $('.instalment').css('display', 'table-row');
    }
  })

  function getAreaList() {
    var shipping_id = $("#shipping_id").val();
    $.ajax({
      type : "POST",
      url : "/admin/goods.php",
      dataType:'json',
      data: {
        tpl_id:shipping_id,
        act:"ajax_shipping_area"
      },
      //请求成功
      success : function(result) {

        var s = '<table border="1" style="width: 60%">';
        s     += '<tr><td style="width: 60%;">地区</td><td style="width: 10%;">首件</td><td style="width: 10%;">费用</td><td style="width: 10%;">续件</td><td style="width: 10%;">费用</td></tr>';
        $.each(result,function(index,element){//element是data.emp json数组之中的数据
          s+="<tr>";
          s += "<td>"+element.area_list_name+"</td>";
          s += "<td>"+element.head+"</td>";
          s += "<td>"+element.head_fee+"</td>";
          s += "<td>"+element.step+"</td>";
          s += "<td>"+element.step_fee+"</td>";
          s+="</tr>";
        });

        s += "</table>";
        $("#area_list_show").html(s);

      },
      //请求失败，包含具体的错误信息
      error : function(e){
          console.log(e.status);
          console.log(e.responseText);
      }
    });
  }


  function addInstalment(ele) {
    var len = $('.each-instalment-item').length,
        $list = $('.instalment-item-list');
    if (len >= 5) {
      alert('分期数最多5个')
      return
    }
    var $item = $(buildInstalmentItem(len))
    $list.append($item)
    if ($('.each-instalment-item').length >= 5) {
      $(ele).hide()
    }
  }

  function bindInstalmentItemEvent($ele) {
  }

  /**
   * 分期项目html构建
   *
   * @param      {Number}  index   条数索引
   */ 
  function buildInstalmentItem(index) {
    var htmlStr = `
      <div class="each-instalment-item">
        <span><?php echo $this->_var['lang']['instalment_pay_cycle']; ?></span>
        <select class="instalment-method" name="instalments[${index}][method]" onChange="instalmentMethodChange(event)">
          <option value="">请选择</option>
          <option value="5">全款付</option>
          <option value="1">年付</option>
          <option value="2">半年付</option>
          <option value="3">季付</option>
          <option value="4">月付</option>
        </select>
        <span><?php echo $this->_var['lang']['instalment_num']; ?></span>
        <input class="instalment-num" type="number" value="" placeholder="请输入2-48的整数" name="instalments[${index}][num]" oninput="instalNumInput(event, 1)" onblur="instalmentChanged(event)" />
        <a class="del-ins" href="javascript:void(0);" onclick="delInstalment(event)">删除</a>
        <input class="instalment-pay-plan" type="hidden" name="instalments[${index}][payment_plan]" />
        <a class="set-ins" href="javascript:void(0);" onclick="setInstalment(event)">设定分期金额</a>
        <span class="instalments-icon err">
          <img class="icon-yes" src="images/yes.svg" width="16">
          <img class="icon-no" src="images/no.svg" width="16">
        </span>
      </div>
    `
    return htmlStr
  }

  /**
   * 只能输入数字的input
   *
   * @param      {Object}   e       点击事件
   * @param      {boolean}  isInt   是否只能是整数
   */
  function instalNumInput(e, isInt) {
    var target = e.srcElement || e.target
    if (isInt) {
      target.value = parseInt(target.value.replace(/\./g, ''))
    } else {
      target.value = parseInt(parseFloat(target.value) * 100) / 100
    }
  }

  /**
   * 付款周期选择
   *
   * @param      {Object}  e       点击事件
   */
  function instalmentMethodChange(e) {
    var $target = $(e.srcElement || e.target)
    var goodPrice = document.forms['theForm'].elements['shop_price'].value,
        hb_num = document.forms['theForm'].elements['hb_line'].value,
        money = sub(parseFloat(goodPrice),parseFloat(hb_num));

    if ($target.val() == 5) {
      $target.siblings('.instalment-num').val(1).attr('readonly', 'readonly')
      $target.siblings('.set-ins').hide()
      $target.siblings('.instalment-pay-plan').val(JSON.stringify([{cash:money, surplus:hb_num}]))
      $target.siblings('.instalments-icon').addClass('ok').removeClass('err')
    } else {
      $target.siblings('.instalment-num').val('').removeAttr('readonly', 'readonly')
      $target.siblings('.set-ins').show()
      $target.siblings('.instalment-pay-plan').val('')
      $target.siblings('.instalments-icon').addClass('err').removeClass('ok')
    }

  }

  /**
   * 删除本条分案方案
   *
   * @param      {Object}  e       点击事件
   */
  function delInstalment(e) {
    var target = e.srcElement || e.target
    $(target).parent('.each-instalment-item').remove()
    if ($('.each-instalment-item').length < 5) {
      $('.instalment-add').show()
    }
  }

  function instalmentChanged(e) {
    var $target = $(e.srcElement || e.target),
        $numInput = $target.siblings('.instalment-pay-plan')
    if (JSON.parse($numInput.val()).length == $target.val()) {
      return
    }
    $numInput.val('')
    $target.siblings('.instalments-icon').removeClass('ok').addClass('err')
  }

  /**
   * 设置分案细分方案
   *
   * @param      {Object}  e       点击事件
   */
  function setInstalment(e) {
    var target = e.srcElement || e.target,
        method = $(target).siblings('.instalment-method').val(),
        num = $(target).siblings('.instalment-num').val();

    var goodPrice = document.forms['theForm'].elements['shop_price'].value,
        hb_num = document.forms['theForm'].elements['hb_line'].value;

    if (!goodPrice || !hb_num) {
      alert('请先设置商品价格')
      return
    }

    if (!method || !num) {
      alert('请选择付款周期和期数')
      return
    } else if (method == 5 && num != 1) {
      alert('付全款时，期数必须为一期')
      return
    } else if (method != 5 && (num < 2 || num > 48)) {
      alert('期数必须为2-48的整数')
      return
    }

    var money = sub(parseFloat(goodPrice),parseFloat(hb_num)),
        data = $(target).siblings('.instalment-pay-plan').val(),
        html = '';

    if (data) {
      html = buildInstalmentForm(JSON.parse(data), false)
    } else {
      html = buildInstalmentForm(new Array(parseInt(num)), true)
    }

    $('.instalment-model').find('tbody').data('tr', $(target).parent())
    $('.instalment-model').find('tbody').empty().append($(html))
    getTotal()
    $('.instalment-model').show(300)
  }

  /**
   * 批量填充金额
   *
   * @param      {Object}  e       点击事件
   */
  function fillMoney(e) {
    var target = e.srcElement || e.target,
        forClass = $(target).attr('for'),
        val = $(target).siblings('input').val();

    if (!val) {
      alert('请输入金额')
      return
    }

    $(".instalment-model").find(`.${forClass}`).each(function () {
      $(this).val(val)
    })

    $(target).siblings('input').val('')

    getTotal()

  }
  /**
   * 获取合计金额
   */
  function getTotal() {
    var cash = 0,
        hh = 0,
        trs = $(".instalment-model").find('tbody').find('tr'),
        tfoot = $(".instalment-model").find('tfoot');

    trs.each(function() {
      var cash1 = $(this).find('.cash-input').val() || 0,
          hh1 = $(this).find('.hh-input').val() || 0;
      $(this).find('.periods-total').html(`￥${decimal_add(cash1, hh1)}`)
      cash = decimal_add(cash, cash1)
      hh = decimal_add(hh, hh1)
    })

    tfoot.find('.cash-total').html(`￥${cash}`)
    tfoot.find('.hh-total').html(`￥${hh}`)
    tfoot.find('.money-total').html(`￥${decimal_add(cash, hh)}`)
  }
  function getInstalmentPlan() {
    var $tr = $(".instalment-model tbody tr"),
        arr = [];

    $tr.each(function() {
      arr.push({
        cash: $(this).find('.cash-input').val(),
        surplus: $(this).find('.hh-input').val()
      })
    })

    return JSON.stringify(arr)
  }
  /**
   * 确定分期方案
   */
  function comfirmInstalment() {
    var isOk = true,
        $table = $(".instalment-model").find('table');

    var goodPrice = document.forms['theForm'].elements['shop_price'].value,
        hb_num = document.forms['theForm'].elements['hb_line'].value,
        money = sub(parseFloat(goodPrice),parseFloat(hb_num));

    $table.find('input').each(function() {
      if (!$(this).val()) {
        isOk = false
      }
    })

    if (!isOk) {
      alert('现金、权益金额不可为空，无需支付的请填“0”')
      return
    }

    var cash_total = parseFloat($table.find('.cash-total').html().replace(/[^\d]/g, ''))
    var hh_total = parseFloat($table.find('.hh-total').html().replace(/[^\d]/g, ''))
    if (cash_total != money || hh_total != hb_num) {
      alert('分期现金总计需等于商品现金总额， 分期权益总计需等于商品权益币总额， 请修改。')
      return
    }

    var $instalmentItem = $table.find('tbody').data('tr')
    $instalmentItem.find('.instalment-pay-plan').val(getInstalmentPlan())
    $instalmentItem.find('.instalments-icon').removeClass('err').addClass('ok')

    cancelInstalment()
  }
  function cancelInstalment() {
    $(".instalment-model").hide(300, function() {
      $(".instalment-model").find('tbody').empty();
    })
  }

  /**
   * 构建分期详细计划
   *
   * @param      {Array}   data    填充的数据（新增时为特定长度的空数组）
   * @param      {boolean} isNew   是否为新增
   * @return     {String}  html 字符串
   */
  function buildInstalmentForm(data, isNew) {
    var str = ''
    for (var i = 0; i < data.length; i++) {
      str += `
        <tr>
          <td>${i == 0 ? '首期' : '第'+(i+1)+'期'}</td>
          <td><input type="number" class="cash-input" ${view ? 'disabled="disabled"' : ''} oninput="instalNumInput(event, false)" onChange="getTotal()" ${isNew ? '' : 'value="'+data[i].cash+'"'}></td>
          <td><input type="number" class="hh-input" ${view ? 'disabled="disabled"' : ''} oninput="instalNumInput(event, false)" onChange="getTotal()" ${isNew ? '' : 'value="'+data[i].surplus+'"'}></td>
          <td class="periods-total"></td>
        </tr>
      `
    }

    return str
  }

  
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>
