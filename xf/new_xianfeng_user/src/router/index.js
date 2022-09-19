import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

//首页
const home = () => import(/* webpackChunkName: 'weight2' */ '@/page/home/home')
// 签约完成
const claimsComplete = () => import(/* webpackChunkName: 'weight2' */ '@/page/claims/complete')
// 签约处理中
const claimsIng = () => import(/* webpackChunkName: 'weight2' */ '@/page/claims/ing')
// 签约协议
const claimsAgreement = () => import(/* webpackChunkName: 'weight2' */ '@/page/claims/agreement')
// 签约补充协议
const claimsSupplement = () => import(/* webpackChunkName: 'weight2' */ '@/page/claims/supplement')
// 委托授权协议
const TrustAgreement = () => import(/* webpackChunkName: 'TrustAgreement' */ '@/page/Trust/Agreement')

//登录
const login = () => import(/* webpackChunkName: 'weight2' */ '@/page/login/login')
//借款人登录
const borrowerLogin = () => import(/* webpackChunkName: 'weight2' */ '@/page/login/BorrowerLogin')
const borrowerVoucher = () => import(/* webpackChunkName: 'weight2' */ '@/page/propertyCompose/BorrowerVoucherDetail')

//个人中心
const user = () => import(/* webpackChunkName: 'weight2' */ '@/page/user/user')

//安全设置
const security = () => import(/* webpackChunkName: 'weight2' */ '@/page/security/Security')
//设置交易密码
const setPassWord = () => import(/* webpackChunkName: 'weight2' */ '@/page/security/SetPassWord')
//找回密码
const findPassWord = () => import(/* webpackChunkName: 'weight2' */ '@/page/security/FindPassWord')
//校验交易密码
const checkTradersPwd = () => import(/* webpackChunkName: 'weight2' */ '@/page/security/CheckTradersPwd')

//资产构成
const propertyCompose = () => import(/* webpackChunkName: 'weight2' */ '@/page/propertyCompose/PropertyCompose')
//在途详情
const propertyComposeDetail = () => import(/* webpackChunkName: 'weight2' */ '@/page/propertyCompose/PropertyComposeDetail')
//出借详情
const lendingDetails = () => import(/* webpackChunkName: 'weight2' */ '@/page/propertyCompose/LendingDetails')
//合同和协议列表
const agreementList = () => import(/* webpackChunkName: 'weight2' */ '@/page/propertyCompose/AgreementList')
//服务协议
const serviceAgreement = () => import(/* webpackChunkName: 'weight2' */ '@/page/serviceAgreement/ServiceAgreement')
//合同及交易凭证
const voucherDetail = () => import(/* webpackChunkName: 'weight2' */ '@/page/propertyCompose/VoucherDetail')


const ExchangeList = () => import(/* webpackChunkName: 'weight2' */ '@/page/Exchange/index')

// 消息中心
const message = () => import(/* webpackChunkName: 'weight2' */ '@/page/message/message')
const messageList = () => import(/* webpackChunkName: 'weight2' */ '@/page/message/messageList')
const messageDetail = () => import(/* webpackChunkName: 'weight2' */ '@/page/message/messageDetail')
// 咨询反馈
const feedBackList = () => import(/* webpackChunkName: 'weight2' */ '@/page/message/feedBack')
const feedBackDetail = () => import(/* webpackChunkName: 'weight2' */ '@/page/message/feedBackDetail')
//接口报错-无网络
const noNetWork = () => import(/* webpackChunkName: 'weight2' */ '@/page/noNetWork/NoNetWork')
//联系客服
const service = () => import(/* webpackChunkName: 'weight2' */ '@/page/service/Service')
//修改手机号（不可用）
const editPhoneNum = () => import('@/page/editPhoneNum')
//修改手机（不可用） 修改成功
const editSuccess = () => import('@/page/editPhoneNum/editSuccess')
//修改手机（可用） 修改成功
const editPhoneSuccess = () => import('@/page/security/EditPhoneSuccess')
//修改手机（不可用） 帮助
const help = () => import('@/page/editPhoneNum/help')
//安全设置手机号首页
const editPhoneIndex = () => import('@/page/security/showCurrentPhone')
//安全设置手机号修改页
const editPhone = () => import('@/page/security/EditPhone')
//多个业务修改手机号功能列表
const editPhoneList = () => import('@/page/login/AboutDisableLogin')
//交易所申请注册
const jysAuthReg = () => import('@/page/login/JYSAuthReg')
// 委托授权资料补充
const supplement = () => import('@/page/supplement')

