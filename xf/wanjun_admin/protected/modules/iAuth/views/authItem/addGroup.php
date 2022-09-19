<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">


<div class="span19" ng-app="myApp" ng-controller="addgroup">
    <div id="content" style="position:relative;">
        <div class="form">
            <form id="itz-channel-user-form" name='form1'  ng-submit="formsubmit(item)">
                <div class="row">
                    <label for="iauth_auth">权限组：</label>
                    <span class="text-middle">*</span>                    
                    <input size="50" maxlength="50" name="item[name]" ng-model="item.name" id="iauth_auth" type="text" required="required" value=""><span class="errorMess" id="realnameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_description">权限组代码：</label>
                    <span class="text-middle">*</span>
                    <input size="50" maxlength="50" name="item[code]" ng-model="item.code" id="iauth_description" type="text" required="required" value=""><span class="errorMess" id="realnameerror"></span>
                </div>
                
                <div class="row rowSpacing">
                    <label for="iauth_system">权限组系统：</label>
                    <span class="text-middle">*</span>
                    <select style="width:220px;" name="item[system]" ng-model="item.system" required="required" id="iauth_system" >
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
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
