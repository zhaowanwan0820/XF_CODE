<!-- $Id: category_list.htm 17019 2010-01-29 10:10:34Z liuhui $ -->
<?php if ($this->_var['full_page']): ?>
<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,listtable.js')); ?>

<form method="post" action="" name="listForm">
<!-- start ad position list -->
<div class="list-div" id="listDiv">
<?php endif; ?>

<table width="100%" cellspacing="1" cellpadding="2" id="list-table">
  <tr>
    <th><?php echo $this->_var['lang']['cat_name']; ?></th>
    <th>分类ID</th>
    <th><?php echo $this->_var['lang']['goods_number']; ?></th>
    <th><?php echo $this->_var['lang']['measure_unit']; ?></th>
    <th><?php echo $this->_var['lang']['nav']; ?></th>
    <th><?php echo $this->_var['lang']['is_show']; ?></th>
    <th><?php echo $this->_var['lang']['short_grade']; ?></th>
    <th><?php echo $this->_var['lang']['sort_order']; ?></th>
    <th><?php echo $this->_var['lang']['handler']; ?></th>
  </tr>
  <?php $_from = $this->_var['cat_info']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'cat');if (count($_from)):
    foreach ($_from AS $this->_var['cat']):
?>
  <?php if (! $this->_var['cat']['level']): ?>
  <tr align="center" class="<?php echo $this->_var['cat']['level']; ?>" id="<?php echo $this->_var['cat']['level']; ?>_<?php echo $this->_var['cat']['cat_id']; ?>" >
    <td style="text-align: left" class="first-cell" >
      <?php if ($this->_var['cat']['is_leaf'] != 1): ?>
      <img src="images/menu_plus.gif" id="icon_<?php echo $this->_var['cat']['level']; ?>_<?php echo $this->_var['cat']['cat_id']; ?>" width="9" height="9" border="0" style="background-color:black;margin-left:<?php echo $this->_var['cat']['level']; ?>em" onclick="rowClicked(this)" />
      <?php else: ?>
      <img src="images/menu_arrow.gif" width="9" height="9" border="0" style="margin-left:<?php echo $this->_var['cat']['level']; ?>em" />
      <?php endif; ?>
      <span><a href="goods.php?act=list&cat_id=<?php echo $this->_var['cat']['cat_id']; ?>"><?php echo $this->_var['cat']['cat_name']; ?></a></span>
      <?php if ($this->_var['cat']['cat_image']): ?>
      <img src="../<?php echo $this->_var['cat']['cat_image']; ?>" border="0" style="vertical-align:middle;" width="60px" height="21px">
      <?php endif; ?>
    </td>
    <td width="10%"><?php echo $this->_var['cat']['cat_id']; ?></td>
    <td width="10%"><?php echo $this->_var['cat']['goods_num']; ?></td>
    <td width="10%"><span onclick="listTable.edit(this, 'edit_measure_unit', <?php echo $this->_var['cat']['cat_id']; ?>)"><!-- <?php if ($this->_var['cat']['measure_unit']): ?> --><?php echo $this->_var['cat']['measure_unit']; ?><!-- <?php else: ?> -->&nbsp;&nbsp;&nbsp;&nbsp;<!-- <?php endif; ?> --></span></td>
    <td width="10%"><img src="images/<?php if ($this->_var['cat']['show_in_nav'] == '1'): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_show_in_nav', <?php echo $this->_var['cat']['cat_id']; ?>)" /></td>
    <td width="10%"><img src="images/<?php if ($this->_var['cat']['is_show'] == '1'): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_is_show', <?php echo $this->_var['cat']['cat_id']; ?>)" /></td>
    <td><span onclick="listTable.edit(this, 'edit_grade', <?php echo $this->_var['cat']['cat_id']; ?>)"><?php echo $this->_var['cat']['grade']; ?></span></td>
    <td width="10%" align="right">
      <a onclick="removeEle(this, 'edit_sort_order', <?php echo $this->_var['cat']['cat_id']; ?>, -1)">上移</a>
      <a onclick="removeEle(this, 'edit_sort_order', <?php echo $this->_var['cat']['cat_id']; ?>, 1)">下移</a>
    </td>
    <td width="24%" align="center">
      <a href="attribute.php?act=list&category_id=<?php echo $this->_var['cat']['cat_id']; ?>">属性列表</a> |
      <a href="category.php?act=move&cat_id=<?php echo $this->_var['cat']['cat_id']; ?>"><?php echo $this->_var['lang']['move_goods']; ?></a> |
      <a href="category.php?act=edit&amp;cat_id=<?php echo $this->_var['cat']['cat_id']; ?>"><?php echo $this->_var['lang']['edit']; ?></a> |
      <a href="javascript:;" onclick="listTable.remove(<?php echo $this->_var['cat']['cat_id']; ?>, '<?php echo $this->_var['lang']['drop_confirm']; ?>')" title="<?php echo $this->_var['lang']['remove']; ?>"><?php echo $this->_var['lang']['remove']; ?></a>
    </td>
  </tr>
  <?php else: ?>
  <tr align="center" class="<?php echo $this->_var['cat']['level']; ?>" id="<?php echo $this->_var['cat']['level']; ?>_<?php echo $this->_var['cat']['cat_id']; ?>" style="display: none">
    <td style="text-align: left" class="first-cell" >
      <?php if ($this->_var['cat']['is_leaf'] != 1): ?>
      <img src="images/menu_minus.gif" id="icon_<?php echo $this->_var['cat']['level']; ?>_<?php echo $this->_var['cat']['cat_id']; ?>" width="9" height="9" border="0" style="background-color:black;margin-left:calc(<?php echo $this->_var['cat']['level']; ?>em * 2)" onclick="rowClicked(this)" />
      <?php else: ?>
      <img src="images/menu_arrow.gif" width="9" height="9" border="0" style="margin-left:<?php echo $this->_var['cat']['level']; ?>em" />
      <?php endif; ?>
      <span><a href="goods.php?act=list&cat_id=<?php echo $this->_var['cat']['cat_id']; ?>"><?php echo $this->_var['cat']['cat_name']; ?></a></span>
      <?php if ($this->_var['cat']['cat_image']): ?>
      <img src="../<?php echo $this->_var['cat']['cat_image']; ?>" border="0" style="vertical-align:middle;" width="60px" height="21px">
      <?php endif; ?>
    </td>
    <td width="10%"><?php echo $this->_var['cat']['cat_id']; ?></td>
    <td width="10%"><?php echo $this->_var['cat']['goods_num']; ?></td>
    <td width="10%"><span onclick="listTable.edit(this, 'edit_measure_unit', <?php echo $this->_var['cat']['cat_id']; ?>)"><!-- <?php if ($this->_var['cat']['measure_unit']): ?> --><?php echo $this->_var['cat']['measure_unit']; ?><!-- <?php else: ?> -->&nbsp;&nbsp;&nbsp;&nbsp;<!-- <?php endif; ?> --></span></td>
    <td width="10%"><img src="images/<?php if ($this->_var['cat']['show_in_nav'] == '1'): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_show_in_nav', <?php echo $this->_var['cat']['cat_id']; ?>)" /></td>
    <td width="10%"><img src="images/<?php if ($this->_var['cat']['is_show'] == '1'): ?>yes<?php else: ?>no<?php endif; ?>.svg" width="20" onclick="listTable.toggle(this, 'toggle_is_show', <?php echo $this->_var['cat']['cat_id']; ?>)" /></td>
    <td><span onclick="listTable.edit(this, 'edit_grade', <?php echo $this->_var['cat']['cat_id']; ?>)"><?php echo $this->_var['cat']['grade']; ?></span></td>
    <td width="10%" align="right">
      <a onclick="removeEle(this, 'edit_sort_order', <?php echo $this->_var['cat']['cat_id']; ?>, -1)">上移</a>
      <a onclick="removeEle(this, 'edit_sort_order', <?php echo $this->_var['cat']['cat_id']; ?>, 1)">下移</a>
    </td>
    <td width="24%" align="center">
      <a href="attribute.php?act=list&category_id=<?php echo $this->_var['cat']['cat_id']; ?>">属性列表</a> |
      <a href="category.php?act=move&cat_id=<?php echo $this->_var['cat']['cat_id']; ?>"><?php echo $this->_var['lang']['move_goods']; ?></a> |
      <a href="category.php?act=edit&amp;cat_id=<?php echo $this->_var['cat']['cat_id']; ?>"><?php echo $this->_var['lang']['edit']; ?></a> |
      <a href="javascript:;" onclick="listTable.remove(<?php echo $this->_var['cat']['cat_id']; ?>, '<?php echo $this->_var['lang']['drop_confirm']; ?>')" title="<?php echo $this->_var['lang']['remove']; ?>"><?php echo $this->_var['lang']['remove']; ?></a>
    </td>
  </tr>

  <?php endif; ?>

  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
