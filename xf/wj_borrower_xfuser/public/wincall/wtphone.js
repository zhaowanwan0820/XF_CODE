/**
 * @param options
 * @constructor wtPhone
 */
function wtPhone(options) {
    var defaults = {
        reconnect: true,
        reconnectTimes: 10,
        cmdopen: false,
        eventopen: false,
        cmdclose: false,
        eventclose: false,
        cmdid: 1,
        eventid: 1,
        wincall: null
    };

    this.options = $.extend(defaults, options);
}

wtPhone.prototype.isLogin = false;
wtPhone.prototype.wsocket_event = null;
wtPhone.prototype.wsocket_cmd = null;

/**
 * 打印日志
 * @param msg
 */
wtPhone.prototype.log = function(msg) {
    if (window.console && this.options.wincall.opts.debug) {
        console.log(msg);
    }
};

/**
 * 初始化接收消息连接
 */
wtPhone.prototype.init_event = function () {
    var self = this;
    try {
        self.wsocket_event = new WebSocket('ws://'+this.options.ip+':'+this.options.port);
        self.wsocket_event.onopen = function(){
            self.log('init_event onopen sucessfully! '+ this.readyState);
            self.open_status(2, this.readyState);
        };
        self.wsocket_event.onmessage = function(event) {
            self.log('init_event onmessage:', event.data);
            self.response(event);
        };
        self.wsocket_event.onclose   = function(event) {
            self.log('init_event onclose: ', event);
            self.close_statue(2, this.readyState);
        };
        self.wsocket_event.onerror = function(event) {
            self.log('init_event onerror: ', event);
        }
    } catch (exception) {
        self.log(exception);
    }
};

/**
 * 初始化发送消息连接
 */
wtPhone.prototype.init_cmd = function() {
    var self = this;
    try {
        self.wsocket_cmd = new WebSocket('ws://'+this.options.ip+':'+this.options.port);
        self.wsocket_cmd.onopen = function(){
            self.log('init_cmd onopen sucessfully!');
            self.open_status(1, this.readyState);
        };
        self.wsocket_cmd.onmessage = function(event){
            self.log('init_cmd onmessage:', event);
            self.response(event);
        };
        self.wsocket_cmd.onclose   = function(event){
            self.log('init_cmd onclose', event);
            self.close_statue(1, this.readyState);
        };
        self.wsocket_cmd.onerror = function(event){
            self.log('init_cmd onerror', event);
            alert("请确认是否安装了WTphone或服务是否已经启动");
            if (confirm("是否需要下载WTphone软件")) {
                location.href='WTPhone.exe';
            }
        }
    } catch (exception) {
        self.log(exception);
    }
};

/**
 * 建立连接后操作
 *
 * @param linktype
 * @param status
 */
wtPhone.prototype.open_status = function (linktype, status) {
    if (linktype == 1 && status == 1) {
        this.options.cmdopen = true;
    } else if (linktype == 2 && status == 1) {
        this.options.eventopen = true;
    }

    if (this.options.cmdopen == true && this.options.eventopen == true) {
        this.event_register();
        this.cmd_register();
        this.get_regstatus();
        this.get_callstatus();
    }
};

/**
 * 关闭连接操作
 *
 * @param linktype
 * @param status
 */
wtPhone.prototype.close_statue = function (linktype,status) {
    if (linktype == 1 && status == 3) {
        this.cmdclose = true;
    } else if(linktype == 2 && status == 3) {
        this.eventclose = true;
    }

    if (this.cmdclose == true || this.eventclose == true) {
        this.wsocket_event.close();
        this.wsocket_cmd.close();
    }
};

/**
 * EVENT连接认证
 */
wtPhone.prototype.event_register = function () {
    try{
        this.log('{"Action":400,"Link":200,"Actid":'+this.options.eventid+'}');
        this.wsocket_event.send('{"Action":400,"Link":200,"Actid":'+this.options.eventid+'}');
        this.options.eventid++;
    } catch(ex){
        this.log(ex);
    }
};

/**
 * CMD连接认证
 */
wtPhone.prototype.cmd_register = function () {
    this.send('{"Action":400,"Link":100,"Actid":'+this.options.cmdid+'}');
    this.options.cmdid++;
};

/**
 * WTPhone登录
 *
 * @param pho_num
 * @param pho_pwd
 * @param ip
 * @param port
 */
wtPhone.prototype.login = function (pho_num, pho_pwd,ip,port) {
    var msg= '"user":"'+pho_num+ '",'+'"ip":"'+ip+'",'+'"port":'+port+","+'"pass":"'+pho_pwd+'"';
    this.send('{"Action":100,"Actid":'+this.options.cmdid+','+msg+'}');
    this.options.cmdid++;
};

/**
 * 注销
 */
