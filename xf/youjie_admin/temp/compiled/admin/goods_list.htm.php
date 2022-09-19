<!-- $Id: goods_list.htm 17126 2010-04-23 10:30:26Z liuhui $ -->

<?php if ($this->_var['full_page']): ?>
<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,listtable.js')); ?>

<div id="tabbar-div">
    <p>
      <span class="tab-back <?php if ($this->_var['status'] == 4): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=4<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">上架中（<?php echo $this->_var['goods_status']['sale_on']; ?>）</a></span>
      <span class="tab-back <?php if ($this->_var['status'] == 5): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=5<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">已下架（<?php echo $this->_var['goods_status']['sale_off']; ?>）</a></span>
      <span class="tab-back <?php if ($this->_var['status'] == 6): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=6<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">已售罄（<?php echo $this->_var['goods_status']['sold_out']; ?>）</a></span>
      <span class="tab-back <?php if ($this->_var['status'] == 1): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=1<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">审核中（<?php echo $this->_var['goods_status']['is_check_ing']; ?>）</a></span>
      <span class="tab-back <?php if ($this->_var['status'] == 2): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=2<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">审核通过（<?php echo $this->_var['goods_status']['is_check_on']; ?>）</a></span>
      <span class="tab-back <?php if ($this->_var['status'] == 3): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=3<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">审核不通过（<?php echo $this->_var['goods_status']['is_check_off']; ?>）</a></span>
      <span class="tab-back <?php if ($this->_var['status'] == 0): ?>cur<?php endif; ?>"><a href="goods.php?act=list&status=0<?php if ($this->_var['add_handler']): ?>&extension_code=virtual_card<?php endif; ?>">草稿（<?php echo $this->_var['goods_status']['is_check']; ?>）</a></span>
    </p>
</div>
<!-- 商品搜索 -->
<?php echo $this->fetch('goods_search.htm'); ?>
<!-- 商品列表 -->
<form method="post" action="" name="listForm" onsubmit="return confirmSubmit(this)">
  <!-- start goods list -->
  <div class="list-div" id="listDiv">
<?php endif; ?>
      <?php if ($this->_var['status'] == 0): ?>
      <button class="btn" style="width: 90px;margin-left: 91%;" type="submit" id="btnSubmit" name="type" value="submit_audit" disabled="true"><?php echo $this->_var['lang']['submit_audit']; ?></button>
      <?php endif; ?>
