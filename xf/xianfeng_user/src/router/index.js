import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

//首页
const home = () => import(/* webpackChunkName: 'weight2' */ '@/page/home/home')

//登录
const login = () => import(/* webpackChunkName: 'weight2' */ '@/page/login/login')

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
export default new Router({
  // mode: 'history',
  routes: [
    {
      name: 'home',
      path: '/',
      component:home ,
      meta: {
        component_title: '首页',
      }
    },
    {
      name: 'login',
      path: '/login',
      component:login ,
      meta: {
        component_title: '登录',
      }
    },
    {
      name: 'user',
      path: '/user',
      component:user ,
      meta: {
        component_title: '个人信息',
      }
    },
    {
      name: 'security',
      path: '/security',
      component:security ,
      meta: {
        component_title: '安全设置',
      },
    },
    {
      name: 'setPassWord',
      path: '/setPassWord',
      component:setPassWord ,
      meta: {
        component_title: '设置交易密码',
      },
    },
    {
      name: 'findPassWord',
      path: '/findPassWord',
      component:findPassWord ,
      meta: {
        component_title: '找回密码',
      },
    },
    {
      name: 'serviceAgreement',
      path: '/serviceAgreement',
      component:serviceAgreement ,
      meta: {
        component_title: '签订服务协议',
      },
    },
    {
      name: 'propertyCompose',
      path: '/propertyCompose',
      component:propertyCompose ,
      meta: {
        component_title: '资产构成',
      },
    },
    {
      name: 'propertyComposeDetail',
      path: '/propertyComposeDetail',
      component:propertyComposeDetail ,
      meta: {
        component_title: '资产构成详情',
      },
    },
    {
      name: 'ExchangeList',
      path: '/ExchangeList',
      component:ExchangeList ,
      meta: {
        component_title: '积分兑换记录',
      },
    },
    {
      name: 'message',
      path: '/message',
      component:message ,
      meta: {
        component_title: '消息中心',
      },
    },
    {
      name: 'messageList',
      path: '/messageList',
      component:messageList ,
      meta: {
        component_title: '消息列表',
      },
    },
    {
      name: 'messageDetail',
      path: '/messageDetail',
      component:messageDetail ,
      meta: {
        component_title: '消息详情',
      },
    },
    {
      name: 'feedBackList',
      path: '/feedBackList',
      component:feedBackList ,
      meta: {
        component_title: '反馈列表',
      }
    },
    {
      name: 'feedBackDetail',
      path: '/feedBackDetail',
      component:feedBackDetail ,
      meta: {
        component_title: '反馈列表详情',
      }
    },
    {
      name: 'noNetWork',
      path: '/noNetWork',
      component:noNetWork ,
      meta: {
        component_title: '无网络',
      }
    },
    {
      name: 'checkTradersPwd',
      path: '/checkTradersPwd',
      component:checkTradersPwd ,
      meta: {
        component_title: '校验交易密码',
      }
    },
    {
      name: 'lendingDetails',
      path: '/lendingDetails',
      component:lendingDetails ,
      meta: {
        component_title: '出借详情',
      }
    },
    {
      name: 'agreementList',
      path: '/agreementList',
      component:agreementList ,
      meta: {
        component_title: '合同和协议列表',
      }
    },
    {
      name: 'service',
      path: '/service',
      component:service ,
      meta: {
        component_title: '联系客服',
      }
    }
  ]
})
