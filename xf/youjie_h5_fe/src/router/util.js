import utils from '../util/util'

export const tabBarRouteName = ['home', 'category', 'myStore', 'cart', 'profile']

// 打开app原生页面时 参数转换
export const getQuery = (url, page) => {
  // 商品列表，H5的get参数名与App不同，进行替换
  let querys = url.split('?')[1]
  if ('products' == page) {
    querys = querys.replace(/(category|sort_key|keywords|tags_id|is_newbie|brand|admin_order)/gi, (match, p) => {
      // https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/String/replace#%E6%8C%87%E5%AE%9A%E4%B8%80%E4%B8%AA%E5%87%BD%E6%95%B0%E4%BD%9C%E4%B8%BA%E5%8F%82%E6%95%B0
      return {
        category: 'catId',
        sort_key: 'sortKey',
        keywords: 'keyword',
        tags_id: 'tagsId',
        is_newbie: 'isNewbie',
        brand: 'brand',
        admin_order: 'adminOrder'
      }[p]
    })
  }

  return querys ? '?' + querys : ''
}

// 首页一级路由（Tabbar）对应的app schema
export const getTabSchema = to => {
  const schemaMap = {
    home: 'yjmall://home',
    category: 'yjmall://category',
    myStore: 'yjmall://myStore',
    // cart: encodeURIComponent('yjmall://cart?alone=' + (to.meta.isshowtabbar ? 1 : 0)),
    profile: 'yjmall://myCenter'
  }
  return schemaMap[to.name]
}
