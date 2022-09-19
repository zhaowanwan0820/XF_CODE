<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">


<div class="span19" ng-app="myApp" ng-controller="creatCtrl">
    <div id="content" style="position:relative;">
        <div class="form">
            <form id="itz-channel-user-form">
                <div class="row">
                    <label for="iauth_userName">姓名：</label>
                    <span class="text-middle">*</span>
                    <input size="50" maxlength="50" name="user[realname]" ng-model="user.realname" id="iauth_userName" type="text" required="required" value=""><span class="errorMess" id="realnameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_mail">邮箱：</label>
                    <span class="text-middle">*</span>
                    <input size="50" name="user[username]" ng-model="user.username" id="iauth_mail" required="required" type="text"><span class="fontSize" style="margin-left: 5px;" >@itouzi.com</span><span class="errorMess" id="usernameerror"></span>
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
                    <!-- <input  id="subBtn" type="submit" name="yt0" value="提交" > -->
                    <input  id="subBtn" type="button" ng-click="openModel(user)"  value="提交" >
                </div>
            </form>
        </div>
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
