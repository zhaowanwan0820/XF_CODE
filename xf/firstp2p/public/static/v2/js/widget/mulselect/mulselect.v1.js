/**
 * 命名空间Firstp2p
 * @nampespace Firstp2p
 * mulDom       下拉框容器
 * url          列表数据文件路径（josn 格式）
 * defaultdata  默认数据
 * firstTitle:  '--请选择--' 默认下拉选框的标题
 * firstValue:  '0'  默认下拉选框的值
 * selectsClass 下拉框默认样式
 * jsonsingle   Json 数据源中没有子项的元素
 * jsonmany     Json 数据源中包含子项的元素
 * jsonfirstName Json 数据源最外层数据的名称
 * typestate    如果选项是年月日 则设置为 time 默认为空
 */

/**
 * 更新日志
 * v1.1
 * 修复同一页面多个select干扰bug
 * 修复默认值匹配逻辑错误
 * 修复firstValue不为0的firstTitle的默认选项错误
 * 新增年月日选项的建立（自动生成数据）
 * 新增私有方法_buildTimeDay计算日期
 * 优化变量声明，删除不必要逻辑
 */

if (typeof Firstp2p == "undefined") {
    Firstp2p = {};
}
//组件闭包开始
(function() {

    /**
     * @module 多级联动
     */
    var mulselect = function(element, options) {
        if (!$(element).length) {
            return;
        };
        // 默认值
        var defaults = {
            typestate: null,
            mulDom: null,
            url: null,
            defaultdata: [],
            firstTitle: '--请选择省--:--请选择市--:--请选择区县--',
            firstValue: '0',
            selectsClass: "select",
            jsonsingle: "v",
            jsonmany: "s",
            jsonfirstName: "Jsonlist",
            destroy: null,
            callback: null,
            selectName : $(element).data("selectname")

        };
        this.settings = $.extend({}, defaults, options);
        //属性begin

        /**
         * 是否为年月日
         * @attribute  typestate
         * @type {string} 如果选项是年月日 则设置为 time 默认为空
         */
        this.typestate = this.settings.typestate;
        /**
         * 下拉框容器
         * @attribute  mulDom
         * @type {Dom对象} 下拉框容器,示例：$("#id")
         */
        this.mulDom = this.settings.mulDom;
        /**
         * 数据来源
         * @attribute  URL
         * @type {JSON数组} 列表数据文件路径（josn 格式）
         */
        this.url = this.settings.url;
        /**
         * 默认数据
         * @attribute  defaultdata
         * @type {Array} 默认数据
         */
        this.defaultdata = this.settings.defaultdata;
        /**
         * 默认下拉选框的标题
         * @attribute  firstTitle
         * @type {string} '--请选择--' 默认下拉选框的标题
         */
        this.firstTitle = this.settings.firstTitle.split(":");
        /**
         * 默认下拉选框的值
         * @attribute  firstValue
         * @type {string} '0' 默认下拉选框的值
         */
        this.firstValue = this.settings.firstValue;
        /**
         * 下拉框默认样式
         * @attribute  selectsClass
         * @type {string} 每个下拉框默认样式
         */
        this.selectsClass = this.settings.selectsClass;
        /**
         * Json 数据源中没有子项的元素
         * @attribute  jsonsingle
         * @type {string} Json 数据源中没有子项的元素
         */
        this.jsonsingle = this.settings.jsonsingle;
        /**
         * Json 数据源中包含子项的元素
         * @attribute  jsonmany
         * @type {string} Json 数据源中包含子项的元素
         */
        this.jsonmany = this.settings.jsonmany;
        /**
         * Json 数据源中包含子项的元素
         * @attribute  jsonmany
         * @type {string} Json 数据源中包含子项的元素
         */
        this.jsonfirstName = this.settings.jsonfirstName;
        /**
         * 组件销毁
         * @attribute  destroy
         * @type {string} 是否销毁组件
         */
        this.destroy = this.settings.destroy;
        //属性end
        //组件销毁

        this.nameArr = !!this.settings.selectName ? this.settings.selectName.split(":") : ["select0" , "select1" , "select2","select3","select4","select5"];

        if (this.destroy == "destroy" && this.mulDom != null) {
            this.destroysel();
            return;
        }
        this._init();
    };

    $.extend(mulselect.prototype, {
        /**
         * 组件初始化,获取数据信息，生成select
         * @method _init
         * @return none
         */
        _init: function() {
            var _this = this;
            //判断容器是否存在
            if (!_this.mulDom.length) {
                return;
            }
            //选项变更事件
            $(_this.mulDom).on('change', 'select[class=' + _this.selectsClass + ']', function() {
                _this._selectChange(this.name);
                if (!!_this.settings.callback) {
                    _this.settings.callback;
                }
            });

            //如果设置为年月日格式
            if (_this.typestate == "time") {
                _this._bindSelectHtml(3, 0);
            } else {
                // 读取 URL，通过 Ajax 获取数据
                if (typeof _this.url === 'string') {
                    $.getJSON(_this.url, function(json) {
                        _this.dataJson = json;
                        _this._buildContent(_this.dataJson.Jsonlist, 0);
                    });

                    // 读取自定义数据
                } else if (typeof _this.url === 'object') {
                    _this.dataJson = _this.url;
                    _this._buildContent(_this.dataJson.Jsonlist, 0);
                }

            }
        },
        /**
         * 生成日期下拉框
         * @method _buildContentt
         * @return none
         */
        _buildTimeDay: function(selyear, selmouth) {
            var year = parseInt(selyear),
                month = parseInt(selmouth),
                dayCount = 0,
                _html = "";
            switch (month) {
                case 1:
                case 3:
                case 5:
                case 7:
                case 8:
                case 10:
                case 12:
                    dayCount = 31;
                    break;
                case 4:
                case 6:
                case 9:
                case 11:
                    dayCount = 30;
                    break;
                case 2:
                    dayCount = 28;
                    if ((year % 4 == 0) && (year % 100 != 0) || (year % 400 == 0)) {
                        dayCount = 29;
                    }
                    break;
                default:
                    break;
            }
            for (var i = 1; i <= dayCount; i++) {
                _html += '<option value="' + i + '">' + i + '日</option>';
            }
            return _html;
        },
        /**
         * 根据数据生成下拉框
         * @method _buildContentt
         * @return none
         */
        _buildContent: function(NewData, selSum) {
            var _this = this;
            // 判断JSON是否为空
            if ($.isEmptyObject(NewData) && _this.typestate == null) {
                return;
            }
            //如果不是重置选项
            if (selSum == 0) {
                // 循环遍历数据源 返回最深层级
                _this.ArrayJson = [];
                _this.selSum = 0;
                var GetJsonList = function(NewData) {
                    $.each(NewData, function(i, k) {
                        if (typeof(k[_this.jsonmany]) === "object" && k[_this.jsonmany] != "undefined") {
                            _this.selSum++;
                            GetJsonList(k[_this.jsonmany], _this.selSum);
                        } else {
                            return true;
                        }
                    });
                    if (_this.selSum != 0) {
                        _this.ArrayJson.push(_this.selSum);
                    }
                    _this.selSum = 0;
                };

                GetJsonList(_this.dataJson.Jsonlist, 0);
                //备份数组层级
                _this.NewArrayJson = _this.ArrayJson.slice(0);
                //获取最大层级
                _this.JsonTopSum = _this.NewArrayJson.sort(function(a, b) {
                    return a < b ? 1 : -1;
                })[0];
            }

            //生成Select选项
            _this._bindSelectHtml(_this.JsonTopSum + 1, selSum);
        },

        /**
         * 生成select Html  并绑定选项
         * @method _bindSelectHtml
         * @return none
         */
        _bindSelectHtml: function(num, state) {
            var _this = this,
                _html = "",
                yearNow = new Date().getFullYear(),
                settings = this.settings,
                selectName = settings.selectName,
                //console.log(_this.selectName);
                nameArr = this.nameArr;
            //_this.nameArr[i]请选择
            for (var i = 0; i < num; i++) {
                //生成html
                _html += '<select  class="' + _this.selectsClass + '" name="' + nameArr[i] + '" data-selectjson="ms_'+ i + '">';
                //第一个选项
                //console.log(_this.firstTitle[i]);
                _html += '<option value="' + _this.firstValue + '">' + _this.firstTitle[i] + '</option>';
                if (i == 0 && _this.typestate == null) {
                    _html += _this._JsonCycle(_this.dataJson.Jsonlist);
                } else {
                    if (i == 0 && _this.typestate == "time") {
                        for (var y = yearNow; y >= 1900; y--) {
                            _html += '<option value="' + y + '">' + y + '年</option>';
                        }
                    }

                }
                _html += "</select>";
            }
            //console.log(_html);
            //追加到容器中
            $(_this.mulDom).empty().append(_html);
            if (state == 0) {
                _this._bindDefaultdata(0);
            }
        },
        /*
         * 选择变更事件
         * @method _selectChange
         * @return none
         */
        _selectChange: function(name) {
            var _this = this,
                //获取当前的选中的索引
                select = $(_this.settings.mulDom).find("select[name=" + name + "]"),
                selectIndex = select.get(0).selectedIndex - 1,
                //获取当前选中的值
                selValues = select.val(),
                //分隔name获取当前的select
                ArrName = select.data("selectjson").split("_"),
                NextselectNum = parseInt(ArrName[1]) + 1,
                //获取当前数据的层级
                selCeng = ArrName[1] != 0 ? $(_this.mulDom).find("select").get(0).selectedIndex - 1 : selectIndex,
                //记录每个select选中的索引
                selectValues = [],
                //获取页面当前多少select
                moreSel = $(_this.mulDom + "> select").length,
                //循环填充下一个select的值
                i = 0,
                thisData = null,
                nameArr = this.nameArr,
                _html = "";

            if (_this.typestate == "time") {
                _this.ArrayJson = [2, 2, 2];
                selCeng = 1;
            }
            //如果最外层选中的是--请选择--
            if (selValues == _this.firstValue) {
                if (ArrName[1] == 0 && _this.typestate == null) {
                    _this._buildContent(_this.dataJson.Jsonlist, 1);
                    return;
                } else {
                    for (var i = ArrName[1]; i <= _this.ArrayJson[selCeng]; i++) {
                        $(_this.mulDom).find("select[name=" + nameArr[i] + "]").val(_this.firstValue);
                    }
                }
            }
            for (i = 0; i < moreSel; i++) {
                selectValues.push($(_this.mulDom).find("select[name=" + nameArr[i] + "]").get(0).selectedIndex - 1);
                //当前select后面的置为--请选择--
                if (i > ArrName[1]) {
                    $(_this.mulDom).find("select[name=" + nameArr[i] + "]").val(_this.firstValue);
                }
            }
            if (parseInt(ArrName[1]) == 0) {
                //根据层级重新生成下拉框
                if (_this.typestate == "time") {
                    _this._bindSelectHtml(3, 1);
                } else {
                    _this._bindSelectHtml(_this.ArrayJson[selectIndex] + 1, 1);
                }
                //绑定选中的值
                $(_this.mulDom).find("select[name=" + name + "]").get(0).selectedIndex = selectValues[0] + 1;

            }
            //计算是否是最后一个选框
            if (NextselectNum <= _this.ArrayJson[selCeng]) {
                //第一个选项
                _html += '<option value="' + _this.firstValue + '">' + _this.firstTitle[NextselectNum] + '</option>';
                if (_this.typestate != "time") {
                    //计算数据源
                    _this.selData = _this.dataJson.Jsonlist;
                    for (i = 0; i < NextselectNum; i++) {
                        thisData = _this.selData[selectValues[i]][_this.jsonmany];
                        if (typeof(thisData) === "object" && thisData != "undefined") {
                            _this.selData = _this.selData[selectValues[i]][_this.jsonmany];
                        }
                    }
                    _html = _html + this._JsonCycle(_this.selData);
                    //console.log(_html);
                } else {
                    //如果是第二个选项 生成月
                    if (NextselectNum == 1) {
                        for (var i = 1; i <= 12; i++) {
                            _html += '<option value="' + i + '">' + i + '月</option>';
                        }
                    }
                    //如果是第三个选项  生成日
                    if (NextselectNum == 2) {
                        var selyear = $(_this.mulDom).find("select[name=ms_0]").val();
                        var selmouth = $(_this.mulDom).find("select[name=ms_1]").val();
                        //如果年月未选择
                        if (selyear == _this.firstValue || selmouth == _this.firstValue) {
                            _html += "";
                        } else {
                            //生成日期选择项
                            _html += _this._buildTimeDay(selyear, selmouth);
                        }
                    }
                }


                $(_this.mulDom).find("select[name=" + nameArr[NextselectNum] + "]").empty().append(_html);
                //如果有默认值
                if (_this.defaultdata.length > 0) {
                    _this._bindDefaultdata(NextselectNum);
                }
            }

        },

        /*
         * 绑定默认值
         * @method _bindDefaultdata
         * @return none
         */
        _bindDefaultdata: function(defaultNum) {
            //console.log(defaultNum);
            var _this = this,
                nameArr = this.nameArr,
                count = $(_this.mulDom).find("select[name=" + nameArr[defaultNum] + "] option").length,
                beforeNum = 0,
                $defaultDom = $(_this.mulDom).find("select[name=" + nameArr[defaultNum] + "]");
            //如果第一默认值没有匹配上，则直接调回
            beforeNum = defaultNum != 0 ? defaultNum - 1 : beforeNum;
            if ($(_this.mulDom).find("select[name=" + nameArr[beforeNum] + "]").val() == _this.firstValue && defaultNum != 0) {
                return;
            }
            if (_this.defaultdata) {
                for (var i = 0; i < count; i++) {
                    if ($defaultDom.get(0).options[i].text == _this.defaultdata[defaultNum]) {
                        $defaultDom.get(0).options[i].selected = true;
                        $defaultDom.trigger('change');
                        break;
                    }
                }
                //循环绑定完成 清空defaultdata 避免重复绑定默认值
                _this.defaultdata = [];
            }
        },
        /*
         * 循环遍历
         * @method _JsonCycle
         * @return _html
         */
        _JsonCycle: function(jsonData) {
            var _this = this,
                _html = "",
                splitJson = [];
            $.each(jsonData, function(i, v) {
                if (typeof(v[_this.jsonsingle]) === 'string' || typeof(v[_this.jsonsingle]) === 'number' || typeof(v[_this.jsonsingle]) === 'boolean') {
                    //分隔数据字符串
                    splitJson = v[_this.jsonsingle].split(":");
                    if (splitJson.length >= 2) {
                        _html += '<option value="' + splitJson[1] + '">' + splitJson[0] + '</option>';
                    } else {
                        _html += '<option value="' + splitJson[0] + '">' + splitJson[0] + '</option>';
                    }

                }
            });
            return _html;
        },
        /**
         * 公共方法，组件销毁，清理内存
         * @public
         * @method _destory
         * @required
         * @return none
         */
        destroysel: function() {
            var _this = this;
            //去除事件的绑定
            $(_this.mulDom).off('change', 'select[class=' + _this.selectsClass + ']', function() {});
            //清空组件
            $(_this.mulDom).empty();
        }
    });

    Firstp2p.mulselect = function(element, options) {
        return new mulselect(element, options);
    };

})();