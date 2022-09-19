<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">

<div  class="clearfix divmargin" ng-app="myApp" ng-controller="assign">
<div>用户名：<span class="pageLeft">{{userName}}</span></div>
<!-- 遍历整个div -->

<form ng-submit="formSubmit()">
<div class="clearfix fixedW"  ng-repeat="(index,first) in ListData" >
	<div class="rowSpacing">权限:<b class="pageLeft">{{first.system_info}}</b></div><span class="tabHide" ng-click="toggle(index)">{{showText(index)}}</span>
	<div>
		<table class="table table-bordered table-hover  lh-24 clearfix" id='tab{{index}}' style="margin-left: 40px;margin-top:20px;width:80%;" ng-repeat="sec in first.groupList"  ng-hide="hidden(index)" >
	 		<tr class="vertic">
				<td valign="middle" rowspan="100" id="fixedLineHeight"><input type="checkbox" id="{{sec.id}}" name="{{sec.name}}" ng-checked="isSelected(sec.id)" ng-click="updateSelection($event,sec.id)" /><span class="radioSty">{{sec.name}} [{{sec.code}}]</span></td>
			</tr>
	 		<tr ng-repeat="thr in sec.actionList" class="vertic">
				<!-- <td><label><input type="checkbox" data-ng-model="user.name[$index]" ng-true-value="{{thr.id}}" ng-false-value=""/> {{thr.name}}</label></td> -->
                <td><label><input type="checkbox" id="{{thr.id}}" name="{{thr.name}}" ng-checked="isSelected(thr.id)" ng-click="updateSelection($event,thr.id)" />{{thr.name}} [{{thr.code}}]</label></td>
			</tr>
		</table>
	</div>
</div>
<div class="row botton rowSpacing">
    <!-- <input  id="assBtn"  type="submit" name="yt0" value="提交" > -->
    <input  id="assBtn" type="button" ng-click="openModel()"  value="提交" >
	<!-- <botton  class="assBtn" ng-click="openModel()"  style="background:#e04545;color:#fff; margin-top: 2px;padding: 8px 20px; ">双因子认证</a> -->
</div>
</form>
<div>
<script type="text/ng-template" id="myModalContent.html">
    <form ng-submit="Authenticate(user)">
        <div class="modal-header">
            <h3 class="modal-title">请输入手机验证码</h3>
        </div>
        <div class="modal-body">
             <div class="towFactor">验证码已发送到你{{phoneNumber}}手机,请注意查收！</div>
             <input type="text" ng-model="user.code" style="height:30px;vertical-align: inherit;" placeholder="请输入验证码">
             <div>若长时间未收到短信，请关闭此弹窗重新进行操作~</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" type="submit" ng-click="ok()" data-dismiss="modal">确定</button>
            <button class="btn btn-warning" type="button" ng-click="cancel()" data-dismiss="modal">取消</button>
        </div>
    </form>
</script>
</div>
</div>
<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/utils.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
