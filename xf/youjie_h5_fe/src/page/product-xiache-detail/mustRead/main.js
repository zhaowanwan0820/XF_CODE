import store from '../../../store/index'
import { rules } from './config'

export const checkRead = productDetailInfo => {
  let ret = { check: true }
  const res = isShouldRead(productDetailInfo)
  if (res['should'] && !isHasRead(res['rule']['keyInDb'])) {
    ret.check = false
    ret.rule = res['rule']
  }
  return ret
}

export const isShouldRead = productDetailInfo => {
  let ret = { should: false }
  const finded = rules.find(item => {
    return item.rule(productDetailInfo)
  })
  if (finded) {
    ret.should = true
    ret.rule = finded
  }

  return ret
}

const isHasRead = keyInDb => {
  const user = store.getters.getUser
  const read_marker = user.read_marker || []
  return read_marker.indexOf(keyInDb) !== -1
}
