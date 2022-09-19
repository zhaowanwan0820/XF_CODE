import Vue from 'vue'
import { sendBuryingPointInfo } from '../api/buryingPoint'

Vue.directive('stat', {
  bind(el, binding) {
    // if (process.env.NODE_ENV === 'production') {
    //   let url = document.location.href,
    //     referrer = document.referrer
    //   // 存放content业务代码，由后台整合至content属性下
    //   let params = {
    //     click_position: binding.value.id, // 点击位置
    //     url: url,
    //     referer: referrer
    //   }
    //   el.addEventListener('click', () => {
    //     sendBuryingPointInfo(params)
    //   })
    // }
  }
})
