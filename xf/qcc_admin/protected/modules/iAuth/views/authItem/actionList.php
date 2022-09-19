<link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">

<div class="clearfix divmargin" ng-app="myApp" ng-controller="actionlist">

      <div class="rowSpacing">
        <a  ng-if="isTrue" href="/iauth/authItem/addAction" class="addUser" style="color: #fff;margin-left: 15px;">添加权限</a>
      </div>
	<table class="table table-bordered table-hover  lh-24 clearfix" style="margin-left: 15px;">
		<tr class="trHead">
		<th>权限组</th>
            <th>权限名称</th>
            <th>权限代码</th>
            <th>权限描述</th>
            <th>双因子认证</th>
            <th>权限开发人</th>
            <th>添加时间</th>
            <th>权限状态</th>
		<th style="width:280px">操作</th>
		</tr>
		<tr ng-repeat="list in ListData.list">
            <td>{{list.parent_name}}</td>
			<td>{{list.name}}</td>
            <td>{{list.code}}</td>
            <td>{{list.desc}}</td>
			<td>{{list.dual_factor_info}}</td>
            <td>{{list.author}}</td>
            <td>{{list.created_time}}</td>
            <td>{{list.status_info}}</td>
			<td ><a ng-if="actionView" href="/iauth/authItem/actionView?id={{list.id}}">查看</a><span ng-if="actionView">|</span><a ng-if="editaction" href="/iauth/authItem/editAction?id={{list.id}}&page={{currentPage}}">编辑</a><span ng-if="editaction">|</span><a ng-if="disable"  ng-click="disables(list.id)" ng-show="list.status==1">停用</a><a ng-if="enable"  ng-click="enables(list.id)" ng-show="list.status==2">启用</a></td>
		</tr>
	</table>
    <!-- <uib-pagination direction-links="true"  boundary-links="true" total-items="totalItems" ng-model="currentPage" ng-change="pageChanged()" previous-text="上一页" next-text="下一页" first-text="首页" last-text="尾页"></uib-pagination> -->
    <uib-pagination total-items="bigTotalItems" ng-model="currentPage" max-size="maxSize" class="pagination" boundary-links="true" ng-change="pageChanged()"  force-ellipses="true" previous-text="上一页" next-text="下一页" first-text="首页" last-text="尾页"></uib-pagination>
</div>
<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
