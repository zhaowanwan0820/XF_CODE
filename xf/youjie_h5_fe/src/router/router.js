import utils from '../util/util'
// home 首页
const Home = () => import(/* webpackChunkName: 'weight1' */ '@/page/home/Home')

// category 分类
const Category = () => import(/* webpackChunkName: 'weight1' */ '@/page/category/Category')

// product list 商品列表
const ProductList = () => import(/* webpackChunkName: 'weight1' */ '@/page/product-list/ProductList')

// search 搜索
const Search = () => import(/* webpackChunkName: 'weight1' */ '@/page/search/Search')

// 商品购买流程
// product detail 商品详情
const ProductDetail = () => import(/* webpackChunkName: 'weight1' */ '@/page/product-detail/ProductDetail')
const XiacheProductDetail = () => import(/* webpackChunkName: 'weight1' */ '@/page/product-xiache-detail/ProductDetail')
// product 大连天宝 购买须知
const ProductShouldKnownBeforeby = () =>
  import(/* webpackChunkName: 'weight7' */ '@/page/product-detail/ShouldKnowBeforeBy')

// product comments
const ProductComments = () => import(/* webpackChunkName: 'weight7' */ '@/page/product-detail/ProductComments')
// product video
const ProductVideo = () => import(/* webpackChunkName: 'weight7' */ '@/page/product-detail/ProductVideo')
// checkout 确认订单
const Checkout = () => import(/* webpackChunkName: 'weight2' */ '@/page/checkout/Checkout')
const GoodsList = () => import(/* webpackChunkName: 'weight2' */ '@/page/checkout/GoodsList')
// payment 确认付款
const Payment = () => import(/* webpackChunkName: 'weight2' */ '@/page/payment/Payment')
const PaymentNew = () => import(/* webpackChunkName: 'weight2' */ '@/page/payment/PaymentNew')
const PaySucceed = () => import(/* webpackChunkName: 'weight2' */ '@/page/payment/PaySucceed')
const PayResult = () => import(/* webpackChunkName: 'weight2' */ '@/page/payment/PayResult')

// address 地址
const AddressList = () => import(/* webpackChunkName: 'weight7' */ '@/page/address/AddressList')
const AddressManage = () => import(/* webpackChunkName: 'weight7' */ '@/page/address/AddressManage')
const AddressEdit = () => import(/* webpackChunkName: 'weight7' */ '@/page/address/AddressEdit')
// bond 债权兑换
const Bond = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/Bond')
// const BondHD = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/BondHD')
const BondDebt = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/BondDebt')
const BondRules = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/BondRules')
const BondResult = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/BondResult')
const BondHbDesc = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/HbDesc')
// const BondHDRules = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/BondHDRules')
const Recharge = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/Recharge')
const RechargeTips = () => import(/* webpackChunkName: 'weight7' */ '@/page/bond/RechargeTips')

// 品牌列表页
const Brand = () => import(/* webpackChunkName: 'weight7' */ '@/page/brand/Brand')
const BrandSearch = () => import(/* webpackChunkName: 'weight7' */ '@/page/brand/BrandSearch')

// auth 用户账户相关
const Login = () => import(/* webpackChunkName: 'weight1' */ '@/page/login/Login')
const LoginGuide = () => import(/* webpackChunkName: 'weight3' */ '@/page/login/LoginGuide')
const LoginAgreeForApp = () => import(/* webpackChunkName: 'weight3' */ '@/page/login/LoginAgreeForApp')
const Agreement = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/Agreement')
const WebPage = () => import(/* webpackChunkName: 'weight2' */ '@/page/auth/WebPage')
const AuthPage = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthPage')
const Auth = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/Auth')
const AgreementPage = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AgreementPage')
const AuthResult = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthResult')
const AuthFirstStepResult = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthFirstStepResult')
const AuthManage = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthManage')
const AuthChooseOgnztion = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthChooseOgnztion')
const AuthCheck = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthCheck')
const AuthCheckResult = () => import(/* webpackChunkName: 'weight3' */ '@/page/auth/AuthCheckResult')

