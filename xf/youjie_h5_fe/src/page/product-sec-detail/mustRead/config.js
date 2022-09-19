import { templates } from './templates'

export const rules = [
  {
    id: 1, // 唯一标识
    rulesTitle: '大连天宝购买须知', // 需要阅读的须知名称
    keyInDb: 1, // 保存【已读】时给api接口传递的当前规则标识；store > user > read_marker中有该数值代表是否已读
    template: templates[1], // 当前规则说明内容
    tempTitle: '购买须知', // 规则title
    rule: productInfo => {
      // 商品 校验规则
      return productInfo.supplier && productInfo.supplier.sn == 'SA000240828' ? true : false
    }
  },
  {
    id: 2,
    rulesTitle: '汽车服务卡购买须知',
    keyInDb: 2,
    template: templates[2],
    tempTitle: '购买须知',
    rule: productInfo => {
      return [6976, 6977, 6920].includes(Number(productInfo.id))
    }
  },
  {
    id: 3,
    rulesTitle: '塔城酒业购买须知',
    keyInDb: 3,
    template: templates[3],
    tempTitle: '购买须知',
    rule: productInfo => {
      return productInfo.supplier && productInfo.supplier.sn == 'SA000240884' ? true : false
    }
  },
  {
    id: 4,
    rulesTitle: '明仕科技债转债份额划扣购买须知',
    keyInDb: 4,
    template: templates[4],
    tempTitle: '购买须知',
    rule: productInfo => {
      return [7541].includes(Number(productInfo.id))
    }
  }
  // {
  //   id: 6,
  //   rulesTitle: '分期租购协议',
  //   keyInDb: 6,
  //   template: templates[6],
  //   tempTitle: '分期租购协议',
  //   rule: productInfo => {
  //     return productInfo.instalment && productInfo.instalment.length > 0
  //   }
  // }
]
