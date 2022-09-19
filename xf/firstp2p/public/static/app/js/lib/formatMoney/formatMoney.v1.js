/*
 *_nowMoney  传入金额
 *formatClass  ul class
 *activeClass  当前选中class
*/
$.getformatMoney = function (_nowMoney, formatClass, activeClass) {
    var html="";
    var MoneyArr =[ '个', '十', '百', '千', '万', '十万', '百万', '千万'];
    if (_nowMoney != "" && !isNaN(_nowMoney)) {
        _nowMoney=Number(_nowMoney);
        html = '<ul class="' + formatClass + ' clearfix">';
        //转换
        var _intlength = parseInt(_nowMoney).toString().length;
        if (_intlength > 8) {
            _intlength = 8;
        }
        for (var i = _intlength-1; i >=0; i--) {
            if (i == _intlength - 1) {
                html += '<li class="'+ activeClass +' money_li'+ i +'">'+ MoneyArr[i] +'</li>';
            } else {
                html += '<li class=' +' money_li'+ i +'>' + MoneyArr[i] + '</li>';
            }
        }
        html += '</ul>';
    } else {
        html = '';
    }
    return html;

}