</table>
<?php if ($this->_var['full_page']): ?>
</div>
</form>


<script language="JavaScript">
<!--

onload = function()
{
  // 开始检查订单
  startCheckOrder();
}

var imgPlus = new Image();
imgPlus.src = "images/menu_plus.gif";

/**
 * 折叠分类列表
 */
function rowClicked(obj)
{
  // 当前图像
  img = obj;
  // 取得上二级tr>td>img对象
  obj = obj.parentNode.parentNode;
  // 整个分类列表表格
  var tbl = document.getElementById("list-table");
  // 当前分类级别
  var lvl = parseInt(obj.className);
  // 是否找到元素
  var fnd = false;
  var sub_display = img.src.indexOf('menu_minus.gif') > 0 ? 'none' : (Browser.isIE) ? 'block' : 'table-row' ;
  // 遍历所有的分类
  for (i = 0; i < tbl.rows.length; i++)
  {
      var row = tbl.rows[i];
      if (row == obj)
      {
          // 找到当前行
          fnd = true;
          //document.getElementById('result').innerHTML += 'Find row at ' + i +"<br/>";
      }
      else
      {
          if (fnd == true)
          {
              var cur = parseInt(row.className);
              var icon = 'icon_' + row.id;
              if (cur > lvl)
              {
                  row.style.display = sub_display;
                  if (sub_display != 'none')
                  {
                      var iconimg = document.getElementById(icon);
                      iconimg.src = iconimg.src.replace('plus.gif', 'minus.gif');
                  }
              }
              else
              {
                  fnd = false;
                  break;
              }
          }
      }
  }

  for (i = 0; i < obj.cells[0].childNodes.length; i++)
  {
      var imgObj = obj.cells[0].childNodes[i];
      if (imgObj.tagName == "IMG" && imgObj.src != 'images/menu_arrow.gif')
      {
          imgObj.src = (imgObj.src == imgPlus.src) ? 'images/menu_minus.gif' : imgPlus.src;
      }
  }
}

