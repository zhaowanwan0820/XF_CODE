/**
 * 提交 form 确认
 */

$(function(){
    $("#de-form").submit(function(){
        var btn = $("#de-form").find(".j_submit");
        var type = $('input:radio[name="is_right_now"]:checked').val();
        if(1 == type){
            if(!confirm('确定立即执行？')){
                return false;
            };
        }
        if ((1 == $('#job_type').val()) || (4 == $('#job_type').val())) {
            Date.prototype.diff = function(date) {
                var diff_day = (this.getTime() - date.getTime())/(24 * 60 * 60 * 1000);
                return diff_day < 0 ? Math.floor(diff_day - 1) : Math.ceil(diff_day + 1);
            }
            var func_get_date = function (date) {
                month = date.getMonth() + 1;
                return date.getFullYear() + '年' + month + '月' + date.getDate() + '日';
            }
            var now = new Date();
            now.setUTCHours(0, 0, 0, 0);
            var next_repay_time_arr = $('#next_repay_time').val().split('-');
            var next_repay_time_date = new Date(next_repay_time_arr[0], next_repay_time_arr[1] - 1, next_repay_time_arr[2], 8);
            var diff_day = next_repay_time_date.diff(now);
            return confirm('最近一期还款日' + func_get_date(next_repay_time_date) + '—设置日期' + func_get_date(now) + ' ，共 ' + diff_day + ' 天！');
        }else{
            return true;
        }
        return true;
    })
});
