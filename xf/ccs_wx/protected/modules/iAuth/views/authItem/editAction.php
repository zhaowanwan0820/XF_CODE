<link rel="stylesheet" type="text/css" href="/css/iAuthcss.css">


<div class="span19" ng-app="myApp" ng-controller="editactionCtrl">

    <div id="content" style="position:relative;">
        <div class="form">
            <form id="itz-channel-user-form" ng-submit="formsubmit(item)">
                <input name="item[id]" ng-model="item.id"  type="hidden" >
                <div class="row rowSpacing">
                    <label for="iauth_system">归属系统：</label>
                    <select style="width:220px;" name="item[system]" ng-model="item.system" required="required" id="iauth_system" >
                        <option ng-repeat="(dep,value) in departments"  value={{dep}}>{{value}}</option>
                    </select><span class="errorMess" id="sectorerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_CompetenceGroup">权限组：</label>
                    <select style="width:220px;" name="item[parent]" ng-model="item.parent" required="required" id="iauth_CompetenceGroup" >
                        <option ng-repeat="(dep,value) in CompetenceGroup"  value={{dep}}>{{value}}</option>
                    </select><span class="errorMess" id="sectorerror"></span>
                </div>                
                <div class="row rowSpacing">
                    <label for="iauth_name">权限名称：</label>
                    <input size="50" maxlength="50" name="item[name]" ng-model="item.name" id="iauth_name" type="text" required="required" value=""><span class="errorMess" id="realnameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_code">权限代码：</label>
                    <input size="50" name="item[code]" ng-model="item.code" id="iauth_code" required="required" type="text"><span class="errorMess" id="usernameerror"></span>
                </div>
                <div class="row rowSpacing">
                    <label for="iauth_author">权限开发人：</label>
                    <input size="50" maxlength="50" name="item[author]" ng-model="item.author" id="iauth_author" type="text" value=""><span class="errorMess" id="phoneerror"></span>
                </div>
<!--                 <div class="row rowSpacing">
                    <label>是否双因子：</label>
                    <input name="item[dual_facotr]" ng-model="item.dual_facotr" id="iauth_dual_facotr1" type="radio"  value="1"><lable class="radioSty" for="iauth_dual_facotr1">是</lable>
                    <input name="item[dual_facotr]" ng-model="item.dual_facotr" id="iauth_dual_facotr2" type="radio" value="0"><lable class="radioSty" for="iauth_dual_facotr2">否</lable>
                    <span class="errorMess" id="phoneerror"></span>
                </div> -->
                <div class="row rowSpacing">
                    <label for="iauth_author">权限描述：</label>
                    <!-- <input size="50" maxlength="50" name="item[author]" ng-model="item.author" id="iauth_author" type="text" required="required" value=""><span class="errorMess" id="phoneerror"></span> -->
                    <textarea name="item[desc]" ng-model="item.desc"></textarea>
                </div>
                <div class="row botton rowSpacing">
                    <input  id="subBtn" type="submit" name="yt0" value="提交" >
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="/third/User/angularjs.js"></script>
<script type="text/javascript" src="/third/User/utils.js"></script>
<script type="text/javascript" src="/third/User/ui-bootstrap-tpls-0.14.3.min.js"></script>
<script type="text/javascript" src="/third/User/AllContorllor.js"></script>
