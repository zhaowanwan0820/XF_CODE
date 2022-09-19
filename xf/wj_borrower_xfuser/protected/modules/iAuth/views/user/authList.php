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
				<td rowspan="{{100}}" id="fixedLineHeight"><input type="checkbox" id="{{sec.id}}" name="{{sec.name}}" ng-checked="isSelected(sec.id)" ng-click="updateSelection($event,sec.id)" ng-disabled="true"/><span class="radioSty">{{sec.name}} [{{sec.code}}]</span></td>
			</tr>
	 		<tr ng-repeat="thr in sec.actionList" class="vertic">
				<!-- <td><label><input type="checkbox" data-ng-model="user.name[$index]" ng-true-value="{{thr.id}}" ng-false-value=""/> {{thr.name}}</label></td> -->
                <td><label><input type="checkbox" id="{{thr.id}}" name="{{thr.name}}" ng-checked="isSelected(thr.id)" ng-click="updateSelection($event,thr.id)" ng-disabled="true"/>{{thr.name}} [{{thr.code}}]</label></td>
			</tr>
		</table>
	</div>
</div>
</form>
</div>
<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/utils.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
