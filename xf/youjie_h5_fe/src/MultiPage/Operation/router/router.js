// Error
const ErrorPage = () => import(/* webpackChunkName: 'bundleOperation' */ '../page/Error')
// 运营静态活动页
const OperationPage = () => import(/* webpackChunkName: 'bundleOperation' */ '../page/iXiu/Index')

export default [
  {
    name: 'operationPage',
    path: '/operationPage/:id',
    component: OperationPage,
    meta: {
      title: '运营页'
    }
  },
  {
    // 进入商城
    name: 'mall',
    path: '/mall',
    beforeEnter: (to, from, next) => {
      const mallPath = 'production' === process.env.NODE_ENV ? '/h5' : '/index'
      window.location.href = mallPath
    }
  },
  {
    path: '*',
    name: 'error',
    component: ErrorPage
  }
]
