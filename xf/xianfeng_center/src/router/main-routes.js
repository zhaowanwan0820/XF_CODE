export default [
  {
    name: 'home',
    path: '/',
    meta: {
      title: '首页',
      // requireAuth: true,
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/home/index'),
  },
  {
    name: 'auth',
    path: '/oAuth',
    meta: {
      title: '授权',
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/entry/auth'),
  },
  {
    name: 'agreement',
    path: '/serviceAgreement',
    meta: {
      title: '注册协议及隐私保护政策',
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/exchange/agreement'),
  },
  {
    name: 'debt',
    path: '/debtExchange',
    meta: {
      title: '选择债权',
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/exchange/debt'),
  },
  {
    name: 'debt_agreement',
    path: '/debt_agreement',
    meta: {
      title: '',
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/exchange/debt_agreement'),
  },
  {
    name: 'debt_agreement_v2',
    path: '/debt_agreement_v2',
    meta: {
      title: '',
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/exchange/debt_agreement_v2'),
  },
]
