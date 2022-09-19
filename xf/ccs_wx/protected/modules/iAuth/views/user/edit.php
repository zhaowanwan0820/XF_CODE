<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">


<div class="span19" ng-app="myApp" ng-controller="edit">
    <div id="content" style="position:relative;">
        <div class="form">
            <form id="itz-channel-user-form"  ng-submit="formsubmit(user)">
                <input name="user[id]" ng-model="user.id"  type="hidden" >
                <div class="row">
                    <label for="iauth_userName">用户名：</label>
                    <span class="text-middle">*</span>
                    <input size="50" maxlength="50" name="user[username]" ng-model="username" id="iauth_userName" type="text" required="required" value="" readonly="readonly"><span class="errorMess" id="realnameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_userName">姓名：</label>
                    <span class="text-middle">*</span>
                    <input size="50" maxlength="50" name="user[realname]" ng-model="realname" id="iauth_userName" type="text" required="required" value="" readonly="readonly"><span class="errorMess" id="realnameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_mail">邮箱：</label>
                    <span class="text-middle">*</span>
                    <input size="50" name="user[email]" ng-model="email" id="iauth_mail" required="required" type="text" readonly="readonly"><span class="errorMess" id="usernameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_iphone">手机：</label>
                    <span class="text-middle">*</span>
                    <input size="50" maxlength="50" name="user[phone]" ng-model="user.phone" id="iauth_iphone" type="text" required="required" value=""><span class="errorMess" id="phoneerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_department">部门：</label>
                    <span class="text-middle">*</span>
                    <select style="width:220px;" name="user[sector]" ng-model="user.sector" required="required" id="iauth_department" >
                        <option ng-repeat="(dep,value) in departments"  value={{dep}}>{{value}}</option>
                    </select><span class="errorMess" id="sectorerror"></span>
                </div>
                <div class="row botton rowSpacing">
                    <input  id="subBtn" type="submit" name="yt0" value="提交">
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/utils.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
