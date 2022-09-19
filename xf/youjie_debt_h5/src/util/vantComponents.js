import { Toast } from 'vant'

// 可同时存在多个toast
Toast.allowMultiple()
let Toast_loading = ''
// 自定义小loading
export const $loading = {
  open: (message = '加载中...') => {
    if(Toast_loading){
      Toast_loading.clear()
    }
    Toast_loading = Toast.loading({
      duration: 0,
      forbidClick: true,
      message
    })
  },
  close: () => {
    Toast_loading.clear()
  }
}