// profile 用户信息
const Profile = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/Profile')
const Setting = () => import(/* webpackChunkName: 'weight3' */ '@/page/profile/Setting')
const Help = () => import(/* webpackChunkName: 'weight3' */ '@/page/profile/Help')
const ShopGuide = () => import(/* webpackChunkName: 'weight3' */ '@/page/profile/child/ShopGuide')
const Collection = () => import(/* webpackChunkName: 'weight3' */ '@/page/profile/Collection')
const About = () => import(/* webpackChunkName: 'weight3' */ '@/page/profile/About')
const DownLoadLead = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/DownLoadLead')
const Fund = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/fundList')
const Account = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/accountList')
const BankCard = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/BankCard')
const BankCardAdd = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/BankCardAdd')
const MyBankCard = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/myBankcard')
const BindingBankCard = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/BindingBankCard')
const TestPhone = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/TestPhone')
const TotalAssets = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/totalassets')
const ItemTo = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/itemto')
const SiBankCard = () => import(/* webpackChunkName: 'weight1' */ '@/page/profile/siBankCard')
// 1.5确权新增
const AssetList = () => import(/* webpackChunkName: 'weight2' */ '@/page/profile/child/AssetList')
// order 订单
const Order = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/Order')
const OrderDetail = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/OrderDetail')
const OrderTrack = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/child/OrderTrack')
const OrderTrade = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/child/OrderTrade')
const OrderComment = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/child/OrderComment')
const OrderSubmit = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/child/OrderSubmit')
const OrderPayDetail = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/OrderPayDetail')
const OrderInstalDetail = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/OrderInstalDetail')
const OrderEveryInstalDetail = () => import(/* webpackChunkName: 'weight3' */ '@/page/order/OrderEveryInstalDetail')

// friendPayOrder 好友代付订单
const FriendPayOrder = () => import(/* webpackChunkName: 'weight3' */ '@/page/friendPayOrder/Order')
const FriendPayOrderDetail = () => import(/* webpackChunkName: 'weight3' */ '@/page/friendPayOrder/OrderDetail')
const FriendPayOrderPayDetail = () => import(/* webpackChunkName: 'weight3' */ '@/page/friendPayOrder/OrderPayDetail')

// balance
const BalanceHistory = () => import(/* webpackChunkName: 'weight3' */ '@/page/balance/BalanceHistory')

// refund 退款结果
const RefundResult = () => import(/* webpackChunkName: 'weight3' */ '@/page/refund/RefundResult')

// 消息提示
// const Message = () => import(/* webpackChunkName: 'weight3' */ '@/page/message/Message')
// const MessageList = () => import( webpackChunkName: 'weight3'  '@/page/message/MessageList')

// 分销客 账户相关
const HuankeProfile = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeProfile')
const HuankeAccount = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeAccount')
const HuankeBalanceHistory = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeBalanceHistory')
const HuankeOrder = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeOrder')
const HuankeShareCheckout = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeShareCheckout')
const HuankeOrderDetail = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeOrderDetail')
const HuankeIntro = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeIntro')
const HuanKeConfirm = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeConfirm')
const HuanKeResult = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeResult')
const HuanKeOrderPayDetail = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeOrderPayDetail')
const HuankeWithdraw = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/HuankeWithdraw')
const WithdrawResult = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/WithdrawResult')

// 分销商品列表（池子）
// const ChooseProductList = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/ChooseProductList')
// 分销客小店数据看板
// const ShopDashboard = () => import(/* webpackChunkName: 'weight4' */ '@/page/huanhuanke/ShopDashboard')

// product list 分销商品列表
const MlmProductList = () => import(/* webpackChunkName: 'weight4' */ '@/page/product-dis-list/MlmProductList')
// mlm【分销客】买家流程
const MlmBuyerProductDetail = () => import(/* webpackChunkName: 'weight4' */ '@/page/product-dis-detail/BuyerDetail')
// mlm【分销客】分享流程
const MlmSharerProductDetail = () => import(/* webpackChunkName: 'weight4' */ '@/page/product-dis-detail/SharerDetail')

// 一键下车
const ExpandDebt = () => import(/* webpackChunkName: 'weight4' */ '@/page/expandDebt/ExpandDebt')

// 工单相关
//工单信息列表
// const WorkorderMessage = () => import(/* webpackChunkName: 'weight5' */ '@/page/workorder/WorkorderMessage')
// //添加工单信息
// const Addmessage = () => import(/* webpackChunkName: 'weight5' */ '@/page/workorder/Addmessage')
// //工单列表
// const WorkorderList = () => import( webpackChunkName: 'weight5'  '@/page/workorder/WorkorderList')
// //创建工单
// const AddWorkorder = () => import(/* webpackChunkName: 'weight5' */ '@/page/workorder/AddWorkorder')

// 客服页面
const Service = () => import(/* webpackChunkName: 'weight2' */ '@/page/service/Service')

