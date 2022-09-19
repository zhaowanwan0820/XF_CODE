import { ENUM } from '../../const/enum'

// 排序键
export const SORTKEY = [
  {
    key: ENUM.SORT_KEY.DEFAULT,
    name: '全部商品',
    value: ENUM.SORT_VALUE.DESC,
    isMore: true,
    id: 0,
    child: [
      {
        key: ENUM.SORT_KEY.DEFAULT,
        name: '全部商品',
        isMore: false,
        value: ENUM.SORT_VALUE.DESC,
        id: 1
      },
      // {
      //   key: ENUM.SORT_KEY.CREDIT,
      //   name: '好评率',
      //   isMore: false,
      //   value: ENUM.SORT_VALUE.DESC,
      //   id: 4
      // },
      {
        key: ENUM.SORT_KEY.SUPPLIERS_MONTH,
        name: '销量',
        isMore: true,
        value: ENUM.SORT_VALUE.DESC,
        id: 10
      },
      {
        key: ENUM.SORT_KEY.SUPPLIERS_DAY,
        name: '24h热销',
        isMore: false,
        value: ENUM.SORT_VALUE.DESC,
        id: 11
      },
      {
        key: ENUM.SORT_KEY.SUPPLIERS_IN_DEBT,
        name: '专区商品',
        isMore: false,
        value: ENUM.SORT_VALUE.DESC,
        id: 7
      }
    ]
  },
  {
    key: ENUM.SORT_KEY.PRICE,
    name: '价格排序',
    value: ENUM.SORT_VALUE.ASC,
    isTurn: true,
    childId: 0,
    id: 8,
    child: [
      {
        key: ENUM.SORT_KEY.PRICE,
        name: '价格从低到高',
        isMore: false,
        value: ENUM.SORT_VALUE.ASC,
        id: 2
      },
      {
        key: ENUM.SORT_KEY.PRICE,
        name: '价格从高到底',
        isMore: false,
        value: ENUM.SORT_VALUE.DESC,
        id: 3
      }
    ]
  },
  {
    key: ENUM.SORT_KEY.DATE,
    name: '新品首发',
    isMore: false,
    value: ENUM.SORT_VALUE.DESC,
    id: 6
  }
  // {
  //   key: ENUM.SORT_KEY.SALE,
  //   name: '销量排序',
  //   isMore: false,
  //   value: ENUM.SORT_VALUE.DESC,
  //   id: 5
  // }
]