wtPhone.prototype.logout = function () {
    this.send('{"Action":101,"Actid":'+this.options.cmdid+'}');
    this.options.cmdid++;
};
/**
 * 获取注册状态
 */
wtPhone.prototype.get_regstatus = function () {
    this.send('{"Action":150,"Actid":'+this.options.cmdid+'}');
    this.options.cmdid++;
};

/**
 * 获取通话状态
 */
wtPhone.prototype.get_callstatus = function () {
    this.send('{"Action":151,"Actid":'+this.options.cmdid+'}');
    this.options.cmdid++;
};

/**
 * 应答
 */
wtPhone.prototype.wt_answer = function () {
    this.send('{"Action":103,"Actid":'+this.options.cmdid+'}');
    this.options.cmdid++;
};
/**
 * 挂机
 */
wtPhone.prototype.fn_hangup = function () {
    this.send('{"Action":104,"Actid":'+this.options.cmdid+'}');
    this.options.cmdid++;
};

/**
 * 发送命令
 *
 * @param message
 */
wtPhone.prototype.send = function(message) {
    try{
        this.wsocket_cmd.send(message);
    } catch(ex){
        this.log(ex);
    }
};

wtPhone.prototype.ping = function (message) {
    this.send(message);
};

/**
 * 电话消息处理
 *
 * @param {object} obj
 * @param {object} obj.data
 * @param {int} obj.Action
 * @param {int} obj.Status
 */
wtPhone.prototype.response = function (obj) {
    obj = JSON.parse(obj.data);
    var message = '';
    switch (obj.Action) {
        case 210://注册事件
            switch (obj.Status) {
                case 0: message = '210000';this.isLogin = true; break;//已注册
                case 1: message = '210001';break;//注册失败
                case 2: message = '210002';break;//注册中
                case 3: message = '210003';break;//注册失败，密码错误
                case 4: message = '210004';break;//已注册，无法连接电话服务器
                default :message = '210999';break;//未知状态
            }
            break;
        case 211://注销事件
            switch (obj.Status) {
                case 0: message = '211000';break;//注销成功
                default : message = '211999';break;//未知状态
            }
            break;
        case 215://注册查询
            switch (obj.Status) {
                case 0: message = '215000';this.isLogin = true;break;//已注册
                case 1: message = '215001';this.isLogin = false;break;//未注册
                default : message = '215999';this.isLogin = false;break;//未知状态
            }
            break;
        case 220://通话事件
        case 221://通话事件查询
            switch (obj.Status) {
                case 1:break;//语音流(不处理)
                case 2: message = '222002'; break;//来电
                case 3: message = '222003'; break;//暂停
                case 4: message = '222004'; break;//接通
                case 5: break;//暂停中(不处理)
                case 6: message = '222006'; break;//恢复
                case 7: message = '222007'; break;//外呼
                case 8: message = '222008'; break;//挂机
                case 9: message = '222009'; break;//对方暂停电话
                case 10: message = '222010'; break;//呼叫错误
                case 11: break;//视频请求（不处理）
                case 12: message = '222012';break;//外呼进行中（不处理）
                case 13://外呼回铃
                case 14://外呼彩铃
                    message = '222013'; break;
                case 15:break;//外呼更新(不处理)
                case 16:break;//内核释放呼叫(不处理)
                case 20://空闲
                case 21://空闲		//查询操作独有
                    message = '222020'; break;
                default:message = '220999';break;
            }
            break;
        case 400: message = '400000'; this.isLogin = false;this.logout(); break;//正在注销上一个用户，请稍后再登陆。。。
        case 401: message = '401000'; break;//请先登陆账号
        case 402: message = '402000'; break;//请在登录后使用通话操作
        default: break;
    }

    var responseMessage = '';
    if (!message) {
        responseMessage = 'action='+obj.Action+' status='+obj.Status;
    } else {
        responseMessage = this.responseCode[message] || '未定义'+message;
    }

    this.options.wincall.opts.event_listener('电话-'+responseMessage);
};

/** 消息返回编码 */
wtPhone.prototype.responseCode = {
    '210000': '注册成功',
    '210001': '注册失败',
    '210002': '注册中',
    '210003': '注册失败，密码错误',
    '210004': '已注册，无法连接电话服务器',
    '210999': '未知错误-210999',
    '211000': '注销成功',
    '211099': '未知错误-211999',
    '215000': '已注册',
    '215001': '未注册',
    '215999': '未知错误-215099',
    '222002': '来电',
    '222003': '暂停',
    '222004': '接通',
    '222006': '恢复',
    '222007': '外呼',
    '222008': '挂机',
    '222009': '对方暂停电话',
    '222010': '呼叫错误',
    '222012': '外呼进行中',
    '222013': '外呼回铃',
    '222020': '空闲',
    '400000': '已注册',
    '401000': '未登录',
    '402000': '请在登录后使用通话操作'
};