<!-- $Id: agency_info.htm 14216 2008-03-10 02:27:21Z testyang $ -->
<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/region.js')); ?>
<link href="styles/uploader.css" rel="stylesheet" type="text/css" />
<div class="main-div">
<form method="post" action="suppliers.php" name="theForm" enctype="multipart/form-data" onsubmit="return validate()">
<table cellspacing="1" cellpadding="3" width="100%">
  <tr><td class="line-title">基本信息设置</td></tr>
  <!-- 店铺名称 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_shop_name']; ?></td>
    <td><input type="text" name="shop_name" maxlength="50" value="<?php echo $this->_var['suppliers']['shop_name']; ?>" id="shop_name" /></td>
  </tr>
  <!-- 供应商类型 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_type']; ?></td>
    <td>
      <select name="type" id="">
        <option value="0">请选择</option>
        <?php $_from = $this->_var['suppliers']['typeList']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?>
        <option value="<?php echo $this->_var['key']; ?>" <?php if ($this->_var['suppliers']['type'] == $this->_var['key']): ?> selected <?php endif; ?> ><?php echo $this->_var['item']; ?></option>
        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
      </select>
    </td>
  </tr>
  <!-- 企业名称 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_suppliers_name']; ?></td>
    <td><input type="text" name="suppliers_name" maxlength="50" value="<?php echo $this->_var['suppliers']['suppliers_name']; ?>" /></td>
  </tr>
  <!-- 主营业务 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_main_business']; ?></td>
    <td><input type="text" name="main_business" maxlength="50" value="<?php echo $this->_var['suppliers']['main_business']; ?>" /></td>
  </tr>
  <!-- 店铺图标 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_shop_icon']; ?></td>
    <td>
        <p class="upload_view" id="view" style="background:url('../<?php echo $this->_var['suppliers']['shop_icon']; ?>')"></p>
        <input type="button" class="open" value="上传图片">
        <div id="upload_D">
          <div class="upload_frame">
            <div class="upload_title">
              <span class="upload_title_left">请选择图片</span>
              <span class="upload_title_right"><img src="images/close.png"></span>
            </div>
            <div class="upload_fileBtn">
              图片上传
              <input type="file" id="file"/>
              <input id="file1" name="shop_icon" hidden multiple="multiple" style="cursor:pointer;" />
              <label for="file">选择图片</label>
            </div>
            <div class="upload_content">
              <div id="clipArea"></div>
              <div class="upload_content_right">
                <p class="upload_view"></p>
                <input type="button" id="clipBtn" value="保存修改">
                <input type="button" class="close" value="提交">
              </div>
            </div>
          </div>
        </div>
        <span class="notice-span"> <?php echo $this->_var['lang']['warn_icon']; ?></span>
    </td>
  </tr>
  <!-- 个性签名 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_personal_signature']; ?></td>
    <td><textarea  name="personal_signature" cols="60" rows="1" maxlength="20" placeholder="<?php echo $this->_var['lang']['warn_signature']; ?>" value="<?php echo $this->_var['suppliers']['personal_signature']; ?>"  ><?php echo $this->_var['suppliers']['personal_signature']; ?></textarea></td>
  </tr>
  <!-- 店铺简介 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_shop_desc']; ?></td>
    <td><textarea  name="shop_desc" cols="60" rows="4" maxlength="500" ><?php echo $this->_var['suppliers']['shop_desc']; ?></textarea><span class="notice-span"> <?php echo $this->_var['lang']['warn_desc']; ?></span></td>
  </tr>
  <!-- 客服电话 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_service_tel']; ?></td>
    <td><input type="text" name="service_tel" maxlength="14" value="<?php echo $this->_var['suppliers']['service_tel']; ?>" /><span class="notice-span"> <?php echo $this->_var['lang']['warn_service_tel']; ?></span></td>
  </tr>
  <!-- 服务时间 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_service_week']; ?></td>
    <td><input type="text" name="weekdays_s" maxlength="60" value="<?php echo $this->_var['suppliers']['service_time']['weekdays']['s']; ?>" class="checktime" placeholder="<?php echo $this->_var['lang']['label_time_exc1']; ?>" /> 到 <input type="text" name="weekdays_e" maxlength="60" value="<?php echo $this->_var['suppliers']['service_time']['weekdays']['e']; ?>" class="checktime" placeholder="<?php echo $this->_var['lang']['label_time_exc2']; ?>" /></td>
  </tr>
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_service_weekends']; ?></td>
    <td><input type="text" name="holiday_s" maxlength="60" value="<?php echo $this->_var['suppliers']['service_time']['holiday']['s']; ?>" class="checktime" placeholder="<?php echo $this->_var['lang']['label_time_exc1']; ?>" /> 到 <input type="text" name="holiday_e" maxlength="60" value="<?php echo $this->_var['suppliers']['service_time']['holiday']['e']; ?>" class="checktime" placeholder="<?php echo $this->_var['lang']['label_time_exc2']; ?>" /></td>
  </tr>
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_service_qq']; ?></td>
    <td><input type="text" name="service_qq" maxlength="14" value="<?php echo $this->_var['suppliers']['service_qq']; ?>" /><span class="notice-span"> <?php echo $this->_var['lang']['label_warn_qq_tel']; ?></span></td>
  </tr>

  <tr><td class="line-title">收货信息设置</td></tr>
  <!-- 收货地址 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_receiver_address']; ?></td>
      <!-- <input type="text" name="receiver_address" value="<?php echo $this->_var['suppliers']['receiver_address']; ?>"> -->
        <!-- <select name="country" id="selCountries" onChange="region.changed(this, 1, 'selProvinces')" size="10">
          <?php $_from = $this->_var['countries']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'country');$this->_foreach['fe_country'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['fe_country']['total'] > 0):
    foreach ($_from AS $this->_var['country']):
        $this->_foreach['fe_country']['iteration']++;