export default new Router({
  // mode: 'history',
  routes: [
    {
      name: 'home',
      path: '/',
      component: home,
      meta: {
        component_title: '首页',
      }
    },
    {
      name: 'claimsComplete',
      path: '/claims/complete',
      component: claimsComplete,
      meta: {
        component_title: '签署协议',
      }
    },
    {
      name: 'claimsIng',
      path: '/claims/ing',
      component: claimsIng,
      meta: {
        component_title: '签署协议',
      }
    },
    {
      name: 'claimsAgreement',
      path: '/claims/agreement',
      component: claimsAgreement,
      meta: {
        component_title: '签署协议',
      }
    },
    {
      name: 'claimsSupplement',
      path: '/claims/supplement',
      component: claimsSupplement,
      meta: {
        component_title: '补充协议',
      }
    },
    {
      name: 'supplement',
      path: '/supplement',
      component: supplement,
      meta: {
        component_title: '委托授权资料补充',
      }
    },
    {
      name: 'TrustAgreement',
      path: '/trust/agreement',
      component: TrustAgreement,
      meta: {
        component_title: '委托授权协议',
      }
    },
    {
      name: 'borrowerLogin',
      path: '/borrowerLogin',
      component: borrowerLogin,
      meta: {
        component_title: '借款人登录',
      }
    },
    {
      name: 'borrowerVoucher',
      path: '/borrowerVoucher',
      component: borrowerVoucher,
      meta: {
        component_title: '借款人还款凭证',
      }
    },

    {
      name: 'login',
      path: '/login',
      component: login,
      meta: {
        component_title: '登录',
      }
    },
    {
      name: 'user',
      path: '/user',
      component: user,
      meta: {
        component_title: '个人信息',
      }
    },
    {
      name: 'security',
      path: '/security',
      component: security,
      meta: {
        component_title: '安全设置',
      },
    },
    {
      name: 'setPassWord',
      path: '/setPassWord',
      component: setPassWord,
      meta: {
        component_title: '设置交易密码',
      },
    },
    {
      name: 'findPassWord',
      path: '/findPassWord',
      component: findPassWord,
      meta: {
        component_title: '找回密码',
      },
    },
    {
      name: 'serviceAgreement',
      path: '/serviceAgreement',
      component: serviceAgreement,
      meta: {
        component_title: '签订服务协议',
      },
    },
    {
      name: 'propertyCompose',
      path: '/propertyCompose',
      component: propertyCompose,
      meta: {
        component_title: '资产构成',
      },
    },
    {
      name: 'propertyComposeDetail',
      path: '/propertyComposeDetail',
      component: propertyComposeDetail,
      meta: {
        component_title: '资产构成详情',
      },
    },
    {
      name: 'voucherDetail',
      path: '/voucherDetail',
      component: voucherDetail,
      meta: {
        component_title: '合同及交易凭证',
      },
    },
    {
      name: 'ExchangeList',
      path: '/ExchangeList',
      component: ExchangeList,
      meta: {
        component_title: '积分兑换记录',
      },
    },
    {
      name: 'message',
      path: '/message',
      component: message,
      meta: {
        component_title: '消息中心',
      },
    },
    {
      name: 'messageList',
      path: '/messageList',
      component: messageList,
      meta: {
        component_title: '消息列表',
      },
    },
    {
      name: 'messageDetail',
      path: '/messageDetail',
      component: messageDetail,
      meta: {
        component_title: '消息详情',
      },
    },
    {
      name: 'feedBackList',
      path: '/feedBackList',
      component: feedBackList,
      meta: {
        component_title: '反馈列表',
      }
    },
    {
      name: 'feedBackDetail',
      path: '/feedBackDetail',
      component: feedBackDetail,
      meta: {
        component_title: '反馈列表详情',
      }
    },
    {
      name: 'noNetWork',
      path: '/noNetWork',
      component: noNetWork,
      meta: {
        component_title: '无网络',
      }
    },
    {
      name: 'checkTradersPwd',
      path: '/checkTradersPwd',
      component: checkTradersPwd,
      meta: {
        component_title: '校验交易密码',
      }
    },
    {
      name: 'lendingDetails',
      path: '/lendingDetails',
      component: lendingDetails,
      meta: {
        component_title: '出借详情',
      }
    },
    {
      name: 'agreementList',
      path: '/agreementList',
      component: agreementList,
      meta: {
        component_title: '合同和协议列表',
      }
    },
    {
      name: 'service',
      path: '/service',
      component: service,
      meta: {
        component_title: '联系客服',
      }
    },
    {
      name: 'editPhoneNum',
      path: '/editPhoneNum',
      component: editPhoneNum,
      meta: {
        component_title: '修改绑定手机',
      }
    },
    {
      name: 'editSuccess',
      path: '/editSuccess',
      component: editSuccess,
      meta: {
        component_title: '修改绑定手机',
      }
    },
    {
      name: 'help',
      path: '/help',
      component: help,
      meta: {
        component_title: '帮助',
      }
    }, {
      name: 'editPhoneIndex',
      path: '/editPhoneIndex',
      component: editPhoneIndex,
      meta: {
        component_title: '手机号',
      }
    },
    {
      name: 'editPhone',
      path: '/editPhone',
      component: editPhone,
      meta: {
        component_title: '修改绑定手机',
      }
    }, {
      name: 'editPhoneSuccess',
      path: '/editPhoneSuccess',
      component: editPhoneSuccess,
      meta: {
        component_title: '修改绑定手机',
      }
    },
    {
      name: 'editPhoneList',
      path: '/editPhoneList',
      component: editPhoneList,
      meta: {
        component_title: '功能列表',
      }
    },
    {
      name: 'jysAuthReg',
      path: '/jysAuthReg',
      component: jysAuthReg,
      meta: {
        component_title: '功能列表',
      }
    },
  ]
})
