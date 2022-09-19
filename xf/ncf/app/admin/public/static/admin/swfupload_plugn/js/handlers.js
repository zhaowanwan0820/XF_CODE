function fileQueueError(file, errorCode, message){
    try {
        errorMsg = "";
        switch (errorCode) {
            case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT :
                errorMsg = "o(╯□╰)o乖乖，上传的图片太大了!";
                break;
            case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED :
                errorMsg = "囧，不要上传这么多图片!";
                break;
            case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE :
                errorMsg = "⊙﹏⊙，无效文件类型!";
                break;
            case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE :
                errorMsg = "::>_<::，这是一个空文件!";
                break;
            default :
                errorMsg = "⊙?⊙未知错误!";
        }
        alert(errorMsg);
    } catch (ex) {
        this.debug(ex);
    }
}

function fileDialogStart() {
    // 用于处理图片数量上限问题
    window.uploadNumError = 0;
}

function fileQueued(file) {
    // 处理图片数量上限问题
    var haveImgsNum = $(".imgs_upload").length;
    var queueStatus = this.getStats();
    if ((haveImgsNum + queueStatus.files_queued) > $("#imgs_num_limit").val()) {
        this.cancelUpload();
        // 同一次队列，只有第一张触发数量上限的图片，才触发error时间
        if (0 == window.uploadNumError++) {
            this.fileQueueError(file, SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED);
        }
        return false;
    }

    return true;
}

function fileDialogComplete(numFilesSelected, numFilesQueued){
    try {
        this.startUpload();
    } 
    catch (ex) {
        this.debug(ex);
    }
}

function uploadProgress(file, bytesLoaded){

    try {
        var percent = Math.ceil((bytesLoaded / file.size) * 100);
        
        var progress = new FileProgress(file, this.customSettings.upload_target);
        progress.setProgress(percent);
        if (percent === 100) {
            progress.setStatus("创建缩略图中");
            progress.toggleCancel(false, this);
        }
        else {
            progress.setStatus("上传中");
            progress.toggleCancel(true, this);
        }
    } 
    catch (ex) {
        this.debug(ex);
    }
}


function uploadSuccess(file, serverData){
	addImage(serverData);
	var $svalue=$('form>input[name=s]').val();
	if($svalue==''){
		$('form>input[name=s]').val(serverData);
	}else{
		$('form>input[name=s]').val($svalue+"|"+serverData);
	}
	
}

function uploadComplete(file){
    try {

        if (this.getStats().files_queued > 0) {
            this.startUpload();
        }
        else {
            var progress = new FileProgress(file, this.customSettings.upload_target);
            progress.setComplete();
            progress.setStatus("所有图片上传成功！");
            progress.toggleCancel(false);
        }
    } 
    catch (ex) {
        this.debug(ex);
    }
}

function uploadError(file, errorCode, message){
    var imageName = "./static/admin/swfupload_plugn/images/error.gif";
    var progress;
    try {
        switch (errorCode) {
            case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
                try {
                    progress = new FileProgress(file, this.customSettings.upload_target);
                    progress.setCancelled();
                    progress.setStatus("取消");
                    progress.toggleCancel(false);
                } 
                catch (ex1) {
                    this.debug(ex1);
                }
                break;
            case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
                try {
                    progress = new FileProgress(file, this.customSettings.upload_target);
                    progress.setCancelled();
                    progress.setStatus("停止");
                    progress.toggleCancel(true);
                } 
                catch (ex2) {
                    this.debug(ex2);
                }
            case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
                imageName = "./static/admin/swfupload_plugn/images/uploadlimit.gif";
                break;
            default:
                alert(message);
                break;
        }
        
        // addImage(imageName);
        
    } 
    catch (ex3) {
        this.debug(ex3);
    }
    
}

function addImage(src){
    var newElement = "<li><img class='content imgs_upload'  src='" + src + "' style=\"width:100px;height:100px;\">" + 
                     "<img class='buttonImg' src="+"./static/admin/swfupload_plugn/images/fancy_close.png>" +
                     "<textarea class='img_url' name='img_url[]' style='display:none'>" + src + "</textarea></li>";
    $("#pic_list").append(newElement);
    $("img.buttonImg").last().bind("click", del);
}

