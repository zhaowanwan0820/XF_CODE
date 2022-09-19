import decimal from 'decimal.js'

export  const dec = {
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
    // 两数相成 x * y
    // 返回 floor
    mul: (x, y) => {
        return +decimal.mul(x, y)
    },
    div: (x, y) => {
        return +decimal.div(x, y)
    },
    // 比较 x 与 y
    // 返回 >:1 =:0 <:-1
    cmp: (x, y) => {
      return new decimal(x).cmp(y)
    },
  }