<table cellpadding="3" cellspacing="1">
  <tr>
    <th class="checks"><input onclick='listTable.selectAll(this, "checkboxes")' type="checkbox"></th>
    <th><a href="javascript:listTable.sort('goods_id'); "><?php echo $this->_var['lang']['record_id']; ?></a><?php echo $this->_var['sort_goods_id']; ?></th>
    <th>商品图</th>
    <th><a href="javascript:listTable.sort('goods_name'); "><?php echo $this->_var['lang']['goods_name']; ?></a><?php echo $this->_var['sort_goods_name']; ?></th>
    <th><a href="javascript:listTable.sort('goods_sn'); "><?php echo $this->_var['lang']['goods_sn']; ?></a><?php echo $this->_var['sort_goods_sn']; ?></th>
    <th><a href="javascript:listTable.sort('shop_price'); "><?php echo $this->_var['lang']['shop_price']; ?></a><?php echo $this->_var['sort_shop_price']; ?></th>
    <th>支付方式</th>
    <?php if ($this->_var['status'] == 6): ?>
    <th><?php echo $this->_var['lang']['is_on_sale']; ?></th>
    <?php endif; ?>
    <?php if ($this->_var['status'] == 4 && $this->_var['admin_type'] == 0): ?>
    <th><a href="javascript:listTable.sort('is_best'); "><?php echo $this->_var['lang']['is_best']; ?></a><?php echo $this->_var['sort_is_best']; ?></th>
    <th><a href="javascript:listTable.sort('is_hot'); "><?php echo $this->_var['lang']['is_hot']; ?></a><?php echo $this->_var['sort_is_hot']; ?></th>
    <th><a href="javascript:listTable.sort('is_new'); "><?php echo $this->_var['lang']['is_new']; ?></a><?php echo $this->_var['sort_is_new']; ?></th>
    <th><a href="javascript:listTable.sort('sort_order'); "><?php echo $this->_var['lang']['sort_order']; ?></a><?php echo $this->_var['sort_sort_order']; ?></th>
    <?php endif; ?>
    <?php if ($this->_var['use_storage']): ?>
    <th><a href="javascript:listTable.sort('goods_number'); "><?php echo $this->_var['lang']['goods_number']; ?></a><?php echo $this->_var['sort_goods_number']; ?></th>
    <?php endif; ?>
    <?php if ($this->_var['status'] == 4 || $this->_var['status'] == 5 || $this->_var['status'] == 6): ?>
    <th>总销量</th>
    <?php endif; ?>
    <th><a href="javascript:listTable.sort('add_time'); ">添加时间</a><?php echo $this->_var['sort_add_time']; ?></th>
    <?php if ($this->_var['status'] != 0 && $this->_var['status'] != 6): ?>
    <th><?php if ($this->_var['status'] == 1): ?>提审时间<?php elseif ($this->_var['status'] == 2 || $this->_var['status'] == 3): ?>审核时间<?php elseif ($this->_var['status'] == 4): ?>上架时间<?php elseif ($this->_var['status'] == 5): ?>下架时间<?php endif; ?></th>
    <?php if ($this->_var['status'] == 3 || $this->_var['status'] == 5): ?>
    <th>备注</th>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($this->_var['admin_type'] == 0): ?>
    <th>商家</th>
    <?php endif; ?>
    <th><?php echo $this->_var['lang']['handler']; ?></th>
  <tr>
  <?php $_from = $this->_var['goods_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'goods');if (count($_from)):
    foreach ($_from AS $this->_var['goods']):
?>
  <tr>
    <td><input type="checkbox" name="checkboxes[]" value="<?php echo $this->_var['goods']['goods_id']; ?>"></td>
    <td><?php echo $this->_var['goods']['goods_id']; ?></td>
    <td><a href="/<?php echo $this->_var['goods']['goods_img']; ?>" target="_blank"><img src="/<?php echo $this->_var['goods']['goods_img']; ?>" height="50" width="50"/></a></td>
    <td class="first-cell" style="<?php if ($this->_var['goods']['is_promote']): ?>color:red;<?php endif; ?>"><span title="<?php echo htmlspecialchars($this->_var['goods']['goods_name']); ?>"><a href="../goods.php?hid=<?php echo $this->_var['goods']['goods_id']; ?>&modproductkey=<?php echo $this->_var['goods']['preview_key']; ?>" target="_blank" title="预览"><?php echo htmlspecialchars($this->_var['goods']['goods_name']); ?></a></span></td>
    <td><span><?php echo $this->_var['goods']['goods_sn']; ?></span></td>
    <td align="right"><span><?php echo $this->_var['goods']['shop_price']; ?></span></td>
    <td align="right">
        <span><?php echo $this->_var['goods']['pay_type']; ?><?php if ($this->_var['goods']['money_line'] >= 0): ?><br/>权益币：<?php echo $this->_var['goods']['money_line']; ?><br/>现金：<?php echo $this->_var['goods']['pay_type_money']; ?><?php endif; ?></span>
    </td>
    <?php if ($this->_var['status'] == 6): ?>
    <td align="center"><img src="images/<?php if ($this->_var['goods']['is_on_sale']): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20"/></td>
    <?php endif; ?>
    <?php if ($this->_var['status'] == 4 && $this->_var['admin_type'] == 0): ?>
    <td align="center"><img src="images/<?php if ($this->_var['goods']['is_best']): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_best', <?php echo $this->_var['goods']['goods_id']; ?>)" /></td>
    <td align="center"><img src="images/<?php if ($this->_var['goods']['is_hot']): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_hot', <?php echo $this->_var['goods']['goods_id']; ?>)" /></td>
    <td align="center"><img src="images/<?php if ($this->_var['goods']['is_new']): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_new', <?php echo $this->_var['goods']['goods_id']; ?>)" /></td>
    <td align="center"><span onclick="listTable.edit(this, 'edit_sort_order', <?php echo $this->_var['goods']['goods_id']; ?>)""><?php echo $this->_var['goods']['sort_order']; ?></span></td>
    <?php endif; ?>
    <?php if ($this->_var['use_storage']): ?>
    <td align="right"><span><?php echo $this->_var['goods']['goods_number']; ?></span></td>
    <?php endif; ?>
    <?php if ($this->_var['status'] == 4 || $this->_var['status'] == 5 || $this->_var['status'] == 6): ?>
    <td align="right"><span><?php echo $this->_var['goods']['sale_number']; ?></span></td>
    <?php endif; ?>
    <td align="right"><span><?php echo $this->_var['goods']['add_time_format']; ?></span></td>
    <?php if ($this->_var['status'] != 0 && $this->_var['status'] != 6): ?>
      <td align="right"><span><?php if ($this->_var['status'] == 1 || $this->_var['status'] == 2 || $this->_var['status'] == 3): ?><?php echo $this->_var['goods']['check_time_format']; ?><?php else: ?><?php echo $this->_var['goods']['sale_time_format']; ?><?php endif; ?></span></td>
      <?php if ($this->_var['status'] == 3 || $this->_var['status'] == 5): ?>
        <td align="right"><span><?php echo $this->_var['goods']['action_remark']; ?></span></td>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($this->_var['admin_type'] == 0): ?>
    <td align="right"><span><?php echo $this->_var['goods']['suppliers_name']; ?></span></td>
    <?php endif; ?>
    <td align="center">

        <?php if ($this->_var['status'] != 0 || ( $this->_var['admin_type'] == 1 && $this->_var['status'] == 1 )): ?>
          <a href="goods.php?act=view&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>" title="<?php echo $this->_var['lang']['view']; ?>"><?php echo $this->_var['lang']['view']; ?></a>
        <?php endif; ?>
        <!--<a href="goods.php?act=copy&goods_id=<?php echo $this->_var['goods']['goods_id']; ?><?php if ($this->_var['code'] != 'real_goods'): ?>&extension_code=<?php echo $this->_var['code']; ?><?php endif; ?>" title="<?php echo $this->_var['lang']['copy']; ?>"><?php echo $this->_var['lang']['copy']; ?></a>-->
        <?php if ($this->_var['status'] == 0 || $this->_var['status'] == 3 || $this->_var['status'] == 5 || $this->_var['admin_type'] == 0): ?>
        <a href="" onclick="checkAttr(<?php echo $this->_var['goods']['goods_id']; ?>);return false" title="<?php echo $this->_var['lang']['edit']; ?>"><?php echo $this->_var['lang']['edit']; ?></a>
        <?php endif; ?>
        <?php if ($this->_var['status'] == 0 || $this->_var['status'] == 3 || $this->_var['status'] == 5): ?>
        <a href="goods.php?act=submit_audit&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>" title="<?php echo $this->_var['lang']['submit_audit']; ?>" onclick="return confirm(submit_audit)"><?php echo $this->_var['lang']['submit_audit']; ?></a>
        <?php endif; ?>
        <?php if ($this->_var['status'] == 1 && $this->_var['admin_type'] == 0): ?>
        <a href="goods.php?act=audit&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>" title="<?php echo $this->_var['lang']['audit']; ?>"><?php echo $this->_var['lang']['audit']; ?></a>
        <?php endif; ?>
        <?php if ($this->_var['goods']['is_check'] == 2): ?>
            <?php if ($this->_var['goods']['is_on_sale'] == 1): ?>
                <?php if ($this->_var['status'] != 2): ?>
            <a href="" title="<?php echo $this->_var['lang']['not_on_sale']; ?>" name="not_on_sale" onclick="if(checkReason(this))listTable.sale(<?php echo $this->_var['goods']['is_on_sale']; ?>, 'toggle_on_sale', <?php echo $this->_var['goods']['goods_id']; ?>)"><?php echo $this->_var['lang']['not_on_sale']; ?></a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($this->_var['admin_type'] == 0 || ( $this->_var['admin_type'] == 1 && ( $this->_var['status'] == 2 || $this->_var['status'] != 5 ) )): ?>
            <a href="javascript:void(0);" title="<?php echo $this->_var['lang']['is_on_sale']; ?>" onclick="showSale(<?php echo $this->_var['goods']['is_on_sale']; ?>, 'toggle_on_sale', <?php echo $this->_var['goods']['goods_id']; ?>)"><?php echo $this->_var['lang']['is_on_sale']; ?></a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($this->_var['status'] == 2 || $this->_var['status'] == 4 || $this->_var['status'] == 6 || $this->_var['status'] == 5): ?>
        <a href="goods.php?act=set_stock&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>" title="库存调整">库存调整</a>
        <?php endif; ?>
        <?php if ($this->_var['status'] == 1): ?>
        <a href="goods.php?act=cancle_audit&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>"  title="撤销">撤销</a>
        <?php endif; ?>
        <?php if ($this->_var['status'] == 0): ?>
        <a href="javascript:;" onclick="listTable.remove(<?php echo $this->_var['goods']['goods_id']; ?>, '<?php echo $this->_var['lang']['trash_goods_confirm']; ?>')" title="删除">删除</a>
        <?php endif; ?>
      <?php if ($this->_var['specifications'] [ $this->_var['goods']['goods_type'] ] != ''): ?><a href="goods.php?act=product_list&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>&status=<?php echo $this->_var['status']; ?>" title="<?php echo $this->_var['lang']['item_list']; ?>"><?php echo $this->_var['lang']['item_list']; ?></a><?php else: ?><img src="images/empty.gif" width="16" height="16" border="0"><?php endif; ?>
      <?php if ($this->_var['add_handler']): ?>
        |
        <?php $_from = $this->_var['add_handler']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'handler');if (count($_from)):
    foreach ($_from AS $this->_var['handler']):
