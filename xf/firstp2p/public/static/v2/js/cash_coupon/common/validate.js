/*
 * validate.js 1.4.1
 * Copyright (c) 2011 - 2014 Rick Harrison, http://rickharrison.me
 * validate.js is open sourced under the MIT license.
 * Portions of validate.js are inspired by CodeIgniter.
 * http://rickharrison.github.com/validate.js
 */

(function(window, document, undefined, $) {
    /*
     * If you would like an application-wide config, change these defaults.
     * Otherwise, use the setMessage() function to configure form specific messages.
     */

    var cnName = {
        mobile: '手机号',
        password: '密码',
        captcha: '图形验证码',
        code: '验证码',
        username: '用户名'
    }

    var defaults = {
        messages: {
            required: '不能为空',
            matches: '两次输入信息不一致',
            valid_email: '邮箱地址无效',
            min_length: '至少需要4个字符',
            max_length: '最多16个字符',
            exact_length: 'The %s field must be exactly %s characters in length.',
            greater_than: 'The %s field must contain a number greater than %s.',
            less_than: 'The %s field must contain a number less than %s.',
            alpha: 'The %s field must only contain alphabetical characters.',
            alpha_numeric: 'The %s field must only contain alpha-numeric characters.',
            alpha_dash: 'The %s field must only contain alpha-numeric characters, underscores, and dashes.',
            numeric: 'The %s field must contain only numbers.',
            integer: 'The %s field must contain an integer.',
            is_natural: 'The %s field must contain only positive numbers.',
            is_natural_no_zero: 'The %s field must contain a number greater than zero.',
            valid_ip: 'The %s field must contain a valid IP.',
            valid_base64: 'The %s field must contain a base64 string.',
            valid_credit_card: 'The %s field must contain a valid credit card number.',
            is_file_type: 'The %s field must contain only %s files.',
            valid_url: 'The %s field must contain a valid URL.',
            phone: '手机号格式不正确',
            tel: '电话号码不正确',
            qq: 'qq号码不正确',
            data: '请输入正确的日期,例:yyyy-mm-dd',
            time: '请输入正确的时间,例:14:30或14:30:00',
            ID_card: '请输入正确的身份证号',
            postcode: '请输入正确的邮政编码',
            chinese: '请输入中文',
            captcha:'验证码不正确',
            code: '请填写6位数字验证码',
            address: '填写正确的地址',
            chineseName: '请输入2-6个汉字中文',
            username: '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母',
            password: '密码为数字，字母及常用符号组成，5-25位',
            fileImage: '图片格式仅限JPG,PNG',
            select: '请选择一项',
            ajax:'ajax验证未通过',
            numReg:'不能只有数字或下划线'
        },
        callback: function(errors) {

        }
    };

    /*
     * Define the regular expressions that will be used
     */

    var ruleRegex = /^(.+?)\[(.+)\]$/,
        numericRegex = /^[0-9]+$/,
        integerRegex = /^\-?[0-9]+$/,
        decimalRegex = /^\-?[0-9]*\.?[0-9]+$/,
        emailRegex = WXLC.ValidateConf.email[3],
        alphaRegex = /^[a-z]+$/i,
        alphaNumericRegex = /^[a-z0-9]+$/i,
        alphaDashRegex = /^[a-z0-9_\-]+$/i,
        naturalRegex = /^[0-9]+$/i,
        naturalNoZeroRegex = /^[1-9][0-9]*$/i,
        ipRegex = /^((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})$/i,
        base64Regex = /[^a-zA-Z0-9\/\+=]/i,
        numericDashRegex = /^[\d\-\s]+$/,
        urlRegex = /^((http|https):\/\/(\w+:{0,1}\w*@)?(\S+)|)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/,
        phoneRegex = WXLC.ValidateConf.mobile[3], //手机号码
        telRegex = /^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:[48]00[\- ]?[1-9]\d{6}))$/, //电话号码
        qqRegex = /^[1-9]\d{4,}$/,
        dateRegex = WXLC.ValidateConf.date[3],
        timeRegex = WXLC.ValidateConf.time[3],
        ID_cardRegex = WXLC.ValidateConf.ID_card[3],
        postcodeRegex = WXLC.ValidateConf.postcode[3],
        chineseRegex = WXLC.ValidateConf.chinese[3],
        chineseNameRegex = WXLC.ValidateConf.chineseName[3],
        usernameRegex = WXLC.ValidateConf.userName[3],
        passwordRegex = WXLC.ValidateConf.password[3],
        numRegEx = /^[_\d]+$/,
        captchaRegEx = WXLC.ValidateConf.captcha[3],
        codeRegEx = WXLC.ValidateConf.code[3],
        addressRegEx = WXLC.ValidateConf.address[3],
        fileImageRegex = /\.jpg$|\.png$/;

    /*
     * The exposed public object to validate a form:
     *
     * @param formNameOrNode - String - The name attribute of the form (i.e. <form name="myForm"></form>) or node of the form element
     * @param fields - Array - [{
     *     name: The name of the element (i.e. <input name="myField" />)
     *     display: 'Field Name'
     *     rules: required|matches[password_confirm]
     * }]
     * @param callback - Function - The callback after validation has been performed.
     *     @argument errors - An array of validation errors
     *     @argument event - The javascript event
     */

    var FormValidator = function(formNameOrNode, fields, callback) {
            this.callback = callback || defaults.callback;
            this.errors = [];
            this.fields = {};
            this.form = this._formByNameOrNode(formNameOrNode) || {};
            this.messages = {};
            this.handlers = {};
            this.conditionals = {};
            var that = this;

            for (var i = 0, fieldLength = fields.length; i < fieldLength; i++) {
                var field = fields[i];

                // If passed in incorrectly, we need to skip the field.
                if ((!field.name && !field.names) || !field.rules) {
                    continue;
                }

                /*
                 * Build the master fields array that has all the information needed to validate
                 */

                if (field.names) {
                    for (var j = 0, fieldNamesLength = field.names.length; j < fieldNamesLength; j++) {
                        this._addField(field, field.names[j]);
                    }
                } else {
                    this._addField(field, field.name);
                }

                //blur事件
                $(this.form[field.name]).on("blur",function(evt) {
                    that._validateblur(evt, that.fields[$(evt.target).attr("name")]);
                });

                //check与radio change事件
                
            }
            $(this.form).bind("submit", function(evt) {
                return that._validateForm(evt);
            });

        },

        attributeValue = function(element, attributeName) {
            var i;

            if ((element.length > 0) && (element[0].type === 'radio' || element[0].type === 'checkbox')) {
                for (i = 0, elementLength = element.length; i < elementLength; i++) {
                    if (element[i].checked) {
                        return element[i][attributeName];
                    }
                }

                return;
            }

            return element[attributeName];
        };

    /*
     * @public
     * Sets a custom message for one of the rules
     */

    FormValidator.prototype.setMessage = function(rule, message) {
        this.messages[rule] = message;

        // return this for chaining
        return this;
    };

    /*
     * @public
     * Registers a callback for a custom rule (i.e. callback_username_check)
     */

    FormValidator.prototype.registerCallback = function(name, handler) {
        if (name && typeof name === 'string' && handler && typeof handler === 'function') {
            this.handlers[name] = handler;
        }

        // return this for chaining
        return this;
    };

    /*
     * @public
     * Registers a conditional for a custom 'depends' rule
     */

    FormValidator.prototype.registerConditional = function(name, conditional) {
        if (name && typeof name === 'string' && conditional && typeof conditional === 'function') {
            this.conditionals[name] = conditional;
        }

        // return this for chaining
        return this;
    };

    /*
     * @private
     * Determines if a form dom node was passed in or just a string representing the form name
     */

    FormValidator.prototype._formByNameOrNode = function(formNameOrNode) {
        return (typeof formNameOrNode === 'object') ? formNameOrNode : document.forms[formNameOrNode];
    };

    /*
     * @private
     * Adds a file to the master fields array
     */

    FormValidator.prototype._addField = function(field, nameValue) {
        this.fields[nameValue] = {
            name: nameValue,
            display: field.display || nameValue,
            rules: field.rules,
            depends: field.depends,
            id: null,
            element: null,
            type: null,
            value: null,
            checked: null
        };
    };

    /*
     * @private
     * Runs the validation when the form is submitted.
     */

    FormValidator.prototype._validateForm = function(evt) {
        this.errors = [];

        for (var key in this.fields) {
            if (this.fields.hasOwnProperty(key)) {
                var field = this.fields[key] || {},
                    element = this.form[field.name];

                if (element && element !== undefined) {
                    field.id = attributeValue(element, 'id');
                    field.element = element;
                    field.type = (element.length > 0) ? element[0].type : element.type;
                    field.value = attributeValue(element, 'value');
                    field.checked = attributeValue(element, 'checked');

                    /*
                     * Run through the rules for each field.
                     * If the field has a depends conditional, only validate the field
                     * if it passes the custom function
                     */

                    if (field.depends && typeof field.depends === "function") {
                        if (field.depends.call(this, field)) {
                            this._validateField(field);
                        }
                    } else if (field.depends && typeof field.depends === "string" && this.conditionals[field.depends]) {
                        if (this.conditionals[field.depends].call(this, field)) {
                            this._validateField(field);
                        }
                    } else {
                        this._validateField(field);
                    }
                }
            }
        }

        if (typeof this.callback === 'function') {
            this.callback(this.errors, evt);
        }

        if (this.errors.length > 0) {
            if (evt && evt.preventDefault) {
                evt.preventDefault();
            } else if (event) {
                // IE uses the global event variable
                event.returnValue = false;
            }
        }

        return true;
    };


    /*
     * @private
     * 鼠标移开事件单个验证
     */

    FormValidator.prototype._validateblur = function(evt, data) {
        this.errors = [];
        //console.log(data);
        var field = data || {},
            element = this.form[field.name];

        if (element && element !== undefined) {
            field.id = attributeValue(element, 'id');
            field.element = element;
            field.type = (element.length > 0) ? element[0].type : element.type;
            field.value = attributeValue(element, 'value');
            field.checked = attributeValue(element, 'checked');

            /*
             * Run through the rules for each field.
             * If the field has a depends conditional, only validate the field
             * if it passes the custom function
             */

            if (field.depends && typeof field.depends === "function") {
                if (field.depends.call(this, field)) {
                    this._validateField(field);
                }
            } else if (field.depends && typeof field.depends === "string" && this.conditionals[field.depends]) {
                if (this.conditionals[field.depends].call(this, field)) {
                    this._validateField(field);
                }
            } else {
                this._validateField(field);
            }
        }

        if (typeof this.callback === 'function') {
            this.callback(this.errors, evt);
        }

        if (this.errors.length > 0) {
            if (evt && evt.preventDefault) {
                evt.preventDefault();
            } else if (event) {
                // IE uses the global event variable
                event.returnValue = false;
            }
        }

        return true;
    };



    /*
     * @private
     * Looks at the fields value and evaluates it against the given rules
     */

    FormValidator.prototype._validateField = function(field) {
        var rules = field.rules.split('|'),
            indexOfRequired = field.rules.indexOf('required'),
            isEmpty = (!field.value || field.value === '' || field.value === undefined);

        /*
         * Run through the rules and execute the validation methods as needed
         */

        for (var i = 0, ruleLength = rules.length; i < ruleLength; i++) {
            var method = rules[i],
                param = null,
                failed = false,
                parts = ruleRegex.exec(method);

            /*
             * If this field is not required and the value is empty, continue on to the next rule unless it's a callback.
             * This ensures that a callback will always be called but other rules will be skipped.
             */

            if (indexOfRequired === -1 && method.indexOf('!callback_') === -1 && isEmpty) {
                continue;
            }

            /*
             * If the rule has a parameter (i.e. matches[param]) split it out
             */

            if (parts) {
                method = parts[1];
                param = parts[2];
            }

            if (method.charAt(0) === '!') {
                method = method.substring(1, method.length);
            }

            /*
             * If the hook is defined, run it to find any validation errors
             */
            //console.log(typeof this._hooks[method]);
            if (typeof this._hooks[method] === 'function') {
                if (!this._hooks[method].apply(this, [field, param])) {
                    failed = true;
                }
            } else if (method.substring(0, 9) === 'callback_') {
                // Custom method. Execute the handler if it was registered
                method = method.substring(9, method.length);

                if (typeof this.handlers[method] === 'function') {
                    if (this.handlers[method].apply(this, [field.value, param, field]) === false) {
                        failed = true;
                    }
                }
            }

            /*
             * If the hook failed, add a message to the errors array
             */

            if (failed) {
                // Make sure we have a message for this rule
                var source = this.messages[field.name + '.' + method] || this.messages[method] || defaults.messages[method],
                    message = 'An error has occurred with the ' + field.display + ' field.';

                if (source) {
                    message = source.replace('%s', field.display);

                    if (param) {
                        message = message.replace('%s', (this.fields[param]) ? this.fields[param].display : param);
                    }
                }
                if ($("#" + field.id).attr("data-validate") != null) {
                    message = $("#" + field.id).attr("data-validate");
                }

                if(method == "required"){
                    message = cnName[field.name] + message;
                }
                //显示失败信息
                $(".yes-" + field.name).css("display", 'none');
                $(".no-" + field.name).find(".msg").html(message);
                var hh = $(".no-" + field.name).find(".msg").height();
                $(".no-" + field.name).removeClass("msg-hide");
                $(".no-" + field.name).height(hh).addClass("msg-show");
                
                this.errors.push({
                    id: field.id,
                    element: field.element,
                    name: field.name,
                    message: message,
                    rule: method
                });
                
                break;
            } else {
                //验证通过
                var msg= $(".no-" + field.name).hasClass("msg-show");
                if (msg) {
                     $(".no-" + field.name).removeClass("msg-show");
                     $(".no-" + field.name).addClass("msg-hide");
                }
                $(".yes-" + field.name).css("display", 'block');
            }
        }
    };

    /*
     * @private
     * Object containing all of the validation hooks
     */

    FormValidator.prototype._hooks = {
        //不能为空
        required: function(field) {
            var value = field.value;
            //console.log(field.type);
            if ((field.type === 'checkbox') || (field.type === 'radio')) {
                return (field.checked === true);
            }
            return (value !== null && value !== '');
        },
        //select 选择验证
        select: function(field, defaultValue) {
            var sel = $("#" + field.id).find('option').not(function() {
                return !this.selected;
            });
            if (sel.val() == defaultValue) {
                return false;
            }
            return true;
        },
        //默认值
        "default": function(field, defaultName) {
            return field.value !== defaultName;
        },

        //比对
        matches: function(field, matchName) {
            var el = this.form[matchName];

            if (el) {
                return field.value === el.value;
            }

            return false;
        },

        //邮箱
        valid_email: function(field) {
            return emailRegex.test(field.value);
        },

        valid_emails: function(field) {
            var result = field.value.split(",");

            for (var i = 0, resultLength = result.length; i < resultLength; i++) {
                if (!emailRegex.test(result[i])) {
                    return false;
                }
            }

            return true;
        },

        //最小长度
        min_length: function(field, length) {
            if (!numericRegex.test(length)) {
                return false;
            }

            return (field.value.length >= parseInt(length, 10));
        },
        //最大长度
        max_length: function(field, length) {
            if (!numericRegex.test(length)) {
                return false;
            }

            return (field.value.length <= parseInt(length, 10));
        },

        exact_length: function(field, length) {
            if (!numericRegex.test(length)) {
                return false;
            }

            return (field.value.length === parseInt(length, 10));
        },

        greater_than: function(field, param) {
            if (!decimalRegex.test(field.value)) {
                return false;
            }

            return (parseFloat(field.value) > parseFloat(param));
        },

        less_than: function(field, param) {
            if (!decimalRegex.test(field.value)) {
                return false;
            }

            return (parseFloat(field.value) < parseFloat(param));
        },

        alpha: function(field) {
            return (alphaRegex.test(field.value));
        },

        alpha_numeric: function(field) {
            return (alphaNumericRegex.test(field.value));
        },

        alpha_dash: function(field) {
            return (alphaDashRegex.test(field.value));
        },

        numeric: function(field) {
            return (numericRegex.test(field.value));
        },

        integer: function(field) {
            return (integerRegex.test(field.value));
        },

        decimal: function(field) {
            return (decimalRegex.test(field.value));
        },

        is_natural: function(field) {
            return (naturalRegex.test(field.value));
        },

        is_natural_no_zero: function(field) {
            return (naturalNoZeroRegex.test(field.value));
        },

        valid_ip: function(field) {
            return (ipRegex.test(field.value));
        },

        valid_base64: function(field) {
            return (base64Regex.test(field.value));
        },

        valid_url: function(field) {
            return (urlRegex.test(field.value));
        },

        //手机电话号码检测
        phone: function(field) {
            return (phoneRegex.test(field.value));
        },
        //固定号码检测
        tel: function(field) {
            return (telRegex.test(field.value));
        },
        //qq号码
        qq: function(field) {
            return (qqRegex.test(field.value));
        },
        //日期
        data: function(field) {
            return (dateRegex.test(field.value));
        },
        //时间
        time: function(field) {
            return (timeRegex.test(field.value));
        },
        //身份证
        ID_card: function(field) {
            return (ID_cardRegex.test(field.value));
        },
        //邮政编码
        postcode: function(field) {
            return (postcodeRegex.test(field.value));
        },
        //中文
        chinese: function(field) {
            return (chineseRegex.test(field.value));
        },
        //姓名中文
        chineseName: function(field) {
            return (chineseNameRegex.test(field.value));
        },
        //用户名
        username: function(field) {
            return (usernameRegex.test(field.value));
        },
        //用户密码
        password: function(field) {
            return (passwordRegex.test(field.value));
        },
        //图片格式
        fileImage: function(field) {
            return (fileImageRegex.test(field.value));
        },
        //数字和下划线
        numReg: function(field) {
             return (numRegEx.test(field.value));
         },
        //验证码
         captcha: function (field) {
             return (captchaRegEx.test(field.value));
         },
         //短信验证码
         code: function (field) {
             return (codeRegEx.test(field.value));
         },
        valid_credit_card: function(field) {
            // Luhn Check Code from https://gist.github.com/4075533
            // accept only digits, dashes or spaces
            if (!numericDashRegex.test(field.value)) return false;

            // The Luhn Algorithm. It's so pretty.
            var nCheck = 0,
                nDigit = 0,
                bEven = false;
            var strippedField = field.value.replace(/\D/g, "");

            for (var n = strippedField.length - 1; n >= 0; n--) {
                var cDigit = strippedField.charAt(n);
                nDigit = parseInt(cDigit, 10);
                if (bEven) {
                    if ((nDigit *= 2) > 9) nDigit -= 9;
                }

                nCheck += nDigit;
                bEven = !bEven;
            }

            return (nCheck % 10) === 0;
        },

        is_file_type: function(field, type) {
            if (field.type !== 'file') {
                return true;
            }

            var ext = field.value.substr((field.value.lastIndexOf('.') + 1)),
                typeArray = type.split(','),
                inArray = false,
                i = 0,
                len = typeArray.length;

            for (i; i < len; i++) {
                if (ext == typeArray[i]) inArray = true;
            }

            return inArray;
        },
        ajax: function(field) {
            $.ajax({
                type: 'post',
                url: $("."+field.name).attr("data-ajaxurl"),
                dataType: "json",
                data: "result=" + $("."+field.name).val(),
                async: false,
                success: function(data) {
                    var data2 = eval(data);
                    if (data2.state == "1") {
                        return true;
                    } else {
                        return false;
                    }
                },
                error: function(xhr, type) {
                    return false;
                }
            });
        }
    };

    window.FormValidator = FormValidator;

})(window, document, undefined, $);
