<?php
//token验证url
return [
    '/borrow/borrowGuarantorCollection/setRepayStatus',
    '/default/index/editPassword',
    '/account/accountRecharge/admin',
    '/user/userCallbackLog/ajaxAdmin',
    '/user/userCallbackLog/Admin',
    '/default/index/login',
    '/login',
    '/default/index/editPassword',//修改密码[双因子+token]
    '/user/user/update',//投资者管理->投资者列表->修改 [双因子+token]
    '/user/userId5Log/returnSaveRemark',//投资者管理->接口实名认证->备注
    //'/user/user/liveBandByAttributes', //投资者管理->投资者列表->审核通过/解绑/解绑并删除
    '/account/accountRecharge/create',//资金管理->充值管理->添加线下充值
    '/user/user/createAuthCash',//资金管理->委托提现管理->添加线下提现申请
    '/borrow/borrow/create',//项目管理->项目列表->添加项目
    '/borrow/borrow/update',//项目管理->项目列表->编辑
    '/borrow/agreementTemplate/create',//项目管理->合同管理->添加合同
    '/borrow/agreementTemplate/update',//项目管理->合同管理->编辑合同
    '/reward/itzReward/update',//项目管理->奖励类别->编辑
    '/borrow/guarantorNew/create',//合作方管理->合作保障机构管理->添加保障机构
    '/borrow/guarantorNew/update',//合作方管理->合作保障机构管理->编辑保障机构
    '/user/user/updateEnterprise',//合作方管理->企业管理->编辑企业
    '/user/user/createEnterprise',//合作方管理->企业管理->新增企业
    '/user/user/checkStatus',//合作方管理->企业管理->审核
    '/borrow/borrowCreditor/update',//合作方管理->债务人列表->编辑
    '/borrow/loanApplication/changeStatus',//合作方管理->借款申请管理->备注
    '/borrow/loanApplication/update',//合作方管理->借款申请管理->编辑
    '/credit/creditType/create',//奖励管理->积分类型管理->添加积分类型
    '/credit/creditType/create',//奖励管理->积分类型管理->编辑
    '/credit/creditExchangeGoods/create',///奖励管理->积分类型管理->添加积分兑换物品规则
    '/credit/creditExchangeGoods/update',///奖励管理->积分类型管理->修改
    '/reward/itzReward/create',//奖励管理->论坛金币奖励
    '/borrow/notices/list',//内容管理->网站维护公告
    '/borrow/notices/listOne',//内容管理->紧急公告
    '/itzHelpCenter/itzHelpCenter/create',//内容管理->帮助中心->添加一级目录
    '/itzHelpCenter/itzHelpCenter/update',//内容管理->帮助中心->编辑
    '/article/article/create',//内容管理->网站内容管理->添加
    '/article/article/update',//内容管理->网站内容管理->编辑
    '/scrollpic/scrollpic/create',//内容管理 > 焦点图管理 > 上传焦点图
    '/scrollpic/scrollpic/update',//内容管理 > 焦点图管理 > 修改
    '/borrow/indexOrder/admin',//内容管理 > 首页产品顺序调整
    '/article/links/saveLinks',//内容管理 > 友链管理 > 编辑/添加友链
    '/user/comment/returnSaveComment',//内容管理 > 原评论管理 > 审核
    '/article/itzSeoArticle/create',//内容管理 > SEO专栏文章列表 > 添加seo专栏文章
    '/article/itzSeoArticle/update',//'内容管理 > SEO专栏文章列表 > 编辑'
    '/payment/payment/create',//支付管理 > 支付通道管理 > 添加支付通道
    '/payment/payment/update',//支付管理 > 支付通道管理 > 编辑
    '/payment/itzBank/create',//支付管理 > 支付银行管理 > 添加支付银行
    '/payment/itzBank/update',//支付管理 > 支付银行管理 > 编辑
    '/payment/ItzBankPayment/create',//支付管理 > 银行通道管理 > 新增通道关系
    '/payment/itzBankPayment/update',//支付管理 > 银行通道管理 > 编辑
    '/payment/itzBankBranch/create',//支付管理 > 分支行管理 > 添加分支行信息
    '/payment/itzBankBranch/update',//支付管理 > 分支行管理 > 编辑分支行信息
];
?>






