?>
        <a href="<?php echo $this->_var['handler']['url']; ?>&goods_id=<?php echo $this->_var['goods']['goods_id']; ?>" title="<?php echo $this->_var['handler']['title']; ?>"><?php echo $this->_var['handler']['title']; ?></a>
        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; else: ?>
  <tr><td class="no-records" colspan="10"><?php echo $this->_var['lang']['no_records']; ?></td></tr>
  <?php endif; unset($_from); ?><?php $this->pop_vars();; ?>
</table>
<!-- end goods list -->

<!-- 分页 -->
<table id="page-table" cellspacing="0">
  <tr>
    <td style="text-align: left">
      <input type="hidden" name="act" value="batch" />
      <!--<select name="type" id="selAction" onchange="changeAction()">-->
          <!--<option value=""><?php echo $this->_var['lang']['select_please']; ?></option>-->
          <!--<option value="submit_audit"><?php echo $this->_var['lang']['submit_audit']; ?></option>-->
          <!--&lt;!&ndash;<option value="on_sale"><?php echo $this->_var['lang']['on_sale']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="not_on_sale"><?php echo $this->_var['lang']['not_on_sale']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="best"><?php echo $this->_var['lang']['best']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="not_best"><?php echo $this->_var['lang']['not_best']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="new"><?php echo $this->_var['lang']['new']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="not_new"><?php echo $this->_var['lang']['not_new']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="hot"><?php echo $this->_var['lang']['hot']; ?></option>&ndash;&gt;-->
          <!--&lt;!&ndash;<option value="not_hot"><?php echo $this->_var['lang']['not_hot']; ?></option>&ndash;&gt;-->
      <!--</select>-->

      <!--<?php if ($this->_var['code'] != 'real_goods'): ?>-->
      <!--<input type="hidden" name="extension_code" value="<?php echo $this->_var['code']; ?>" />-->
      <!--<?php endif; ?>-->
      <!--<input type="hidden" name="no_on_sale_reason"/>-->

      </td>
    <td align="right" nowrap="true">
    <?php echo $this->fetch('page.htm'); ?>
    </td>
  </tr>
