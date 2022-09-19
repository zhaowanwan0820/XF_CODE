import { ENUM } from '../../const/enum'

// 排序键
export const SORTKEY = [
  {
    key: ENUM.SORT_KEY.DATE,
    name: '新品',
    value: ENUM.SORT_VALUE.DESC,
    id: 0
  },
  {
    key: ENUM.SORT_KEY.SUPPLIERS,
    name: '销量',
    value: ENUM.SORT_VALUE.DESC,
    id: 1
  },
  {
    key: ENUM.SORT_KEY.PRICE,
    name: '价格',
    isTurn: true,
    value: ENUM.SORT_VALUE.DESC,
    id: 2
  }
]