// supplier 商家
const Supplier = () => import(/* webpackChunkName: 'weight2' */ '@/page/supplier/Supplier')
const SupplierInfo = () => import(/* webpackChunkName: 'weight6' */ '@/page/supplier/SupplierInfo')
const LicenseShow = () => import(/* webpackChunkName: 'weight6' */ '@/page/supplier/LicenseShow')

// friendPay 好友代付
const FriendPayIndex = () => import(/* webpackChunkName: 'weight6' */ '@/page/friendPay/Index')
const FriendPayConfirm = () => import(/* webpackChunkName: 'weight6' */ '@/page/friendPay/Confirm')
const FriendPayResult = () => import(/* webpackChunkName: 'weight6' */ '@/page/friendPay/Result')

// cart 购物车
const Cart = () => import(/* webpackChunkName: 'weight2' */ '@/page/cart/Cart')

// mystore 我的小店
// const MyStore = () => import(/* webpackChunkName: 'weight1' */ '@/page/huanhuanke/MyStore')
// // 我的小店基本信息设置
// const ShopInfo = () => import(/* webpackChunkName: 'mystore' */ '@/page/huanhuanke/ShopInfo')
// const ExitShopName = () => import(/* webpackChunkName: 'mystore' */ '@/page/huanhuanke/ExitShopName')
// const ExitShopWelcome = () => import(/* webpackChunkName: 'mystore' */ '@/page/huanhuanke/ExitShopWelcome')
// const ManageCategoryList = () => import(/* webpackChunkName: 'mystore' */ '@/page/huanhuanke/ManageCategoryList')

// 分销客店铺（买家视角
const ShopForBuyer = () => import(/* webpackChunkName: 'weight8' */ '@/page/huanhuanke/ShopForBuyer')

// const Error = () => import(/* webpackChunkName: 'weight6' */ '@/components/common/Error')

// 秒杀页面
const Seckill = () => import(/* webpackChunkName: 'weight8' */ '@/page/seckill/SeckillList')
const SeckillProduct = () => import(/*webpackChunkName: 'weight8'*/ '@/page/product-sec-detail/ProductDetail')

// 账单
const BillDetail = () => import(/* webpackChunkName: 'weight9' */ '@/page/bill/BillDetail')

// 优惠券
const CouponList = () => import(/* webpackChunkName: 'weight9' */ '@/page/coupon/CouponList')
const CouponProductList = () => import(/* webpackChunkName: 'weight9' */ '@/page/coupon/CouponProductList')
const ReceiveCoupon = () => import(/* webpackChunkName: 'weight9' */ '@/page/coupon/ReceiveCoupon')

// 积分购物新手引导
const Guide = () => import(/* webpackChunkName: 'weight100' */ '@/page/home/Guide')
// 永乐街
const PageYlj = () => import(/* webpackChunkName: 'weight100' */ '@/page/home/PageYlj')
// 烟台山水龙城
const PageSslc = () => import(/* webpackChunkName: 'weight100' */ '@/page/home/PageSslc')
//无锡高端MINI商墅项目介绍
const PageMiniss = () => import(/* webpackChunkName: 'weight100' */ '@/page/home/PageMiniss')

// 确权
const Confirmation = () => import(/* webpackChunkName: 'weight2' */ '@/page/confirmation/Confirmation')
const ConfirmationList = () => import(/* webpackChunkName: 'weight2' */ '@/page/confirmation/ConfirmationProjectList')
const ConfirmationCashList = () => import(/* webpackChunkName: 'weight2' */ '@/page/confirmation/ConfirmationCashList')
const ConfirmatResult = () => import(/* webpackChunkName: 'weight2' */ '@/page/confirmation/ConfirmatResult')
const ConfirmationDetail = () =>
  import(/* webpackChunkName: 'weight2' */ '@/page/confirmation/ConfirmationProjectDetail')
const ConfirmationForXARH = () => import(/* webpackChunkName: 'weight2' */ '@/page/confirmation/ConfirmationForXARH')

// 汽车专区页
const Carzt = () => import(/* webpackChunkName: 'weight100' */ '@/page/home/Carzt')

// 新还款计划，投票
const NewPlanVote = () => import(/* webpackChunkName: 'weight101' */ '@/page/newplan/vote')
const voteAgree1 = () => import(/* webpackChunkName: 'weight101' */ '@/page/newplan/voteAgree1')
const voteAgree2 = () => import(/* webpackChunkName: 'weight101' */ '@/page/newplan/voteAgree2')
const repaymentList = () => import(/* webpackChunkName: 'weight101' */ '@/page/newplan/RepaymentList')
const repaymentNotice = () => import(/* webpackChunkName: 'weight100' */ '@/page/newplan/RepaymentNotice')

