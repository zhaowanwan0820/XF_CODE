<link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">
<style type="text/css">
    div.modal.ng-isolate-scope.in{
        background-color: rgba(0,0,0,0);
    }
    div.modal.ng-isolate-scope.in.modal{
        width:100%;
        position: fixed;
        margin:50px auto;
        overflow: hidden;
    }
</style>
<div class="clearfix divmargin" ng-app="myApp" ng-controller="PhoneListCtrl" >
	<!-- <div>
		<form ng-submit="">
			<div class="selfrow">
	            <label for="iauth_userName">用户名 ：</label>
	            <input maxlength="50"  ng-model="username" id="iauth_userName" type="text"  >
	            <label for="iauth_realname">姓名：</label>
	            <input maxlength="50"  ng-model="realname" id="iauth_realname" type="text"  >
	            <label for="iauth_phone">手机：</label>
	            <input maxlength="50"  ng-model="phone" id="iauth_phone" type="text"  >
	        </div>
	        <div class="selfrow rowSpacing labInput">
	            <label for="iauth_number">ID：</label>
	            <input maxlength="50"  ng-model="number" id="iauth_number" type="text"  >
                <label for="iauth_department">部门：</label>
                <select style="width:212px; margin-left: 5px"  ng-model="user.sector" required="required" id="iauth_department" >
                    <option value="N">请选择</option>
                    <option ng-repeat="(dep,value) in departments"  value={{dep}}>{{value}}</option>
                </select>
	            <label for="iauth_userName">邮箱：</label>
	            <input maxlength="50" name="user[username]" ng-model="username" id="iauth_userName" type="text"  >
	        </div>
	        <div class="selfrow rowSpacing">
	            <label for="iauth_userName" style="margin-left: 10px">添加时间：</label>
	            <input maxlength="60" name="user[username]" ng-model="username" id="iauth_userName" type="text"  >
	        	<label for="iauth_department" style="margin-left: 70px">用户状态：</label>
                <select style="width:212px; margin-left: 5px"  ng-model="user.sector" required="required" id="iauth_department" >
                    <option value="N">请选择</option>
                    <option ng-repeat="(dep,value) in departments"  value={{dep}}>{{value}}</option>
                </select>
	        	<span style="margin-left:55px "><input  id="subBtn" type="submit" name="yt0" value="搜索" ></span>
	        </div>	
		</form>
	</div> -->
	<div class="rowSpacing">
		<a  ng-if="isTrue" href="/iauth/user/create" class="addUser" style="color: #fff;margin-left: 15px;">添加用户</a>
	</div>
	<table class="table table-bordered table-hover  lh-24 clearfix" style="margin-left: 15px;">
		<tr class="trHead">
			<th>ID</th>
			<th>部门</th>
			<th>用户名</th>
			<th>姓名</th>
			<th>手机</th>
			<th>邮箱</th>
			<th>添加时间</th>
			<th>最后登录时间</th>
			<th>用户状态</th>
			<th style="width:280px">操作</th>
		</tr>
		<tr ng-repeat="list in ListData.list">
			<td>{{list.id}}</td>
			<td>{{list.sector_info}}</td>
			<td>{{list.username}}</td>
			<td>{{list.realname}}</td>
			<td>{{list.phone}}</td>
			<td>{{list.email}}</td>
			<td>{{list.addtime}}</td>
			<td>{{list.last_login_time}}</td>
			<td>{{list.status_info}}</td>
			<td><a ng-if='Edit' href="/iauth/user/edit?id={{list.id}}" >编辑</a><span ng-if='Edit'>|</span><a ng-if='authlist' href="/iauth/user/authList?id={{list.id}}">查看权限</a><span ng-if='authlist'>|</span><a ng-if="assign" href="/iauth/authAssignment/assign?id={{list.id}}">分配权限</a><span ng-if='assign'>|</span><a ng-if='btnReset' ng-click="openModel(list.id)">重置密码</a><span ng-if='btnReset'>|</span><a  ng-click="Logout(list.id)" ng-if="zhuxiao" ng-hide="list.status==2">注销</a><a  ng-click="open(list.id)" ng-if="enable" ng-hide="list.status==1">启用</a></td>
		</tr>
	</table>
    <!-- <uib-pagination direction-links="true"  boundary-links="true" total-items="totalItems" ng-model="currentPage" ng-change="pageChanged()" previous-text="上一页" next-text="下一页" first-text="首页" last-text="尾页"></uib-pagination> -->
	<uib-pagination total-items="bigTotalItems" ng-model="currentPage" max-size="maxSize" class="pagination" boundary-links="true" ng-change="pageChanged()"  force-ellipses="true" previous-text="上一页" next-text="下一页" first-text="首页" last-text="尾页"></uib-pagination>
	<div>
		<span class="Paging">每页</span>
	    <select ng-model="page_size" style="width:80px;">
	    	<option value="10" >10</option>
	    	<option value="100" >100</option>
	    	<option value="200">200</option>
	    	<option value="300">300</option>
	    	<option value="400">400</option>
	    	<option value="500">500</option>
	    </select>
		<span class="Paging"> 共{{ListData.count}}条记录</span>
	</div>
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
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>