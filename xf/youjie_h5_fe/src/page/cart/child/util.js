export const checkGoodsAmoutValid = (item, amount) => {
  let ret = {
    valid: true,
    msg: '',
    amount: amount
  }

  // 提示信息
  let noticeStr = ''
  // 有限购时 限购剩余数
  let limitNum = 0
  if (item.goods.only_purchase) {
    limitNum =
      item.goods.only_purchase - item.goods.now_purchase <= 0 ? 1 : item.goods.only_purchase - item.goods.now_purchase
  }
  // 数量超出 库存时
  if (amount > item.attr_stock) {
    ret.valid = false
    ret.msg = `购买数量超出商品库存 ${item.attr_stock}件`
    ret.amount = item.attr_stock
    // 是否限购
    if (item.goods.only_purchase && amount > limitNum) {
      ret.msg = `购买数量超出商品限购数量，您还可购买${limitNum}件该商品`
      ret.amount = limitNum
    }
  } else {
    // 是否限购
    if (item.goods.only_purchase && amount > limitNum) {
      ret.valid = false
      ret.msg = `购买数量超出商品限购数量，您还可购买${limitNum}件该商品`
      ret.amount = limitNum
    }
  }

  return ret
}
