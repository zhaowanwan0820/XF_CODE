// business routes
import mainRoutes from './main-routes'
// demo routes
// import demoRoutes from './demo-routes'

/**
 * 路由表配置
 */
export default [
  // ## login page
  {
    name: 'login',
    path: '/login',
    meta: {
      title: '授权登录',
      hideHeaderBack: true,
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/login/index'),
  },
  // ## main page
  {
    path: '/',
    component: () => import(/* webpackChunkName: 'common' */ '@/views/layout'),
    children: mainRoutes,
  },
  // ## not found page
  {
    name: 'not-found',
    path: '*',
    meta: {
      title: '找不到页面',
    },
    component: () => import(/* webpackChunkName: 'common' */ '@/views/error'),
  },
]
