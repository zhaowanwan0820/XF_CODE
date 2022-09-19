/* eslint-disable new-cap */
import { decimal } from '@/utils'

export default Vue => {
  // alias
  const dec = {
    // 两数相加 x + y
    // 返回 floor
    add: (x, y) => {
      return +decimal.add(x, y)
    },
    // 两数相减 x - y
    // 返回 floor
    sub: (x, y) => {
      return +decimal.sub(x, y)
    },
    // 比较 x 与 y
    // 返回 >:1 =:0 <:-1
    cmp: (x, y) => {
      return new decimal(x).cmp(y)
    },
  }

  // mount the decimal to Vue
  Object.defineProperties(Vue, {
    dec: { get: () => dec },
  })

  // mount the decimal to Vue component instance
  Object.defineProperties(Vue.prototype, {
    $dec: { get: () => dec },
  })
}