var del = function(){
//    var fid = $(this).parent().prevAll().length + 1;
	var src=$(this).siblings('img').attr('src');
	var $svalue=$('form>input[name=s]').val();

    $.ajax({
        type: "GET", //访问WebService使用Post方式请求
        url: "m.php?m=DealAgency&a=del", //调用WebService的地址和方法名称组合---WsURL/方法名
        data: "src=" + src,
        success: function(data){
		var $val=$svalue.replace(data,'');
			$('form>input[name=s]').val($val);
        }
    });
    $(this).parent().remove();
}

function fadeIn(element, opacity){
    var reduceOpacityBy = 5;
    var rate = 30; // 15 fps
    if (opacity < 100) {
        opacity += reduceOpacityBy;
        if (opacity > 100) {
            opacity = 100;
        }
        
        if (element.filters) {
            try {
                element.filters.item("DXImageTransform.Microsoft.Alpha").opacity = opacity;
            } 
            catch (e) {
                element.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + opacity + ')';
            }
        }
        else {
            element.style.opacity = opacity / 100;
        }
    }
    
    if (opacity < 100) {
        setTimeout(function(){
            fadeIn(element, opacity);
        }, rate);
    }
}

function FileProgress(file, targetID){
    this.fileProgressID = "divFileProgress";
    
    this.fileProgressWrapper = document.getElementById(this.fileProgressID);
    if (!this.fileProgressWrapper) {
        this.fileProgressWrapper = document.createElement("div");
        this.fileProgressWrapper.className = "progressWrapper";
        this.fileProgressWrapper.id = this.fileProgressID;
        
        this.fileProgressElement = document.createElement("div");
        this.fileProgressElement.className = "progressContainer";
        
        var progressCancel = document.createElement("a");
        progressCancel.className = "progressCancel";
        progressCancel.href = "#";
        progressCancel.style.visibility = "hidden";
        progressCancel.appendChild(document.createTextNode(" "));
        
        var progressText = document.createElement("div");
        progressText.className = "progressName";
        progressText.appendChild(document.createTextNode(file.name));
        
        var progressBar = document.createElement("div");
        progressBar.className = "progressBarInProgress";
        
        var progressStatus = document.createElement("div");
        progressStatus.className = "progressBarStatus";
        progressStatus.innerHTML = "&nbsp;";
        
        this.fileProgressElement.appendChild(progressCancel);
        this.fileProgressElement.appendChild(progressText);
        this.fileProgressElement.appendChild(progressStatus);
        this.fileProgressElement.appendChild(progressBar);
        
        this.fileProgressWrapper.appendChild(this.fileProgressElement);
        
        document.getElementById(targetID).appendChild(this.fileProgressWrapper);
        fadeIn(this.fileProgressWrapper, 0);
        
    }
    else {
        this.fileProgressElement = this.fileProgressWrapper.firstChild;
        this.fileProgressElement.childNodes[1].firstChild.nodeValue = file.name;
    }
    
    this.height = this.fileProgressWrapper.offsetHeight;
    
}

FileProgress.prototype.setProgress = function(percentage){
    this.fileProgressElement.className = "progressContainer green";
    this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
    this.fileProgressElement.childNodes[3].style.width = percentage + "%";
};
FileProgress.prototype.setComplete = function(){
    this.fileProgressElement.className = "progressContainer blue";
    this.fileProgressElement.childNodes[3].className = "progressBarComplete";
    this.fileProgressElement.childNodes[3].style.width = "";
    
};
FileProgress.prototype.setError = function(){
    this.fileProgressElement.className = "progressContainer red";
    this.fileProgressElement.childNodes[3].className = "progressBarError";
    this.fileProgressElement.childNodes[3].style.width = "";
    
};
FileProgress.prototype.setCancelled = function(){
    this.fileProgressElement.className = "progressContainer";
    this.fileProgressElement.childNodes[3].className = "progressBarError";
    this.fileProgressElement.childNodes[3].style.width = "";
    
};
FileProgress.prototype.setStatus = function(status){
    this.fileProgressElement.childNodes[2].innerHTML = status;
};


FileProgress.prototype.toggleCancel = function(show, swfuploadInstance){
    this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
    if (swfuploadInstance) {
        var fileID = this.fileProgressID;
        this.fileProgressElement.childNodes[0].onclick = function(){
            swfuploadInstance.cancelUpload(fileID);
            return false;
        };
    }
};
