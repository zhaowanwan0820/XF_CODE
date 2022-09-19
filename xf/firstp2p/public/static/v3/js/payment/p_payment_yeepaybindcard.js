$(function() {
    //银行列表
    $(".select_bankname").select();

    //表单验证
    $('#bankInfoForm').validator({
        rules: {
            bankNo: [/^\d{12,20}$/, '请输入正确的银行卡号']
        },
        fields: {
            bankNo: "银行卡号: required;bankNo;"
        }
    });
});