// 设置交易密码
const TransitionPwdSet = () => import(/* webpackChunkName: 'weight101' */ '@/page/profile/TransitionPwdSet')
// 修改交易密码
const TransitionPwdChange = () => import(/* webpackChunkName: 'weight101' */ '@/page/profile/TransitionPwdChange')
//一键下车
const GiftPackage = () => import(/* webpackChunkName: 'weight10' */ '@/page/getOff/getOff')

const DebtInfo = () => import(/* webpackChunkName: 'weight10' */ '@/page/getOff/debtInfo')
const Protocol = () => import(/* webpackChunkName: 'weight10' */ '@/page/getOff/protocol')

//忘记银行卡
const ForgetBankCard = () => import(/* webpackChunkName: 'weight11' */ '@/page/auth/ForgetBankCard')
const SuccessBank = () => import(/* webpackChunkName: 'weight11' */ '@/page/auth/SuccessBack')
export default [
  {
    name: '',
    path: '/',
    redirect: '/home'
  },
  {
    name: 'home',
    path: '/home',
    component: Home,
    meta: {
      title: '首页',
      noLogin: true,
      isshowtabbar: true
    }
  },
  {
    name: 'category',
    path: '/category',
    component: Category,
    meta: {
      title: '分类',
      noLogin: true,
      isshowtabbar: true
    }
  },
  {
    name: 'products',
    path: '/products',
    component: ProductList,
    meta: {
      component_title: '商品',
      noLogin: true
    }
  },
  {
    name: 'mlmProducts',
    path: '/mlmProducts',
    component: MlmProductList,
    meta: {
      component_title: '分销返佣'
    }
  },
  // search
  {
    name: 'search',
    path: '/search',
    component: Search,
    meta: {
      component_title: '搜索',
      noLogin: true
    }
  },
  {
    name: 'login',
    path: '/login',
    component: Login,
    meta: {
      title: '登录',
      noLogin: true
    }
  },
  {
    name: 'loginGuide',
    path: '/loginGuide',
    component: LoginGuide,
    meta: {
      title: '登录引导',
      noLogin: true
    }
  },
  {
    name: 'LoginAgreeForApp',
    path: '/LoginAgreeForApp',
    component: LoginAgreeForApp,
    meta: {
      title: '登录协议',
      noLogin: true
    }
  },
  {
    name: 'agreement',
    path: '/agreement',
    component: Agreement,
    meta: {
      title: '协议',
      noLogin: true
    }
  },
  {
    name: 'webPage',
    path: '/webPage',
    component: WebPage,
    meta: {
      component_title: '帮助webview',
      noLogin: true
    }
  },
  {
    name: 'authPage',
    path: '/authPage',
    component: AuthPage,
    meta: {
      component_title: '跳转授权'
    }
  },
  {
    name: 'auth',
    path: '/auth',
    component: Auth,
    meta: {
      component_title: '授权'
    }
  },
  {
    name: 'agreementPage',
    path: '/agreementPage',
    component: AgreementPage,
    meta: {
      title: '签署协议'
    }
  },
  {
    name: 'AuthFirstStepResult',
    path: '/AuthFirstStepResult',
    component: AuthFirstStepResult,
    meta: {
      title: '签署成功'
    }
  },
  {
    name: 'AuthManage',
    path: '/AuthManage',
    component: AuthManage,
    meta: {
      title: '授权管理'
    }
  },
  {
    name: 'AuthChooseOgnztion',
    path: '/AuthChooseOgnztion',
    component: AuthChooseOgnztion,
    meta: {
      canShowWithoutAuth: true,
      title: '选择机构'
    }
  },
  {
    name: 'AuthCheck',
    path: '/AuthCheck',
    component: AuthCheck,
    meta: {
      canShowWithoutAuth: true,
      title: '身份验证'
    }
  },
  {
    name: 'AuthCheckResult',
    path: '/AuthCheckResult',
    component: AuthCheckResult,
    meta: {
      canShowWithoutAuth: true,
      title: '身份验证结果'
    }
  },
  {
    name: 'authResult',
    path: '/authResult/:result',
    component: AuthResult,
    meta: {
      title: '授权结果'
    }
  },
  // profile
  {
    name: 'profile',
    path: '/profile',
    component: Profile,
    meta: {
      component_title: '我的',
      isshowtabbar: true
    }
  },
  {
    name: 'downloadapp',
    path: '/downloadapp',
    component: DownLoadLead,
    meta: {
      title: '下载APP',
      noLogin: true
    }
  },
  {
    name: 'setting',
    path: '/setting',
    component: Setting,
    meta: {
      title: '设置'
    }
  },
  {
    name: 'help',
    path: '/help',
    component: Help,
    meta: {
      title: '帮助中心'
    }
  },
  {
    name: 'shopGuide',
    path: '/shopGuide',
    component: ShopGuide,
    meta: {
      title: '购物指南',
      noLogin: true
    }
  },
  {
    name: 'Collection',
    path: '/collection',
    component: Collection,
    meta: {
      title: '我的收藏'
    }
  },
  {
    name: 'About',
    path: '/about',
    component: About,
    meta: {
      title: '关于商城',
      noLogin: true
    }
  },
  // address
  {
    name: 'addressList',
    path: '/addressList',
    component: AddressList,
    meta: {
      title: '收货地址'
    }
  },
  {
    name: 'addressManage',
    path: '/addressManage',
    component: AddressManage,
    meta: {
      title: '管理收货地址',
      keepAlive: false
    }
  },
  {
    name: 'addressEdit',
    path: '/addressManage/addressEdit',
    component: AddressEdit,
    meta: {
      title: '修改收货地址',
      keepAlive: false
    }
  },
  // bond
  {
    name: 'bond',
    path: '/bond',
    component: Bond,
    meta: {
      title: '我的积分'
    }
  },
  {
    name: 'bondDebt',
    path: '/bondDebt',
    component: BondDebt,
    meta: {
      title: '选择债权'
    }
  },
  {
    name: 'bondRules',
    path: '/bondRules',
    component: BondRules,
    meta: {
      title: '兑换规则'
    }
  },
  {
    name: 'bondResult',
    path: '/bondResult',
    component: BondResult,
    meta: {
      title: '兑换结果页'
    }
  },
  {
    name: 'hbDesc',
    path: '/hbDesc',
    component: BondHbDesc,
    meta: {
      title: '积分说明'
    }
  },
  {
    name: 'recharge',
    path: '/recharge',
    component: Recharge,
    meta: {
      title: '积分充值'
    }
  },
  {
    name: 'rechargeTips',
    path: '/rechargeTips',
    component: RechargeTips,
    meta: {
      title: '积分充值卡密说明'
    }
  },
  {
    name: 'brand',
    path: '/brand',
    component: Brand,
    meta: {
      title: '品牌列表页'
    }
  },
  {
    name: 'brandSearch',
    path: '/brandSearch',
    component: BrandSearch,
    meta: {
      title: '品牌搜索页'
    }
  },
  // checkout
  {
    name: 'checkout',
    path: '/checkout/:isCart?',
    component: Checkout,
    meta: {
      title: '确认订单',
      keepAlive: false
    }
  },
  {
    name: 'goodsList',
    path: '/checkout/goodsList',
    component: GoodsList,
    meta: {
      title: '配送方式'
    }
  },
  // payment
  // 原订单支付页，现只给分期商品用
  {
    name: 'paymentHuan',
    path: '/paymentHuan',
    component: Payment,
    meta: {
      title: '订单支付'
    }
  },
  // 新订单支付页，只支持现金支付
  {
    name: 'payment',
    path: '/payment',
    component: PaymentNew,
    meta: {
      title: '订单支付'
    }
  },
  {
    name: 'paySucceed',
    path: '/paySucceed',
    component: PaySucceed,
    meta: {
      title: '购买成功'
    }
  },
  {
    name: 'payResult',
    path: '/payResult',
    component: PayResult,
    meta: {
      title: '购买结果'
    }
  },
  // order
  {
    name: 'order',
    path: '/order/:order?/',
    component: Order,
    meta: {
      title: '我的订单'
    }
  },
  {
    name: 'orderDetail',
    path: '/orderDetail/:orderDetail?/',
    component: OrderDetail,
    meta: {
      title: '订单详情'
    }
  },
  {
    name: 'orderPayDetail',
    path: '/orderPayDetail/:orderPayDetail?/',
    component: OrderPayDetail,
    meta: {
      title: '积分支付明细'
    }
  },
  {
    name: 'orderInstalDetail',
    path: '/orderInstalDetail/:orderInstalDetail?/',
    component: OrderInstalDetail,
    meta: {
      title: '分期明细'
    }
  },
  {
    name: 'orderEveryInstalDetail',
    path: '/orderEveryInstalDetail/:orderEveryInstalDetail?/',
    component: OrderEveryInstalDetail,
    meta: {
      title: '分期详细信息'
    }
  },
  // refund
  {
    name: 'refundResult',
    path: '/refundResult',
    component: RefundResult,
    meta: {
      title: '退款申请结果'
    }
  },
  {
    name: 'Supplier',
    path: '/Supplier/:id?/',
    component: Supplier,
    meta: {
      title: '店铺首页',
      noLogin: true
    }
  },
  {
    name: 'SupplierInfo',
    path: '/SupplierInfo/:id?/',
    component: SupplierInfo,
    meta: {
      title: '店铺信息',
      noLogin: true
    }
  },
  {
    name: 'service',
    path: '/service',
    component: Service,
    meta: {
      canShowWithoutAuth: true,
      title: '联系客服'
    }
  },
  {
    name: 'LicenseShow',
    path: '/LicenseShow',
    component: LicenseShow,
    meta: {
      title: '执照展示',
      noLogin: true
    }
  },
  // friendOrder
  {
    name: 'friendPayOrder',
    path: '/friendPayOrder/:order?/',
    component: FriendPayOrder,
    meta: {
      title: '我的订单'
    }
  },
  {
    name: 'friendPayOrderDetail',
    path: '/friendPayOrderDetail/:orderDetail?/',
    component: FriendPayOrderDetail,
    meta: {
      title: '订单详情'
    }
  },
  {
    name: 'friendPayOrderPayDetail',
    path: '/friendPayOrderPayDetail/:orderPayDetail?/',
    component: FriendPayOrderPayDetail,
    meta: {
      title: '积分支付明细'
    }
  },
  {
    name: 'orderTrack',
    path: '/orderTrack/:orderTrack?',
    component: OrderTrack,
    meta: {
      title: '订单跟踪'
    }
  },
  {
    name: 'orderTrade',
    path: '/orderTrade',
    component: OrderTrade,
    meta: {
      title: '交易成功'
    }
  },
  {
    name: 'orderComment',
    path: '/orderComment',
    component: OrderComment,
    meta: {
      title: '评价宝贝'
    }
  },
  {
    name: 'orderSubmit',
    path: '/orderSubmit',
    component: OrderSubmit,
    meta: {
      title: '评价成功'
    }
  },
  // product detail
  {
    name: 'product',
    path: '/product/:id?/:productId?',
    component: ProductDetail,
    meta: {
      title: '商品详情',
      noLogin: true
    }
  },
  // product ProductShouldKnownBefore
  {
    name: 'shouldKnownBefore',
    path: '/shouldKnownBefore',
    component: ProductShouldKnownBeforeby,
    meta: {
      title: '购买须知',
      noLogin: true
    }
  },
  // product comments
  {
    name: 'comments',
    path: '/comments/:id?',
    component: ProductComments,
    meta: {
      title: '商品评论',
      noLogin: true
    }
  },
  // product vedio
  {
    name: 'video',
    path: '/video/:src?',
    component: ProductVideo,
    meta: {
      title: '视频',
      noLogin: true
    }
  },
  // cart
  {
    name: 'cart',
    path: '/cart/:type?',
    component: Cart,
    meta: {
      component_title: '购物车',
      isshowtabbar: true,
      setIsShowTabBar: 'type'
    }
  },
  // other
  {
    name: 'balanceHistory',
    path: '/balanceHistory',
    component: BalanceHistory,
    meta: {
      title: '资金明细'
    }
  },
  {
    name: 'friendPayIndex',
    path: '/friendPayIndex/:id?',
    component: FriendPayIndex,
    meta: {
      title: '求代付'
    }
  },
  {
    name: 'friendPayConfirm',
    path: '/friendPayConfirm/:id?',
    component: FriendPayConfirm,
    meta: {
      title: '确认代付'
    }
  },
  {
    name: 'friendPayResult',
    path: '/friendPayResult/:orderId/:isSuccess?/:msg?',
    component: FriendPayResult,
    meta: {
      title: '代付结果'
    }
  },
  // product detail
  {
    name: 'buyerProduct',
    path: '/buyerProduct/:mlmId?',
    component: MlmBuyerProductDetail,
    meta: {
      title: '商品详情',
      noLogin: true
    }
  },
  // product detail
  {
    name: 'sharerDetail',
    path: '/sharerDetail/:id?',
    component: MlmSharerProductDetail,
    meta: {
      title: '商品详情',
      noLogin: true
    }
  },
  // 分销客
  {
    name: 'HuankeProfile',
    path: '/HuankeProfile',
    component: HuankeProfile,
    meta: {
      title: utils.mlmUserName
    }
  },
  {
    name: 'HuankeAccount',
    path: '/HuankeAccount',
    component: HuankeAccount,
    meta: {
      title: '账户佣金'
    }
  },
  {
    name: 'HuankeBalanceHistory',
    path: '/HuankeBalanceHistory',
    component: HuankeBalanceHistory,
    meta: {
      title: '收支明细'
    }
  },
  {
    name: 'HuankeOrder',
    path: '/HuankeOrder/:order?',
    component: HuankeOrder,
    meta: {
      title: '分销订单列表'
    }
  },
  {
    name: 'huankeShareCheckout',
    path: '/huankeShareCheckout/:id?',
    component: HuankeShareCheckout,
    meta: {
      title: '分销返佣详情'
    }
  },
  {
    name: 'HuankeOrderDetail',
    path: '/HuankeOrderDetail/:orderDetail?/',
    component: HuankeOrderDetail,
    meta: {
      title: '订单详情'
    }
  },
  {
    name: 'huankeIntro',
    path: '/huankeIntro',
    component: HuankeIntro,
    meta: {
      title: '分销返佣攻略'
    }
  },
  {
    name: 'HuanKeConfirm',
    path: '/HuanKeConfirm/:id?',
    component: HuanKeConfirm,
    meta: {
      title: '确认付款'
    }
  },
  {
    name: 'HuanKeResult',
    path: '/HuanKeResult/:id?',
    component: HuanKeResult,
    meta: {
      title: '支付成功'
    }
  },
  {
    name: 'HuanKeOrderPayDetail',
    path: '/HuanKeOrderPayDetail/:id?',
    component: HuanKeOrderPayDetail,
    meta: {
      title: '支付明细'
    }
  },
  {
    name: 'huankeWithdraw',
    path: '/huankeWithdraw',
    component: HuankeWithdraw,
    meta: {
      title: '提现'
    }
  },
  {
    name: 'shop',
    path: '/shop/:id',
    component: ShopForBuyer,
    meta: {
      title: '店铺'
    }
  },
  {
    name: 'withdrawResult',
    path: '/withdrawResult',
    component: WithdrawResult,
    meta: {
      title: '提现'
    }
  },
  {
    name: 'ExpandDebt',
    path: '/ExpandDebt/:hashid?',
    component: ExpandDebt,
    meta: {
      title: '债权兑换'
    }
  },
  {
    name: 'billDetail',
    path: '/BillDetail/:id?',
    component: BillDetail,
    meta: {
      title: '账单详情页'
    }
  },
  {
    name: 'coupon',
    path: '/coupon',
    component: CouponList,
    meta: {
      title: '我的优惠券'
    }
  },
  {
    name: 'couponProductList',
    path: '/couponProductList/:id?',
    component: CouponProductList,
    meta: {
      title: '我的优惠券'
    }
  },
  {
    name: 'receiveCoupon',
    path: '/receiveCoupon/:id?',
    component: ReceiveCoupon,
    meta: {
      title: '领取优惠券'
    }
  },
  {
    name: 'guide',
    path: '/guide',
    component: Guide,
    meta: {
      title: '新手引导',
      noLogin: true
    }
  },

  {
    name: 'confirmation',
    path: '/confirmation',
    component: Confirmation,
    meta: {
      canShowWithoutAuth: true,
      title: '确权项目'
    }
  },
  {
    name: 'fund',
    path: '/fund/:type?',
    component: Fund,
    meta: {
      title: '所有权益-网信普惠'
    }
  },
  {
    name: 'account',
    path: '/account',
    component: Account,
    meta: {
      title: '账户余额-网信普惠'
    }
  },
  {
    name: 'bankcard',
    path: '/bankcard',
    component: BankCard,
    meta: {
      title: '绑定银行卡'
    }
  },
  {
    name: 'bankcardadd',
    path: '/bankcardadd',
    component: BankCardAdd,
    meta: {
      title: '修改银行卡信息'
    }
  },
  {
    name: 'mybankcard',
    path: '/mybankcard',
    component: MyBankCard,
    meta: {
      title: '绑定银行卡'
    }
  },
  {
    name: 'bindbankcard',
    path: '/bindbankcard',
    component: BindingBankCard,
    meta: {
      title: '银行卡信息-绑定银行卡'
    }
  },
  {
    name: 'testphone',
    path: '/testphone',
    component: TestPhone,
    meta: {
      title: '银行卡信息-绑定银行卡'
    }
  },
  {
    name: 'totalassets',
    path: '/totalassets',
    component: TotalAssets,
    meta: {
      title: '总权益'
    }
  },
  {
    name: 'itemto',
    path: '/itemto',
    component: ItemTo,
    meta: {
      title: '网信'
    }
  },
  {
    name: 'sibank',
    path: '/sibank',
    component: SiBankCard,
    meta: {
      title: '网信'
    }
  },
  {
    name: 'confirmationList',
    path: '/confirmationList/:type?/:status?',
    component: ConfirmationList,
    meta: {
      canShowWithoutAuth: true,
      title: '标的确权'
    }
  },
  {
    name: 'confirmationForXARH',
    path: '/confirmationForXARH',
    component: ConfirmationForXARH,
    meta: {
      canShowWithoutAuth: true,
      title: '项目确权'
    }
  },
  {
    name: 'confirmationCashList',
    path: '/confirmationCashList/:type?',
    component: ConfirmationCashList,
    meta: {
      canShowWithoutAuth: true,
      title: '确权'
    }
  },
  {
    name: 'confirmatResult',
    path: '/confirmatResult/:type?',
    component: ConfirmatResult,
    meta: {
      canShowWithoutAuth: true,
      title: '确权结果'
    }
  },
  {
    name: 'confirmationDetail',
    path: '/confirmationDetail/:type?/:id?',
    component: ConfirmationDetail,
    meta: {
      canShowWithoutAuth: true,
      title: '确权详情'
    }
  },
  {
    name: 'pageYlj',
    path: '/pageYlj',
    component: PageYlj,
    meta: {
      title: '康桥永乐街'
    }
  },
  {
    name: 'pageSslc',
    path: '/pageSslc',
    component: PageSslc,
    meta: {
      title: '烟台山水龙城'
    }
  },
  {
    name: 'pageMiniss',
    path: '/pageMiniss',
    component: PageMiniss,
    meta: {
      title: '无锡高端MINI商墅'
    }
  },
  {
    name: 'newPlanVote',
    path: '/newPlanVote',
    component: NewPlanVote,
    meta: {
      title: '新还款计划'
    }
  },
  {
    name: 'AssetList',
    path: '/AssetList',
    component: AssetList,
    meta: {
      title: '资产收支明细'
    }
  },
  {
    name: 'voteAgree1',
    path: '/voteAgree1',
    component: voteAgree1,
    meta: {
      title: '新还款计划-兑付协议'
    }
  },
  {
    name: 'voteAgree2',
    path: '/voteAgree2',
    component: voteAgree2,
    meta: {
      title: '新还款计划-兑付协议'
    }
  },
  {
    name: 'repaymentList',
    path: '/repaymentList',
    component: repaymentList,
    meta: {
      title: '还款兑付'
    }
  },
  {
    name: 'repaymentNotice',
    path: '/repaymentNotice',
    component: repaymentNotice,
    meta: {
      title: '还款兑付通知'
    }
  },
  {
    name: 'transPwdSet',
    path: '/transPwdSet',
    component: TransitionPwdSet,
    meta: {
      title: '设置交易密码'
    }
  },
  {
    name: 'transPwdChange',
    path: '/transPwdChange',
    component: TransitionPwdChange,
    meta: {
      title: '修改交易密码'
    }
  },
  {
    name: 'carZt',
    path: '/carZt',
    component: Carzt,
    meta: {
      title: '汽车专区'
    }
  },
  {
    name: 'promotion001',
    path: '/promotion001',
    component: GiftPackage,
    meta: {
      title: '有解'
    }
  },
  {
    name: 'expandProduct',
    path: '/expandProduct/:id?/:productId?',
    component: XiacheProductDetail,
    meta: {
      title: '商品'
    }
  },
  {
    name: 'debtinfo',
    path: '/debtinfo',
    component: DebtInfo,
    meta: {
      title: '债权明细'
    }
  },
  {
    name: 'protocol',
    path: '/protocol',
    component: Protocol,
    meta: {
      title: '协议'
    }
  },
  {
    name: 'forgetbankcard',
    path: '/forgetbankcard',
    component: ForgetBankCard,
    meta: {
      title: '银行卡信息'
    }
  },
  {
    name: 'successbank',
    path: '/successbank',
    component: SuccessBank,
    meta: {
      title: '提交成功'
    }
  },
  {
    name: 'Seckill',
    path: '/Seckill',
    component: Seckill,
    meta: {
      title: '秒杀列表'
    }
  },
  {
    name: 'SeckillProduct',
    path: '/SeckillProduct',
    component: SeckillProduct,
    meta: {
      title: '秒杀详情'
    }
  },
  {
    path: '*',
    name: 'error',
    redirect: '/home'
    // meta: {
    //   title: '找不到页面'
    // }
  }
]