</table>

<?php if ($this->_var['full_page']): ?>
</div>

</form>
<div id="onSaleMark" style="position: fixed; top: 0;left: 0; width: 99%;height: 99%;background: rgba(0,0,0,0.5);display: none;"></div>
<div id="onSalePopup" style="position: fixed;top: 100px;left: 50%;margin-left: -240px;background: #fff;border: 1px #e8e8e8 solid;width: 480px;min-height: 200px; padding: 10px;display: none;">
  <h3 style="margin: 0">上架设置</h3>
  <p>
    <input type="checkbox" id="time_box" name="is_sale_time" onclick="timeBox()">
    <span>预售时间设置</span>
    <span class="time-false">(未设置预售时间，立即上架销售)</span>
    <span class="time-true" style="display: none">(请设置预售时间)</span>
  </p>
  <div class="time-pick-wrapper" style="display: none">
    <input type="text" name="sale_time" maxlength="60" size="20" id="sale_time_id"  placeholder="例如 2001-01-01 00:00"/>
     <button type="button" id="sale_time_btn" onclick="return showCalendar('sale_time_id', '%Y-%m-%d %H:%M', '24', false, 'sale_time_btn');" class="cal">
      <img src="images/cal.png" alt="">
    </button>
  </div>
  <div class="btn-wrapper">
    <button class="btn" onclick="confirmSale()">确认</button>
    <button class="btn" onclick="hideSale()">放弃操作</button>
  </div>
</div>

