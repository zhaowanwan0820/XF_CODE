//删除图片
function del_name(id) {
	var spanId = id.split('_');
	var imageId = $('#id_'+spanId[1]).val();
	var id = $('#company_id').val(); 
	if(id && imageId) {
		 $.ajax({
		        type: "POST",
		        url: "/m.php?m=LoanLnfo&a=delJsonImage",           
		        data:{id:id,imageId:imageId,r:Math.random()},  
		        dataType:"json",
		        success: function(msg){
		        	if(msg.code == '0000') {
		        		$('#tr_'+spanId[1]).remove();
		        	}else{
		        		alert(msg.message);
		        	}
		       }
		});
	}else{
		alert('没有上传图片,请先添加!');
	}
}


//图片上传  
function upload(id) {
    var spanId = id.split('_');
    var name = $('#name_'+spanId[1]).val();
    var imageId = $('#id_'+spanId[1]).val();
    if(name) {
        $.ajaxFileUpload({       
            url:'/m.php?m=LoanLnfo&a=upload&tmp_name='+id+'&name='+name+'&imageId='+imageId,
            secureuri :false,
            fileElementId :id,
            dataType : 'json',
            success : function (data, status){
                if(typeof(data.code) != 'undefined'){              
                    if(data.code != '0000'){
                        alert(data.message);
                    }else{
                        $('#span_'+spanId[1]).text(data.content.path);
                        $('#id_'+spanId[1]).val(data.content.imageId);
                        $('#name_'+spanId[1]).attr('name','image_name['+data.content.imageId+']');
                        //处理 图片表cid
                    /*    var image_ids = $('#image_ids').val();
                        image_ids = image_ids + ','+ data.content.imageId;
                        $('#image_ids').val(image_ids);*/
    	         		$('#d_'+spanId[1]).css('display','');
    	         		$('#span_'+spanId[1]).css('display','');
                    }
                }
            },
            error: function(data, status, e){
                alert(data);
            }
        })
    }else{
        alert('图片资料未填写');
        $('#fileToUpload_'+spanId[1]).remove();
        $('#id_'+spanId[1]).last().after('<input id="fileToUpload_'+spanId[1]+'" type="file" size="5"  name="fileToUpload_'+spanId[1]+'" onchange="upload(this.id)">');;
        return false;
    }
}



//验证手机号码
function check_moile() {
	return true;//去掉验证
	var mobile = $('#tel_mobile').val();
	if(mobile) {
		if(/^\d{11}$/.test(mobile)) {
			return true;
		}else{
			alert('手机号码不符合规则!');
			return false;
		}
	}else{
		alert('请填写手机号码');
		return false;
	}
}


//add 2014-1-6
//图片上传   
function uploadOne(id) {
  if(id) {
		var spanId = id.split('_');
		var imageId = $('#id_'+spanId[1]).val();
		$.ajaxFileUpload({       
          url:'/m.php?m=LoanLnfo&a=upload&tmp_name='+id+'&imageId='+imageId+'&flag=1',
          secureuri :false,
          fileElementId :id,
          dataType : 'json',
          success : function (data, status){
              if(typeof(data.code) != 'undefined'){   
                  if(data.code != '0000'){
                      alert(data.message);
                  }else{
                      $('#span_'+spanId[1]).text(data.content.path);
                      $('#id_'+spanId[1]).val(data.content.imageId);
                      //处理 图片表cid
  	         		$('#del_'+spanId[1]).css('display','');
  	         		$('#span_'+spanId[1]).css('display','');
                  }
              }
          },
          error: function(data, status, e){
              alert(data);
          }
      })
  }else{
      alert('图片资料未填写');
      $('#fileToUpload_'+spanId[1]).remove();
      $('#id_'+spanId[1]).last().after('<input id="fileToUpload_'+spanId[1]+'" type="file" size="5"  name="fileToUpload_'+spanId[1]+'" onchange="upload(this.id)">');;
      return false;
  }
}

//删除单独的图片
function del_Image(id){
	var spanId 	  = id.split('_');
	var imageFile = $('#id_'+spanId[1]).val();
	var company_id= $('#company_id').val();				//获取数据id
	var iamgeName = $('#id_'+spanId[1]).attr('name');	//获取当前字段name
	if(id) {
		 $.ajax({
		        type: "POST",
		        url: "/m.php?m=LoanLnfo&a=delImage",           
		        data:{imageFile:imageFile,company_id:company_id,iamgeName:iamgeName,r:Math.random()},  
		        dataType:"json",
		        success: function(msg){
		        	if(msg.code == '0000') {
		        		$('#span_'+spanId[1]).text('');
		        		$('#del_'+spanId[1]).css('display','none');
		        		$('#id_'+spanId[1]).val('');
		        	}else{
		        		alert(msg.message);
		        	}
		       }
		});
	}else{
		alert('没有上传图片,请先添加!');
	}
}