?>
            <option value="<?php echo $this->_var['country']['region_id']; ?>" <?php if (($this->_foreach['fe_country']['iteration'] <= 1)): ?>selected<?php endif; ?>><?php echo htmlspecialchars($this->_var['country']['region_name']); ?></option>
          <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
        </select>
        <select name="province" id="selProvinces" onChange="region.changed(this, 2, 'selCities')" size="10">
          <option value=""><?php echo $this->_var['lang']['select_please']; ?></option>
        </select>
        <select name="city" id="selCities" onChange="region.changed(this, 3, 'selDistricts')" size="10">
          <option value=""><?php echo $this->_var['lang']['select_please']; ?></option>
        </select> -->
        <td><textarea name="receiver_address" cols="60" rows="4" ><?php echo $this->_var['suppliers']['receiver_address']; ?></textarea></td>
  </tr>

  <!-- 收货人 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_receiver_name']; ?></td>
    <td><input type="text" name="receiver_name" maxlength="10" value="<?php echo $this->_var['suppliers']['receiver_name']; ?>" /></td>
  </tr>
  <!-- 收货电话 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_receiver_tel']; ?></td>
    <td><input type="text" name="receiver_tel" maxlength="60" value="<?php echo $this->_var['suppliers']['receiver_tel']; ?>" /></td>
  </tr>
  <!-- 备注 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['label_remark']; ?></td>
    <td><textarea name="remark" cols="60" rows="4" maxlength="500"><?php echo $this->_var['suppliers']['remark']; ?></textarea></td>
  </tr>

  <tr><td class="line-title">发货时间</td></tr>
  <?php if ($this->_var['inputForbidden']): ?>
      <tr><td class="label"></td><td style="font-weight: bold">用户下单后<?php echo $this->_var['suppliers']['delivery_time']; ?>小时内发货</td></tr>
  <?php else: ?>
    <tr><td class="label"></td><td style="font-weight: bold"><input type="radio" value="24" name="delivery_time" <?php if ($this->_var['suppliers']['delivery_time'] == 24): ?>checked<?php endif; ?>>用户下单后24小时内发货</td></tr>
    <tr><td class="label"></td><td style="font-weight: bold"><input type="radio" value="48" name="delivery_time" <?php if ($this->_var['suppliers']['delivery_time'] == 48): ?>checked<?php endif; ?>>用户下单后48小时内发货</td></tr>
    <tr><td class="label"></td><td style="font-weight: bold"><input type="radio" value="72" name="delivery_time" <?php if ($this->_var['suppliers']['delivery_time'] == 72): ?>checked<?php endif; ?>>用户下单后72小时内发货</td></tr>
    <tr><td class="label"></td><td><span class="notice-span"> <?php echo $this->_var['lang']['suppliers_delivery_time']; ?></span></td></tr>
  <?php endif; ?>

  <tr><td class="line-title">店铺联系人</td></tr>
  <!-- 负责人姓名 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_manager_name']; ?></td>
    <td><input type="text" name="manager_name" maxlength="60" value="<?php echo $this->_var['suppliers']['manager_name']; ?>" /></td>
  </tr>
  <!-- 负责人电话 -->
  <tr>
    <td class="label"><?php echo $this->_var['lang']['require_field']; ?><?php echo $this->_var['lang']['label_manager_tel']; ?></td>
    <td><input type="text" name="manager_tel" maxlength="60" value="<?php echo $this->_var['suppliers']['manager_tel']; ?>" onblur="checkTel(value)" /><span class="notice-span"> <?php echo $this->_var['lang']['warn_manager_tel']; ?> </span><span class="warning none"> <?php echo $this->_var['lang']['warn_manager_tel_error']; ?></span></td>
  </tr>
  <!-- 负责人姓名 -->
  <tr><td class="line-title">绑定买家端用户</td></tr>
  <!-- 负责人姓名 -->
  <tr>
    <td class="label">有解注册手机号码：</td>
    <td><input type="text" name="platform_user_phone" maxlength="60" value="<?php echo $this->_var['suppliers']['platform_user_phone']; ?>" <?php if ($this->_var['suppliers']['platform_user_phone'] > 0): ?> disabled <?php endif; ?> /><span class="notice-span"> 非爱投资注册手机号码</span></td>
  </tr>

  <?php if (! $this->_var['inputForbidden']): ?>
    <tr><td class="line-title">平台管理员</td></tr>
    <tr>
      <td class="label">
      <a href="javascript:showNotice('noticeAdmins');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.svg" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a><?php echo $this->_var['lang']['label_admins']; ?></td>
      <td><?php $_from = $this->_var['suppliers']['admin_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'admin');if (count($_from)):
    foreach ($_from AS $this->_var['admin']):
?>
        <input type="radio" name="admins" value="<?php echo $this->_var['admin']['user_id']; ?>" <?php if ($this->_var['admin']['types'] == "this"): ?>checked="checked"<?php endif; ?> />
        <?php echo $this->_var['admin']['user_name']; ?><?php if ($this->_var['admin']['types'] == "other"): ?>(*)<?php endif; ?>
      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?><br />
      <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:block" <?php else: ?> style="display:none" <?php endif; ?> id="noticeAdmins"><?php echo $this->_var['lang']['notice_admins']; ?></span></td>
    </tr>
    <!--新增-->
    <tr>
      <td class="label"><?php echo $this->_var['lang']['platform_list']; ?></td>
      <td><?php $_from = $this->_var['suppliers']['platform_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'admin');if (count($_from)):
    foreach ($_from AS $this->_var['admin']):
?>
        <input type="checkbox" name="platforms[]" value="<?php echo $this->_var['admin']['user_id']; ?>" <?php if ($this->_var['admin']['types'] == "this"): ?>checked="checked"<?php endif; ?> />
        <?php echo $this->_var['admin']['user_name']; ?><?php if ($this->_var['admin']['types'] == "other"): ?>(*)<?php endif; ?>&nbsp;&nbsp;
        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?><br />
      </td>
    </tr>
  <?php endif; ?>
</table>

<?php if (! $this->_var['inputForbidden']): ?>
<table align="center">
  <tr>
    <td colspan="2" align="center">
      <input type="submit" class="button" value="<?php echo $this->_var['lang']['button_submit']; ?>" />
      <input type="reset" class="button" value="<?php echo $this->_var['lang']['button_reset']; ?>" />
      <input type="hidden" name="act" value="<?php echo $this->_var['form_action']; ?>" />
      <input type="hidden" name="id" value="<?php echo $this->_var['suppliers']['suppliers_id']; ?>" />
    </td>
  </tr>
</table>
<?php endif; ?>
</form>
</div>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,validator.js')); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'uploader/jquery.js,uploader/iscroll-zoom.js?1,uploader/hammer.js,uploader/lrz.all.bundle.js,uploader/jquery.photoClip.min.js')); ?>

<!-- 头像上传 -->
<script>
$(function(){
  var clipArea = new bjj.PhotoClip("#clipArea", {
    size: [300, 300],// 截取框的宽和高组成的数组。默认值为[260,260]
    outputSize: [300, 300], // 输出图像的宽和高组成的数组。默认值为[0,0]，表示输出图像原始大小
    //outputType: "jpg", // 指定输出图片的类型，可选 "jpg" 和 "png" 两种种类型，默认为 "jpg"
    file: "#file", // 上传图片的<input type="file">控件的选择器或者DOM对象
    view: ".upload_view", // 显示截取后图像的容器的选择器或者DOM对象
    ok: "#clipBtn", // 确认截图按钮的选择器或者DOM对象
    loadStart: function() {
      // 开始加载的回调函数。this指向 fileReader 对象，并将正在加载的 file 对象作为参数传入
      $('.cover-wrap').fadeIn();
      // console.log("照片读取中");
    },
    loadComplete: function() {
        // 加载完成的回调函数。this指向图片对象，并将图片地址作为参数传入
      console.log("照片读取完成");
    },
    //loadError: function(event) {}, // 加载失败的回调函数。this指向 fileReader 对象，并将错误事件的 event 对象作为参数传入
    clipFinish: function(dataURL) {
        // 裁剪完成的回调函数。this指向图片对象，会将裁剪出的图像数据DataURL作为参数传入
        // console.log(dataURL);
        $("#file1").val(dataURL)
    }
  });

  $(".upload_title_right").click(function(){
    $("#upload_D").fadeOut();
  });
  $(".close").click(function(){
    if ($("#file")[0].files.length) {
      $("#upload_D").fadeOut();
    }
  });
  $(".open").click(function(){
    $("#upload_D").fadeIn();
  });
})
$(".checktime").on("blur",function(){
  if(!this.value) return false
  var reg = /^(20|21|22|23|[0-1]\d):[0-5]\d$/;
  var regExp = new RegExp(reg);
  if(!regExp.test(this.value)){
　　alert("时间格式不正确");
    this.value = ''
  }
})
</script>


<script language="JavaScript">
  document.forms['theForm'].elements['shop_name'].focus();
  onload = function(){
    // 开始检查订单
    startCheckOrder();

    // 查看模式input+textarea禁用
    var inputForbidden = '<?php echo $this->_var['inputForbidden']; ?>'
    if (inputForbidden) $('form').find('input,textarea,select').attr('disabled',true)
  }
    /**
 * 检查表单输入的数据
 */
  function validate(){
    var validator = new Validator("theForm");
    var shop_name = document.forms['theForm'].elements['shop_name'].value.length;
    var shop_desc = document.forms['theForm'].elements['shop_desc'].value.length;
    var type = document.forms['theForm'].elements['type'].value;
    var personal_signature = document.forms['theForm'].elements['personal_signature'].value.length;
    var suppliers_name = document.forms['theForm'].elements['suppliers_name'].value.length;
    var service_tel = document.forms['theForm'].elements['service_tel'].value;
    var manager_name = document.forms['theForm'].elements['manager_name'].value.length;
    var manager_tel = document.forms['theForm'].elements['manager_tel'].value;
    var main_business = document.forms['theForm'].elements['main_business'].value.length;
    var admins = $("input[type=radio][name='admins']:checked").val();
    var platforms = $("input[type=checkbox][name='platforms[]']:checked").length;
    var t1 = document.forms['theForm'].elements['weekdays_s'].value.length;
    var t2 = document.forms['theForm'].elements['weekdays_e'].value.length;
    var t3 = document.forms['theForm'].elements['holiday_s'].value.length;
    var t4 = document.forms['theForm'].elements['holiday_e'].value.length;
    var service_qq = document.forms['theForm'].elements['service_qq'].value;
    var delivery_time = document.forms['theForm'].elements['delivery_time'].value;
    var platform_user_phone = document.forms['theForm'].elements['platform_user_phone'].value;

    var arr = new Array(24,48,72);
    if(!shop_name){
      alert(no_shop_name)
      return false
    }
    if(type<1) {
      alert(no_type);
      return false;
    }
    if(!suppliers_name) {
      alert(no_suppliers_name);
      return false;
    }
    // if(!main_business) {
    //   alert(no_main_business);
    //   return false;
    // }
    if(!service_tel.length && !service_qq.length){
      alert(no_service_tel);
      return false;
    }
    if(service_tel.length){
      var reg = /^[0-9-]{1,14}$/;
      if(!reg.test(service_tel)){
        alert(service_tel_error);
        return false;
      }
    }
      if(service_qq.length) {
        var reg = /^\d{4,14}$/
        if(!reg.test(service_qq)){
          alert(service_qq_error)
          return false;
        }
      }

      if (!delivery_time) {
        alert(select_delivery_time);
        return false;
      }

      if (!arr.includes(parseInt(delivery_time))) {
        alert(select_delivery_time);
        return false;
      }

    // if(!manager_name) {
    //   alert(no_manager_name);
    //   return false;
    // }
    if(!manager_tel.length) {
      alert(no_manager_tel);
      return false;
    }
    if (manager_tel) {
      var telrule = /^1\d{10}$/
      if (!telrule.test(manager_tel)) {
        alert(manager_tel_error)
        return false;
      }
    }
    if (platform_user_phone) {
      var telrule = /^1\d{10}$/
      if (!telrule.test(platform_user_phone)) {
        alert('有解注册手机号码格式错误')
        return false;
      }
    }
  //   if(!t1 || !t2 || !t3 || !t4) {
  //     alert(warn_service_tel);
  //     return false;
  //   }
  //  if(!platforms || !admins) {
  //    alert(no_admins_platforms);
  //    return false;
  //  }
  //   if(!shop_name){
  //     alert(no_shop_name)
  //     return false
  //   }else if(shop_name<2){
  //     alert(shop_name_error)
  //     return false
  //   }
  //   if(!shop_desc){
  //     alert(no_shop_desc)
  //     return false
  //   }else if(shop_desc<2){
  //     alert(shop_desc_error)
  //     return false
  //   }
  //   if(!personal_signature){
  //     alert(no_personal_signature)
  //     return false
  //   }else if(personal_signature<2){
  //     alert(personal_signature_error)
  //     return false
  //   }
    return validator.passed();
  }
  function checkTel(value){
    if (value) {
      var telrule = /^1\d{10}$/
      if (!telrule.test(value)) {
        $("span.warning").fadeIn()
        return false;
      }else{
        $("span.warning").fadeOut()
      }
    }
  }
</script>

<?php echo $this->fetch('pagefooter.htm'); ?>
