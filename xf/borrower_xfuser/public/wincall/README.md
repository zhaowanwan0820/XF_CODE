# wincall接口文档

## 如何使用

### 文件说明

* jquery.min.js：依赖文件，版本要求为1.9以上
* jquery.wincall.v2.js：wincall核心js文件
* socket.io.js：wincall的socket连接的文件
* index.html：wincall演示文件

**非上述文件可忽略**

### 对接开发步骤

#### 1. 创建对象并初始化

    var wincall = new WinCall({
        wintel_server_ip: '192.168.1.36',
        wintelapi_url: 'http://manage3.icsoc.net'
        phonetype : 'telephone',
        vcc_code: 'testvcc',
        busy_reasons: {100:'开会',101:'午餐',102:'培训'},
        debug: false,
        event_listener: eventListener
    });
    wincall.init();
    
#### 2. 绑定按钮的操作

    //登录
    $('#btn_login').click(function(){
        wincall.fn_login(agentNum,agentPass,agentPhone,1);
    });
    // 其它按钮操作类似
    ...
    
#### 3. 创建事件回调函数

该函数为初始化对象时设置的`event_listener`值，用于监听回调的事件，根据不同的事件的做相应的操作

    function eventListener(response) {
        // 打印调试的信息
        console.dir(response);
        // 显示坐席的状态信息
        if (response.agStatus) {
            $('#seat_state').html(response.agStatus);
        }
        // 显示技能组的状态信息
        if (response.queStatus) {
            $('#queue_state').html(response.queStatus);
        }
        // 显示回调的事件的信息
        $('#obj_content').html(response.msg);
        $('#message').append(response.msg+"\r\n");
        // 处理按钮是否可用的状态，disableActions为禁用的按钮，即当前状态下不能操作的按钮
        $.each(response.disableActions, function (index, value) {
            $('#btn_'+value).attr('disabled', 'disabled');
        });
        // 处理按钮是否可用的状态，enableActions为可用的按钮，即当前状态下可以操作的按钮
        $.each(response.enableActions, function (index, value) {
            $('#btn_'+value).removeAttr('disabled');
        });
        // 如果需要对不同的事件做相应的处理，则只需要处理response.type即可，type说明见下面说明
        switch (response.type) {
            case 'ring_inner_event':
            case 'ring_queue_event'://来电事件
                // 可以实现自定义弹屏弹屏等功能
                // ...
                break;
            default://其他事件默认不处理
                // ...
                break;
        }
    }
    
**注：一般情况下只需要监听来电事件即可，在来电时设置自定义弹屏操作即可，弹屏的参数可以使用fn_getParam获取，其他事件可以根据实际的情况去做相应的处理即可**

#### 4. 获取企业配置信息接口

**注：此demo中主要使用jsonp方式来调用，因为此demo不包含任何服务器端代码，都是使用javascript开发，在我们正常的对接开发中，我们可以在服务器端中调用非jsonp接口来获取数据，任何传递到前端javascript中来设置，例如置忙原因我们可以在页面加载时在服务端先获取所有的置忙的原因，任何传递到前端直接显示所有的置忙原因，而不必像此demo中利用jsonp的方式获取然后通过javascript来设置操作的按钮**

##### 1. 获取在线坐席接口

该接口主要用于`呼叫内线`、`咨询内线`等获取空闲坐席列表，可以选择要呼叫的内线坐席，可以在后台调用后传到前台javascript中
也可以在javascript中使用jsonp方式调用

* 接口地址

        GET http://wintelapi_url/v2/wintelapi/api/agent/free
* 接口参数

    * vcc_code：企业代码
    * jsonpcallback：设置jsonp调用的回调的方法名
    
* 返回结果

    * code：结果编码
    * message：结果说明
    * data：返回数据
    
* 结果编码说明

    * 200：ok
    * 401：企业代码为空
    * 402：企业代码不存在
    
* 结果示例

        {"code":200,"message":"ok","data":[{"ag_id":"2389","pho_num":"8004","ag_name":"\u90ce\u745e\u6052",
        "ag_num":"6158"},{"ag_id":"2161","pho_num":"8039","ag_name":"\u62db\u80582","ag_num":"8039"}]}
        
