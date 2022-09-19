//验证图片
	function checkData() {
		var img = $('#hidden_previous_id').val();
		if(img ) {
			return true;
		}else{
			$.showErr('请先上传图片',function(){
				$("#hidden_previous_id").focus();
			});
			return false;
		}
	}
	

	//图片上传
	function upload() {
		var hidden_previous_id = $('#hidden_previous_id').val();
		//判断是否上传过了。如果有需要删除先前的图片
		if(hidden_previous_id) {
			del_previous_image(hidden_previous_id);
			$('#hidden_previous_id').val('');
		}
	    $.ajaxFileUpload({       
	        url:'/m.php?m=BankManage&a=bankinfoImage',
	        secureuri :false,
	        fileElementId :'fileToUpload',
	        dataType : 'json',
	        success : function (data, status){
	            if(typeof(data.code) != 'undefined'){              
	                if(data.code != '0000'){
	                    alert(data.message);
	                    $("#hidden_previous_id").focus();
	                    $('#imageName').val('');
	                }else{
	                	$('#hidden_previous_id').val(data.message.image_id);
	                	if(data.message.filename) {
	                		$('#img_name').css('display','');
		                	$('#img_href').attr('href',data.message.filename);
		                	$('#img_href').text(data.message.filename);
	                	}
	                }
	            }
	        },
	        error: function(data, status, e){
	        	alert(data);
	        }
		})
	}
	
	//删除上一个上传的图片
	function del_previous_image(id) {
		if(id) {
			$.ajax({
				   type: "POST",
				   url: '/m.php?m=BankManage&a=bankinfoImageDel',
				   data: "id="+id,
				   dataType:"json",
				   success: function(data){
					   if(data.code != '0000') {
						   return false;
					   }else{
						   return true;
					   }
				   }
			});
		}
	}
	
	//批量操作删除
	function del_data() {
		var select_value = jqchk();
		if(select_value.length >0) {
			if(confirm('确认删除吗')) {
				$.ajax({
					   type: "POST",
					   url: '/m.php?m=BankManage&a=deleteDataList',
					   data: "ids="+select_value,
					   dataType:"json",
					   success: function(data){
						   if(data.code != '0000') {
							   alert(data.message);
							   return false;
						   }else{
							   window.location.href='/m.php?m=BankManage&a=index';
						   }
					   }
				});
			}	
		}else{
			alert('你还没有选择任何内容！');
		}
	}
	
	//jquery获取复选框值    
	function jqchk(){  
	  var chk_value =[];    
	  $('input[name="key"]:checked').each(function(){    
		  chk_value.push($(this).val());    
	  });  
	  return chk_value;
	  //alert(chk_value.length==0 ?'你还没有选择任何内容！':chk_value);    
	}    
	
	//单条删除
	function delData(id) {
		if(id) {
			if(confirm('确认删除吗')) {
				$.ajax({
					   type: "POST",
					   url: '/m.php?m=BankManage&a=deleteData',
					   data: "id="+id,
					   dataType:"json",
					   success: function(data){
						   if(data.code != '0000') {
							   alert(data.message);
							   return false;
						   }else{
							   $('#id_'+id).remove();
						   }
					   }
				});
			}
		}else{
			alert('参数丢失');
		}
	}
	
	//恢复数据
	function recover_data(id) {
		if(id) {
			$.ajax({
				   type: "POST",
				   url: '/m.php?m=BankManage&a=recoverData',
				   data: "id="+id,
				   dataType:"json",
				   success: function(data){
					   if(data.code != '0000') {
						   alert(data.message);
						   return false;
					   }else{
						   $('#td_'+id).text('有效');
					   }
				   }
			});
		}else{
			alert('参数丢失');
		}
	}