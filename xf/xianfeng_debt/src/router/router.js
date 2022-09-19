export default [
  // {
  //   name: 'debtAgreement',
  //   path: '/debtAgreement',
  //   component: () => import(/* webpackChunkName: 'weight1' */ '../page/purchase/DebtAgreement'),
  //   meta: {
  //     title: '债权转让协议',
  //   }
  // },
  {
    name: 'huiYuanNoviceRaiders',
    path: '/huiYuanNoviceRaiders',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/huiyuan/NoviceRaiders'),
    meta: {
      title: '汇源专区-新手引导'
    }
  },
  {
    name: 'myAreaDebt',
    path: '/myAreaDebt',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/user/MyAreaDebt'),
    meta: {
      title: '可用债权',
      requireAuth: true
    }
  },
  {
    name: 'bankCard',
    path: '/bankCard',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/user/BankCard'),
    meta: {
      title: '银行卡信息',
      requireAuth: true
    }
  },

  {
    name: 'debtAgreementDemo',
    path: '/debtAgreementDemo',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/user/DebtAgreementDemo'),
    meta: {
      title: '债权转让协议范本'
    }
  },
  {
    name: 'sellSuccess',
    // path: '/success/:id',
    path: '/sellSuccess',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/purchase/SellSuccess'),
    meta: {
      title: '操作结果',
      hideHeader: true,
      requireAuth: true
    }
  },
  {
    name: 'sellConfirmation',
    path: '/sellConfirmation',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/purchase/SellConfirmation'),
    meta: {
      title: '出售确认',
      requireAuth: true
    }
  },
  {
    name: 'purchaseDetails',
    path: '/purchaseDetails',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/purchase/PurchaseDetails'),
    meta: {
      title: '说明'
    }
  },
   {
    name: 'purchase',
    path: '/purchase',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/purchase/PurchaseList'),
    meta: {
      title: '求购列表',
      // requireAuth: true
    }
  },
  {
    name: 'huiYuanArea',
    path: '/huiYuanArea',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/huiyuan/Home'),
    meta: {
      title: '汇源专区',
      isshowtabbar: true,
      hideHeader: true
    }
  },
  {
    name: '',
    path: '/',
    redirect: '/debtMarket'
  },
  {
    name: 'debtMarket',
    path: '/debtMarket',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/home/Home'),
    meta: {
      title: '债转市场',
      isshowtabbar: true,
      hideHeader: true
    }
  },
  {
    name: 'noviceRaiders',
    path: '/noviceRaiders',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/home/NoviceRaiders'),
    meta: {
      title: '新手攻略'
    }
  },
  {
    name: 'debtAgreement',
    path: '/debtAgreement',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/user/DebtAgreement'),
    meta: {
      title: '债转服务协议'
    }
  },
  {
    name: 'mydebt',
    path: '/mydebt',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/user/MyDebt'),
    meta: {
      title: '我的债转',
      hideHeader: true
    }
  },
  {
    name: 'release',
    path: '/release',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/release/Release'),
    meta: {
      title: '发布转让',
      keepAlive: true
    }
  },
  {
    name: 'success',
    // path: '/success/:id',
    path: '/success',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/release/success'),
    meta: {
      title: '操作结果',
      hideHeader: true
    }
  },
  {
    name: 'transferDebt',
    path: '/transferDebt/:id?',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/transfer/TransferDebt'),
    meta: {
      title: '选择项目', //债权求购
      requireAuth: true
    }
  },
  {
    name: 'transferSuccess',
    path: '/transferSuccess',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/transfer/TransferSuccess'),
    meta: {
      title: '操作结果'
    }
  },
  {
    name: 'targetList',
    path: '/targetList',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/target/TargetList'),
    meta: {
      title: '转让中的债权'
    }
  },
  // {
  //   name: 'purchaseList',
  //   path: '/purchaseList',
  //   component: () => import(/* webpackChunkName: 'weight1' */ '../page/seek-purchase/PurchaseList'),
  //   meta: {
  //     title: '债权求购',
  //     requireAuth: true
  //   }
  // },
  {
    name: 'projectList',
    path: '/projectList',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/project/ProjectList'),
    meta: {
      title: '选择项目', //发布转让
      requireAuth: true
    }
  },
  {
    name: 'login',
    path: '/login',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/login/Login'),
    meta: {
      canShowWithoutAuth: true,
      title: '有解债转信息平台'
    }
  },
  {
    name: 'loginAgreement',
    path: '/loginAgreement',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/login/LoginAgreement'),
    meta: {
      canShowWithoutAuth: true,
      title: '注册协议'
    }
  },
  // 确权前身份验证 start
  {
    name: 'AuthChooseOgnztion',
    path: '/AuthChooseOgnztion',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/auth/AuthChooseOgnztion'),
    meta: {
      canShowWithoutAuth: true,
      title: '选择机构'
    }
  },
  {
    name: 'AuthCheck',
    path: '/AuthCheck',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/auth/AuthCheck'),
    meta: {
      canShowWithoutAuth: true,
      title: '身份验证'
    }
  },
  {
    name: 'AuthCheckResult',
    path: '/AuthCheckResult',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/auth/AuthCheckResult'),
    meta: {
      canShowWithoutAuth: true,
      title: '身份验证'
    }
  },
  // 确权前身份验证 end
  // 确权流程 start
  {
    name: 'confirmation',
    path: '/confirmation',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/confirmation/Confirmation'),
    meta: {
      canShowWithoutAuth: true,
      title: '确权项目'
    }
  },
  {
    name: 'confirmationList',
    path: '/confirmationList/:type?/:status?',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/confirmation/ConfirmationProjectList'),
    meta: {
      canShowWithoutAuth: true,
      title: '项目确权'
    }
  },
  {
    name: 'confirmatResult',
    path: '/confirmatResult/:type?',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/confirmation/ConfirmatResult'),
    meta: {
      canShowWithoutAuth: true,
      title: '确权结果'
    }
  },
  {
    name: 'confirmationDetail',
    path: '/confirmationDetail/:id?',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/confirmation/ConfirmationProjectDetail'),
    meta: {
      canShowWithoutAuth: true,
      title: '确权详情'
    }
  },
  {
    name: 'service',
    path: '/service',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/service/Service'),
    meta: {
      canShowWithoutAuth: true,
      title: '联系客服'
    }
  },
  {
    name: 'choosePlatForConfirm',
    path: '/choosePlatForConfirm',
    component: () => import(/* webpackChunkName: 'weight1' */ '@/page/confirmation/ChoosePlatForConfirm'),
    meta: {
      canShowWithoutAuth: true,
      title: '确权项目'
    }
  },
  // 确权流程 end
  {
    name: 'subjectDetails',
    path: '/subjectDetails',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/project/SubjectDetails'),
    meta: {
      title: '债权详情'
    }
  },
  {
    name: 'subscriptionConfirmation',
    path: '/subscriptionConfirmation',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/project/SubscriptionConfirmation'),
    meta: {
      title: '认购确认',
      requireAuth: true
    }
  },
  {
    name: 'subscriptionResult',
    path: '/subscriptionResult',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/project/SubscriptionResult'),
    meta: {
      title: '认购结果',
      hideHeader: true
    }
  },
  {
    name: 'mine',
    path: '/mine',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/mine'),
    meta: {
      title: '我的',
      isshowtabbar: true,
      hideHeader: true,
      requireAuth: true
    }
  },
  {
    name: 'setting',
    path: '/setting',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/setting'),
    meta: {
      title: '我的-设置',
      requireAuth: true,
      canShowWithoutAuth: true
    }
  },
  {
    name: 'settphone',
    path: '/settphone',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/settphone'),
    meta: {
      title: '设置密码',
      requireAuth: true,
      canShowWithoutAuth: true
    }
  },
  {
    name: 'settpass',
    path: '/settpass',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/settpass'),
    meta: {
      title: '设置密码',
      requireAuth: true,
      canShowWithoutAuth: true
    }
  },
  {
    name: 'editpass',
    path: '/editpass',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/EditPassword'),
    meta: {
      title: '修改密码',
      requireAuth: true,
      canShowWithoutAuth: true
    }
  },
  {
    name: 'assets',
    path: '/assets',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/AssetsList'),
    meta: {
      title: '账户中心',
      requireAuth: true
    }
  },
  {
    name: 'mechanism',
    path: '/mechanism/:id',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/mechanism'),
    meta: {
      title: '先锋',
      hideHeader: true,
      requireAuth: true
    }
  },
  {
    name: 'project',
    path: '/project',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/ProjectList'),
    meta: {
      title: '先锋',
      requireAuth: true
    }
  },
  {
    name: 'recharge',
    path: '/recharge',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/Recharge'),
    meta: {
      title: '充值'
    }
  },
  {
    name: 'withdrawal',
    path: '/withdrawal',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/Withdrawal'),
    meta: {
      title: '提现'
    }
  },
  {
    name: 'mysubscription',
    path: '/mysubscription',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/MySubscription'),
    meta: {
      title: '我的认购'
    }
  },
  {
    name: 'transferpayments',
    path: '/transferpayments',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/Subscribe/TransferPayments'),
    meta: {
      title: '转账付款'
    }
  },
  {
    name: 'mytransfer',
    path: '/mytransfer',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/MyTransfer'),
    meta: {
      title: '我的转让'
    }
  },
  {
    name: 'myexclusive',
    path: '/myexclusive',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/MyExclusive'),
    meta: {
      title: '专属收购记录'
    }
  },
  {
    name: 'subscribeDetail',
    path: '/subscribeDetail/:id?',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/SubscribeDetail'),
    meta: {
      title: '详情'
    }
  },
  {
    name: 'success1',
    path: '/success1',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/child/success'),
    meta: {
      title: '交易成功'
    }
  },
  {
    name: 'inService',
    path: '/inService',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/child/InService'),
    meta: {
      title: '客服介入'
    }
  },
  {
    name: 'evaluation',
    path: '/questionnaire/:type',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/questionnaire/Questionnaire'),
    meta: {
      title: '风险测评',
      canShowWithoutAuth: true
    }
  },
  {
    name: 'evaluationResult',
    path: '/evaluationResult',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/questionnaire/EvaluationResult'),
    meta: {
      title: '风险测评',
      canShowWithoutAuth: true
    }
  },
  {
    name: 'iframeview',
    path: '/iframeview',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/mine/iframeView'),
    meta: {
      title: '查看合同'
    }
  },
  {
    name: 'authSign',
    path: '/authSign',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/purchase/authSign'),
    meta: {
      title: '认证签约',
      requireAuth: true
    }
  },
  {
    name: 'exclusiveDetails',
    path: '/exclusiveDetails',
    component: () => import(/* webpackChunkName: 'weight1' */ '../page/purchase/ExclusiveDetails'),
    meta: {
      title: '专属收购',
      requireAuth: true
    }
  },
]