/*
 * 移动商品分类发起请求
 */

function removeEle(obj, act, id, status) {
  var val = status == -1 ? 1 : 0
  Ajax.call(listTable.url, "act="+act+"&val=" + val + "&id=" +id, function(res){
    warnMsg(res.content)
    if(!res.error_code){
      remove(obj, status)
    }
  }, "POST", "JSON", false);
}

/*
 *  移动商品分类（html部分）
 */
function remove(obj, status) {
  var obj_tr = obj.parentNode.parentNode  //当前行
  var obj_table = obj.parentNode.parentNode.parentNode  // 整个table标签
  var trs = obj_table.children //所有行
  var index_from = [].indexOf.call(trs,obj_tr)  // 当前行下标

  if(level(obj_tr) === 3){
    if(index_from+status < trs.length && level(trs[index_from+status]) === 3){
      var new_table = document.createElement("table")
      var new_tr = document.createElement("tr")
      new_tr = trs[index_from].cloneNode(true)
      new_table.appendChild(new_tr)
      new_tr = trs[index_from+status].cloneNode(true)
      new_table.appendChild(new_tr)
      obj_table.replaceChild(new_table.children[0],trs[index_from+status])
      obj_table.replaceChild(new_table.children[0],trs[index_from])
    }else{
      // warnMsg()
    }
  }else{
    var index_end = getNextEle(trs,index_from,status) // 目标行下标
    if(!index_end){
      // warnMsg()
      return
    }

    if(index_from === index_end || index_end === trs.length){
      warnMsg()
      return
    }

    // 获取对调行开始和结束的下标
    var start,middle,end;
    if(status>0){ // 下移
      start = index_from
      middle = index_end
      end = getTargetEle(trs,middle,1)
    }else{  // 上移
      start = index_end
      middle = index_from
      end = getTargetEle(trs,middle,1)
    }

    // 将middle上下两侧的 行 对调，放入新的table标签，然后替换原table内的标签
    var new_table = document.createElement("table")
    var new_tr = document.createElement("tr")
    for (var j = middle; j < end; j++) {
      new_tr = trs[j].cloneNode(true)
      new_table.appendChild(new_tr)
    }
    for (var k = start; k < middle; k++) {
      new_tr = trs[k].cloneNode(true)
      new_table.appendChild(new_tr)
    }

    for (var n = start; n < end; n++) {
      obj_table.replaceChild(new_table.children[0],trs[n])
    }
  }
}

function level(obj) { //获取分类等级（1/2/3）
  if(obj.getAttribute('id')){
    return obj.getAttribute('id').charAt(0)-0+1
  }else{
    return 0
  }
}

/**
 * @param    {[type]} trs [需要查询的行]
 * @param    {[type]} index [当前行下标]
 * @param    {[type]} status [上/下移]
 * @return   {[type]} [在同级分类下，获取上/下一个同分类tr下标]
 */
function getNextEle(trs, index, status) {
  var cur_level = level(trs[index])
  for (var i = index+status; i>=0 && i<trs.length; i=i+status) {
    if(level(trs[i]) == cur_level){
      return i
      break
    }else if(level(trs[i]) < cur_level){
      return  0
      break
    }
  }
}

/**
 * @param    {[type]} trs [需要查询的行]
 * @param    {[type]} index [当前行下标]
 * @param    {[type]} status [上/下移]
 * @return   {[type]} [获取上/下一个同分类tr下标]
 */
function getTargetEle(trs, index, status) {
  if(!index) return 0

  var cur_level = level(trs[index])
  for (var i = index+status; i>=0 && i<trs.length; i=i+status) {
    if(level(trs[i]) == cur_level){
      return i
      break
    }else if(level(trs[i]) < cur_level){
      return  status > 0 ? i : i - status
      break
    }
    if(i === 0) {
      return 0
    }
    if(i == trs.length-1) {
      return trs.length
    }
  }
}
function warnMsg(msg = '移动失败') {
  alert(msg)
}
//-->
</script>


<?php echo $this->fetch('pagefooter.htm'); ?>
<?php endif; ?>
