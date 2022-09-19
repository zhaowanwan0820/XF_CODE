import { fetchEndpoint } from '../server/network'
import utils from '../util/util'

// 为你推荐
export const getRecommendList = params => {
  return new Promise((resolve, reject) => {
    fetchEndpoint('/hh/hh.product.foryou', 'POST', {
      ...params
    }).then(
      res => {
        utils.splitMoneyLint(res.list || res)
        resolve(res)
      },
      err => {
        reject(err)
      }
    )
  })
}
