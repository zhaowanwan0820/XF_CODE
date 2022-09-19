<!-- $Id: goods_search.htm 16790 2009-11-10 08:56:15Z wangleisvn $ -->
<script type="text/javascript" src="../js/calendar.php"></script>
<link href="../js/calendar/calendar.css" rel="stylesheet" type="text/css" />
<div class="form-div">
  <form action="javascript:searchGoods()" name="searchForm">
    商品名称： <input type="text" name="keyword" size="25" />
    商品货号： <input type="text" name="goods_sn" size="25" />
    <!--货位号-->
    <?php echo $this->_var['lang']['lab_goods_location']; ?> <input type="text" name="goods_location" size="25" />
    <?php if ($_GET['act'] != "trash"): ?>
    <?php if ($this->_var['admin_type'] == 0): ?>
    <!-- 分类 -->
    <br>商品分类：
    <select name="cat_id"><option value="0"><?php echo $this->_var['lang']['goods_cat']; ?></option><?php echo $this->_var['cat_list']; ?></select>
    <!-- 品牌 -->
    品牌：
    <select name="brand_id"><option value="0"><?php echo $this->_var['lang']['goods_brand']; ?></option><?php echo $this->html_options(array('options'=>$this->_var['brand_list'])); ?></select>
    <!-- 推荐 -->
    推荐类别：
    <select name="intro_type"><option value="0"><?php echo $this->_var['lang']['intro_type']; ?></option><?php echo $this->html_options(array('options'=>$this->_var['intro_list'],'selected'=>$_GET['intro_type'])); ?></select>
    <?php endif; ?>
    <?php if ($this->_var['suppliers_exists'] == 1 && $this->_var['admin_type'] == 0): ?>
    <!-- 供货商 -->
    商家：
    <select name="suppliers_id"><option value="0"><?php echo $this->_var['lang']['intro_type']; ?></option><?php echo $this->html_options(array('options'=>$this->_var['suppliers_list_name'],'selected'=>$_GET['suppliers_id'])); ?></select>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($this->_var['status'] == 0): ?>
    <br>添加时间：
    <input type="text" name="start_time" maxlength="60" size="20" id="start_time_id"  placeholder="例如 2019-01-01 00:00"/>
    <button name="start_time_btn" type="button" id="start_time_btn" onclick="return showCalendar('start_time_id', '%Y-%m-%d %H:%M', '24', false, 'start_time_btn');" class="cal"><img src="images/cal.png" alt=""></button>
    ~
    <input type="text" name="end_time" maxlength="60" size="20" id="end_time_id"  placeholder="例如 2019-01-01 00:00"/>

    <button name="end_time_btn" type="button" id="end_time_btn" onclick="return showCalendar('end_time_id', '%Y-%m-%d %H:%M', '24', false, 'end_time_btn');" class="cal"><img src="images/cal.png" alt="" ></button>
    <?php endif; ?>
    <br>价格：
    <input type="number" step="0.01" name="shop_price_down" maxlength="60" size="20" placeholder="0.00"/>
    ~
    <input type="number" step="0.01" name="shop_price_up" maxlength="60" size="20" placeholder="0.00"/>
    支付方式：
    <select name="money_line">
        <option value="0"><?php echo $this->_var['lang']['intro_type']; ?></option>
        <option value="-1">权益币支付</option>
        <option value="1">混合支付</option>
    </select>
    <?php if ($this->_var['status'] == 6): ?>
    上/下架：
    <select name="is_on_sale">
        <option value=""><?php echo $this->_var['lang']['intro_type']; ?></option>
        <option value="1">上架</option>
        <option value="0">下架</option>
    </select>
    <?php endif; ?>
    <?php if ($this->_var['admin_type'] == 0): ?>
      换换客商品：
    <select name="hh_guest" id="">
      <option value="">请选择</option>
      <option value="3">全部</option>
      <option value="1">上架</option>
      <option value="2">下架</option>
    </select>
    <?php endif; ?>
    <input type="hidden" name="status" value="<?php echo $this->_var['status']; ?>"/>
    <button type="submit" class="btn"><?php echo $this->_var['lang']['button_search']; ?></button>
    <button type="reset" class="btn" onclick="location.reload()">清空</button>
  </form>
</div>


<script>
  function searchGoods() {

<?php if ($_GET['act'] != "trash"): ?>
    <?php if ($this->_var['admin_type'] == 0): ?>
    listTable.filter['cat_id'] = document.forms['searchForm'].elements['cat_id'].value;
    listTable.filter['brand_id'] = document.forms['searchForm'].elements['brand_id'].value;
    listTable.filter['intro_type'] = document.forms['searchForm'].elements['intro_type'].value;
    listTable.filter['hh_guest'] = document.forms['searchForm'].elements['hh_guest'].value;
    <?php endif; ?>
  <?php if ($this->_var['suppliers_exists'] == 1 && $this->_var['admin_type'] == 0): ?>
    listTable.filter['suppliers_id'] = document.forms['searchForm'].elements['suppliers_id'].value;
  <?php endif; ?>
<?php endif; ?>

    listTable.filter['keyword'] = Utils.trim(document.forms['searchForm'].elements['keyword'].value);
    listTable.filter['goods_location'] = Utils.trim(document.forms['searchForm'].elements['goods_location'].value);
    listTable.filter['goods_sn'] = Utils.trim(document.forms['searchForm'].elements['goods_sn'].value);
    <?php if ($this->_var['status'] == 0): ?>
    listTable.filter['start_time'] = Utils.trim(document.forms['searchForm'].elements['start_time'].value);
    listTable.filter['end_time'] = Utils.trim(document.forms['searchForm'].elements['end_time'].value);
    if(listTable.filter['start_time'].length > 0 && listTable.filter['end_time'].length > 0 && new Date(listTable.filter['start_time']).getTime() > new Date(listTable.filter['end_time']).getTime()){
        alert("时间区间输入错误");
        return false;
    }
    <?php endif; ?>
    listTable.filter['money_line'] = Utils.trim(document.forms['searchForm'].elements['money_line'].value);
    listTable.filter['status'] = Utils.trim(document.forms['searchForm'].elements['status'].value);
    listTable.filter['shop_price_down'] = Utils.trim(document.forms['searchForm'].elements['shop_price_down'].value);
    listTable.filter['shop_price_up'] = Utils.trim(document.forms['searchForm'].elements['shop_price_up'].value);
    if(listTable.filter['shop_price_down'].length > 0 && listTable.filter['shop_price_up'].length > 0 && parseFloat(listTable.filter['shop_price_down']) > parseFloat(listTable.filter['shop_price_up'])){
        alert("价格区间输入错误");
        return false;
    }
    <?php if ($this->_var['status'] == 6): ?>
    listTable.filter['is_on_sale'] = Utils.trim(document.forms['searchForm'].elements['is_on_sale'].value);
    <?php endif; ?>
    listTable.filter['page'] = 1;

    listTable.loadList();
  }
</script>