<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.js"></script>
<script type="text/javascript">
  listTable.recordCount = <?php echo $this->_var['record_count']; ?>;
  listTable.pageCount = <?php echo $this->_var['page_count']; ?>;

  <?php $_from = $this->_var['filter']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?>
  listTable.filter.<?php echo $this->_var['key']; ?> = '<?php echo $this->_var['item']; ?>';
  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
  
  onload = function()
  {
    $("#tabbar-div span").click(function() {
      $("#tabbar-div span").eq($(this).index()).addClass("cur").siblings().removeClass('cur');
    });
    startCheckOrder(); // 开始检查订单
    document.forms['listForm'].reset();
  }

  /**
   * @param: bool ext 其他条件：用于转移分类
   */
  function confirmSubmit(frm, ext)
  {
      if (frm.elements['type'].value == 'trash')
      {
          return confirm(batch_trash_confirm);
      }
      else if (frm.elements['type'].value == 'not_on_sale')
      {
         var reason = prompt(batch_no_on_sale);
         if(reason.length == 0){
             alert(empty_reason);
             confirmSubmit(frm,false);
         }else{
             frm.elements['no_on_sale_reason'].value = reason
             return true;
         }
      }
      else if (frm.elements['type'].value == 'move_to')
      {
          ext = (ext == undefined) ? true : ext;
          return ext && frm.elements['target_cat'].value != 0;
      }
      else if (frm.elements['type'].value == '')
      {
          return false;
      }
      else
      {
          return true;
      }
  }

  function changeAction()
  {
      var frm = document.forms['listForm'];

      // 切换分类列表的显示
      frm.elements['target_cat'].style.display = frm.elements['type'].value == 'move_to' ? '' : 'none';

      // <?php if ($this->_var['suppliers_list'] > 0): ?>
      //frm.elements['suppliers_id'].style.display = frm.elements['type'].value == 'suppliers_move_to' ? '' : 'none';
      // <?php endif; ?>

      if (!document.getElementById('btnSubmit').disabled &&
          confirmSubmit(frm, false))
      {
          frm.submit();
      }
  }

  function checkReason(tag){
    var reason = prompt(batch_no_on_sale)
      if(reason.length == 0){
         alert(empty_reason);
         checkReason();
      }else{
         document.getElementsByName('not_on_sale') .value = reason;
         return true;
      }
  }

  function checkAttr(id) {
      Ajax.call('goods.php?act=old_attr&goods_id=' + id, '', attrResponse, 'GET', 'JSON');
  }
  function attrResponse(result) {
      var status = <?php echo $this->_var['status']; ?>;
      $res = result.content.confirm;
      if($res){
          if(confirm("由于更新后台商品分类，以及分类下销售属性。当您对5月14日之前创建的商品进行【编辑】时，系统会清空当前商品已存在的多规格SKU，需要您根据新的销售属性，从新添加所需要的多规格SKU。取消不会清空已有多规格SKU。造成不便敬请谅解。\n" +
              "是否进行编辑？")){
              location.href = "goods.php?act=edit&goods_id=" + result.content.id + "&is_del=1" + "&status=" + status + "&extension_code=";
          }
      }else{
          location.href = "goods.php?act=edit&goods_id=" + result.content.id + "&is_del=0"+ "&status=" + status  + "&extension_code=";
      }
  }
  function showSale(obj, act, id) {
    window.sale = [obj, act, id]
    $("#onSaleMark").show()
    $("#onSalePopup").show()
  }
  function timeBox() {
    var time_box = $("#time_box")[0].checked
    if (time_box) {
      $(".time-true").show()
      $(".time-false").hide()
      $(".time-pick-wrapper").show()
    }else{
      $(".time-true").hide()
      $(".time-false").show()
      $(".time-pick-wrapper").hide()
    }
  }
  function confirmSale() {
    var sale_time = $("#sale_time_id")[0].value
    var time_box = $("#time_box")[0].checked
    if(!sale_time) sale_time=0
    if(time_box && !sale_time){
      alert("请填写预售时间")
      return
    }
    if(sale_time){
      var time_str = sale_time.replace(/-/g,'/')
      var time_code = new Date(time_str).getTime()
      var time_now = new Date().getTime()
      if(time_code<=time_now){
        alert("预售时间不得小于当前时间")
        return
      }
    }
    listTable.sale(window.sale[0], window.sale[1], window.sale[2], sale_time)
  }
  function hideSale(){
    window.sale = []
    $("#time_box")[0].checked = false
    $("#sale_time_id")[0].value = ''
    $(".time-pick-wrapper").hide()
    $("#onSalePopup").hide()
    $("#onSaleMark").hide()
  }


</script>
<?php echo $this->fetch('pagefooter.htm'); ?>
<?php endif; ?>
