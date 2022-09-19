<link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">

<div class="clearfix divmargin" ng-app="myApp" ng-controller="groupListCtrl">
	<div >
		<a ng-if="addgroup" href="/iauth/authItem/addGroup" class="addUser" style="color: #fff;margin-left: 15px;">添加权限组</a>
	</div>
	<table class="table table-bordered table-hover  lh-24 clearfix" style="margin-left: 15px;">
		<tr class="trHead">
			<th>权限组</th>
			<th>权限组代码</th>
			<!-- <th>操作</th> -->
		</tr>
		<tr ng-repeat="list in ListData.list">
			<td>{{list.name}}</td>
			<td>{{list.code}}</td>
			<!-- <td><a ng-if="groupDel" ng-click="del(list.id)">下线</a></td> -->
		</tr>
	</table>
	<uib-pagination total-items="bigTotalItems" ng-model="currentPage" max-size="maxSize" class="pagination" boundary-links="true" ng-change="pageChanged()"  force-ellipses="true" previous-text="上一页" next-text="下一页" first-text="首页" last-text="尾页"></uib-pagination>
    <!-- <uib-pagination direction-links="true"  boundary-links="true" total-items="totalItems" ng-model="currentPage" ng-change="pageChanged()" previous-text="上一页" next-text="下一页" first-text="首页" last-text="尾页"></uib-pagination> -->
</div>
<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