* 结果字段说明

    * ag_id：坐席id
    * ag_num：坐席工号
    * ag_name：坐席名称
    * pho_num：分机号码

##### 2. 获取当前通话中坐席接口

该接口主要用于`监听`等获取通话坐席列表，可以选择要监听的内线坐席，可以在后台调用后传到前台javascript中
也可以在javascript中使用jsonp方式调用

* 接口地址

        GET http://wintelapi_url/v2/wintelapi/api/agent/ontheline
* 接口参数

    * vcc_code：企业代码
    * jsonpcallback：设置jsonp调用的回调的方法名
    
* 返回结果

    * code：结果编码
    * message：结果说明
    * data：返回数据
    
* 结果编码说明

    * 200：ok
    * 401：企业代码为空
    * 402：企业代码不存在
    
* 结果示例

        {"code":200,"message":"ok","data":[{"ag_id":"10","pho_num":"1322,"ag_time":"3:59:59"}]}
        
* 结果字段说明

    * ag_id：坐席ID
    * pho_num：分机号码
    * ag_time：状态持续的时间（HH:MM:SS）
    
##### 3. 获取技能组列表

该接口主要用于`转技能组`操作中获取企业下所有的技能组，然后选择指定的技能组进行转接，只能在javascript中使用jsonp方式调用

* 接口地址

        GET http://wintelapi_url/v2/wintelapi/api/jsonp/queue/list
* 接口参数

    * vcc_code：企业代码
    * jsonpcallback：设置jsonp调用的回调的方法名
    
* 返回结果

    * code：结果编码
    * message：结果说明
    * data：返回数据
    
* 结果编码说明

    * 200：ok
    * 401：企业代码为空
    * 402：企业代码不存在
    
* 结果示例

        {"code":200,"message":"ok","data":[{"id":10,"que_name":"1322"},{"id":11,"que_name":"test"}]}
        
* 结果字段说明

    * id：技能组id
    * que_name：技能组名称
    
##### 4. 获取企业配置的置忙原因接口

该接口主要用于获取企业下配置的置忙原因，置忙原因主要用于扩展置忙的类型，例如可以设置“小休”、“吃饭”等，
可以在后台调用后传到前台javascript中，也可以在javascript中使用jsonp方式调用
调用置忙函数`fn_busy(index)`时的参数`index`为置忙原因的id，如果不传参数，则默认为正常的置忙

* 接口地址

        GET http://wintelapi_url/v2/wintelapi/api/busyreason/list
* 接口参数

    * vcc_code：企业代码
    * jsonpcallback：设置jsonp调用的回调的方法名
    
* 返回结果

    * code：结果编码
    * message：结果说明
    * data：返回数据
    
* 结果编码说明

    * 200：ok
    * 401：企业代码为空
    * 402：企业代码不存在
    
* 结果示例

        {"code":200,"message":"ok","data":[{"id":100,"vcc_id":1,ag_stat":1,"stat_reason":"小休"}]}
        
* 结果字段说明

    * id：置忙原因id
    * vcc_id：企业id
    * ag_stat：类型（1为置忙）
    * stat_reason：置忙的具体原因

### WinCall的options说明

| 选项                  | 说明                                          |  默认值 |
| -------------------- | ----------------------------------------------| ---------|
| **wintel_server_ip** | 通信服务器的ip地址或域名 | 无 |
| wintel_server_port   | 通信服务器的端口 | 6050 |
| **wintelapi_url**    | 通信系统的API接口地址，用于获取账户的配置信息 | 无 |
| **vcc_code**         | 企业代码，用于唯一标识账户的编码，由服务方创建完提供 | 无 |
| phonetype            | 电话类型，包括：话机-telephone、软电话-softphone，该demo只支持telephone |telephone|
| busy_reasons         | 置忙的具体原因，比如小休、午休、开会等，该选项为在后台配置，通过`wintelapi_url`接口获取 | {} |
| **event_listener**   | 事件回调函数，可以自定义事件的处理函数 | wincall.defaultEventListener，只在console中打印消息 |
| debug                | 是否开启调试模式，开启后会在console中显示消息 | false |

*粗体的选项为必填选项*

### 事件响应结果

该结果为业务系统需要监听的事件，主要包括以下字段：

1. code：事件响应的编码
2. msg：事件响应的编码对应的文字说明，用于提示当前的操作状态
3. type：事件的类型，其中以action结尾的为按钮操作后返回的事件，以event结尾的为各种事件返回，包括以下类型：

* 操作返回

| 参数        | 说明     |
| --------    | --------| 
| login_action      | 签入操作返回事件 |
| logout_action      | 签出操作返回事件 |
| busy_action      | 置忙操作返回事件 |
| unbusy_action      | 置闲操作返回事件 |
| enable_autocall_action      | 启用自动外呼操作返回事件 |
| disable_autocall_action      | 禁用自动外呼操作返回事件 |
| callinner_action      | 呼叫内线操作返回事件 |
| callouter_action      | 呼叫外线操作返回事件 |
| hangup_action      | 挂机操作返回事件 |
| consultinner_action      | 咨询内线操作返回事件 |
| consultouter_action      | 咨询外线操作返回事件 |
| consultback_action      | 咨询接回操作返回事件 |
| threeway_action      | 三方操作返回事件 |
| threewayback_action      | 三方接回操作返回事件 |
| transfer_action      | 转接操作返回事件 |
| chanspy_action      | 监听操作返回事件 |
| intercept_action      | 拦截操作返回事件 |
| breakin_action      | 强插操作返回事件 |
| hold_action      | 保持操作返回事件 |
| restore_action      | 恢复操作返回事件 |
| evaluate_action      | 转评价操作返回事件 |
| ivr_action      | 转ivr操作返回事件 |
| queue_action      | 转技能组操作返回事件 |

* 系统事件返回

| 参数        | 说明     |
| --------    | --------| 
| ring_inner_event      | 内线呼叫来电事件 |
| ring_outer_event      | 外线呼叫来电事件（为外线直接转坐席呼叫，不通过技能组分配） |
| ring_queue_event      | 技能组分配来电事件 |
| ring_outbound_event   | 外呼来电事件 |
| ring_chanspy_event    | 监听来电事件 |
| ring_consult_event    | 咨询来电事件 |
| ring_autocall_event   | 自动外呼来电事件 |
| call_connected_event  | 呼叫中事件 |
| call_done_event       | 通话完成事件 |
    
4. agStatus：坐席状态说明文字
5. queStatus：技能组状态说明文字
6. enableActions：可用的操作（用于控制按钮是否可用或显示）
7. disableActions：禁用的操作（用于控制按钮是否可用或显示）

### 获取通话参数

在来电通话中可以使用wincall.fn_getParam(param)函数获取对应的值，例如主叫、被叫、呼叫唯一标识ID等等

| 参数        | 说明     |
| --------    | --------| 
| Caller      | 主叫号码 |
| Called      | 被叫号码 |
| CallId      | 呼叫唯一标识ID |
| OAgId       | 被呼叫或被咨询时为主叫方坐席，例如8001直接呼叫或咨询8002，则8002来电时该值为8001 |
| QueId       | 技能组ID |
| ServNum     | 服务号码，即用户呼叫哪个号码进来，例如用户呼叫03162774666进入到系统，则该值为03162774666 |
| Custom      | ？？？ |
| Others      | 咨询时的第三方号码，例如13811112222呼入系统转到坐席8001，然后8001咨询8002，则此时该值为13811112222 |
| AcPro       | 自动外呼中的项目ID |
| AcTask      | 自动外呼项目中的数据ID |
| IvrResult   | 转IVR后收取的按键的值 |

### 操作类型

该操作类型对应的为enableActions和disableActions中的操作，可以跟具体的按钮相对应

| 操作       | 说明     |
| --------   | --------| 
| login      | 签入 |
| logout      | 签出 |
| dialinner      | 呼叫内线 |
| dialouter      | 呼叫外线 |
| hangup      | 挂断 |
| answer      | 接听 |
| consultinner      | 咨询内线 |
| consultouter      | 咨询外线 |
| consultback      | 咨询接回 |
| busy      | 置忙 |
| unbusy      | 置闲 |
| hold      | 保持 |
| restore      | 恢复 |
| transfer      | 转接 |
| threeway      | 三方 |
| threewayback      | 三方接回 |
| chanspy      | 监听 |
| intercept      | 拦截 |
| breakin      | 强插 |
| evaluate      | 转评价 |
| enable_autocall      | 启用自动外呼 |
| disable_autocall      | 禁用自动外呼 |
| transivr      | 转IVR |
| transque      | 转技能组 |
| blindtrans      | 盲转 |

## 接口函数说明

### 签入

* 原型

        wincall.fn_login(agentNum, agentPass, agentPhone, queState)
* 参数

        agentNum：坐席工号
        agentPass：坐席密码
        agentPhone：分机号
        queState：签入后的初始状态（1表示空闲 2表示忙碌）
* 说明

        签入到系统中，其他的操作只有在签入后才能操作

### 注销

* 原型
    
        wincall.fn_logout()
* 参数
    
        无
* 说明
        
        签出系统，并与服务器断开连接

### 置闲

* 原型
    
        wincall.fn_unbusy()
* 参数
    
        无
* 说明
    
        修改坐席状态为空闲状态，坐席只有在空闲状态下才能接听队列分配的电话

### 置忙

* 原型
    
        wincall.fn_busy(busy_reason)
* 参数
    
        busy_reason：具体置忙的原因，例如`index.v2.html`中定义的`busy_reasons`定义的原因中的键
* 说明
    
        修改坐席状态为忙碌状态，置忙后技能组不会再分配电话到该坐席，但是直接呼叫仍然可以呼通

### 接听

* 原型
    
        wincall.fn_answer()
* 参数
    
        无
* 说明
        
        接听电话

### 挂断

* 原型
    
        wincall.fn_hangup()
* 参数
    
        无
* 说明
        
        挂断电话
        
### 保持

* 原型
    
        wincall.fn_hold()
* 参数
    
        无
* 说明
        
        保持通话，保持后将给另外一方播放音乐
        
### 恢复

* 原型
    
        wincall.fn_restore()
* 参数
    
        无
* 说明
        
        恢复被保持的通话，双方将进入正常的通话状态

### 呼叫内线坐席

* 原型
    
        wincall.fn_dialinner(agentID, caller)
* 参数
    
        agentID：呼叫的坐席的ID
        caller：显示的主叫号码
* 说明
        
        呼叫内线坐席

### 呼叫外线号码

* 原型
    
        wincall.fn_dialouter(called, caller, queId)
* 参数
    
        called：呼叫的外线号码
        caller：显示的主叫号码
        queId：呼叫外线时使用的技能组，如果坐席属于多个技能组，则选择其中一个即可
* 说明
        
        呼叫外线号码

### 咨询坐席

* 原型
    
        wincall.fn_consultinner(agentID，caller)
* 参数
    
        agentID：被咨询的坐席ID
        caller：显示的主叫号码
* 说明
        
        在通话中咨询指定的坐席，主要用于外线呼入到坐席，当该坐席有不确定的问题，可以通过该方法来咨询指定的坐席，在咨询时，外线将处于收听音乐的状态

### 咨询外线

* 原型
    
        wincall.fn_consultouter(called，caller)
* 参数
    
        called：被咨询的外线号码
        caller：显示的主叫号码
* 说明
        
        在通话中咨询指定的坐席，主要用于外线呼入到坐席，当该坐席有不确定的问题，可以通过该方法来咨询指定的外线号码，
        在咨询时，外线将处于收听音乐的状态

### 咨询接回

* 原型
    
        wincall.fn_consultback()
* 参数
    
        无
* 说明
        
        接回在咨询的通话，恢复原来的双方通话，将被咨询的内线或者外线挂断

### 转接

* 原型
    
        wincall.fn_transfer()
* 参数
    
        无
* 说明
        
        将当前的通话转接给被咨询的内线坐席或外线号码，该操作只能在咨询通话过程中进行，不能直接使用，
        因为我们在转接前首先需要确认被转接的内线坐席或者外线号码是否能呼通或者有空的情况下才转接，
        如果直接转的话，可能存在转接失败的情况，如果需要直接转的话，那可以使用“盲转”的函数

### 三方

* 原型
    
        wincall.fn_3way()
* 参数
    
        无
* 说明
        
        将当前的通话加入到三方通话中，该操作也只能在咨询通话过程中进行，不能直接使用，原因同“转接”操作

### 三方接回

* 原型
    
        wincall.fn_3wayback()
* 参数
    
        无
* 说明
        
        将当前的三方通话中的被咨询的一方挂断，恢复到原来的双方通话

### 监听

* 原型
    
        wincall.fn_chanspy(agentID)
* 参数
    
        agentID：被监听的坐席ID
* 说明
        
        主要用于班长或管理员坐席监听指定坐席的通话，该情况下班长或管理员不能说话，只能听坐席和对方的通话

### 拦截

* 原型
    
        wincall.fn_intercept()
* 参数
    
        无
* 说明
        
        在监听的状态下，如果班长或管理员发现坐席和对方的通话存在问题，则班长或管理员可以直接拦截该通话，
        自己和对方通话，而将坐席直接挂断

### 强插

* 原型
    
        wincall.fn_breakin()
* 参数
    
        无
* 说明
        
        在监听的状态下，如果班长或管理员发现坐席和对方的通话存在问题，则班长或管理员可以直接使用该方法加入当前的通话中，
        实现三方通话的效果

### 转满意度评价

* 原型
    
        wincall.fn_evaluate()
* 参数
    
        无
* 说明
        
        在通话的情况下将外线转到满意度评价的语音中，收取响应的满意度评价的结果，该结果将保存在通话记录中

### 转技能组

* 原型
    
        wincall.fn_transque(queId)
* 参数
    
        queId：需要转接到的技能组ID
* 说明
        
        将通话直接转接到指定的技能组中进行排队，该方法一个使用场景为外线呼叫中系统中，坐席发现外线咨询的问题不是他能处理的，
        而是另外一个组来处理的，那他就可以将该通话直接转到指定的技能组中，需要注意的是转技能组后将视作一个新的呼入通话，
        在通话记录中将记录为两条通话

### 盲转

* 原型
    
        wincall.fn_bindtrans(called,caller)
* 参数
    
        called：需要盲转的外线号码
        caller：显示的主叫号码
* 说明
        
        该方法将通话直接盲转到指定的外线号码，如果该外线号码正在通话中或无法接通的情况下，那么该通话将直接挂断，而无法接回
        
### 发送DTMF按键

* 原型
    
        wincall.fn_dtmf(num)
* 参数
    
        num：需要发送的按键
* 说明
        
        该方法用于发送在ivr中需要按键的情况，例如在呼叫10086过程中需要按键，那系统中可以使用该方法来发送实际的按键

### 进入自动外呼

* 原型
    
        wincall.fn_autocallin(proId)
* 参数
    
        proId：需要自动外呼的项目id
* 说明
        
        将该坐席放入指定项目的自动外呼的坐席列表中，自动外呼接通后将会将通话转到该坐席，
        自动外呼为设置指定的项目proId为自动外呼状态，那么系统会自动从项目对应的数据库表中获取数据进行外呼，
        外呼接通后将转到该项目中所有的空闲坐席，然后双方进行通话
        
### 退出自动外呼

* 原型
    
        wincall.fn_autocallout()
* 参数
    
        无
* 说明
        
        将该坐席从对应的自动外呼的坐席列表中删除，后续的自动外呼接通的通话将不再分配到该坐席

### 获取当前坐席所属的技能组

* 原型
    
        wincall.fn_get_que()
* 参数
    
        无
* 说明
        
        获取当前坐席所有的技能组，格式为数组，例如[6250, 6251]

### 获取当前账号允许使用的主叫号码

* 原型
    
        wincall.fn_get_caller()
* 参数
    
        无
* 说明
        
        获取当前账号允许使用的主叫号码，格式为数组，例如['58452440','58452444','58452441']
