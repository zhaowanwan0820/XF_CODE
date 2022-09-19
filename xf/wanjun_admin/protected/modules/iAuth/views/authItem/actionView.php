<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">
<link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">

<div class="clearfix divmargin" ng-app="myApp" ng-controller="actionView">
<ul class="List">
	<li><span>归属系统&nbsp;：</span><b>{{ListData.system_info}}</b></li>
	<li><span>权限组&nbsp;&nbsp;：</span><b>{{ListData. parent_info}}</b></li>
	<li><span>权限名称&nbsp;：</span><b>{{ListData.name}}</b></li>
	<li><span>权限代码&nbsp;：</span><b>{{ListData.code}}</b></li>
	<li><span>权限开发人：</span><b>{{ListData.author}}</b></li>
	<li><span>是否双因子：</span><b>{{ListData.dual_factor_info}}</b></li>
	<li><span>权限描述&nbsp;：</span><b>{{ListData.desc}}</b></li>
</ul>
</div>
<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/utils.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
