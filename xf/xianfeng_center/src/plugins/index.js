/**
 * My plugins
 */

import axios from './axios'
import title from './title'
import services from './services'
import nprogress from './nprogress'
import authorize from './authorize'
import decimal from './decimal'

// vant 按需引入
import {
  NavBar,
  Dialog,
  Toast,
  Button,
  Form,
  Field,
  List,
  Row,
  Col,
  Divider,
  DropdownMenu,
  DropdownItem,
  PasswordInput,
  NumberKeyboard,
  Checkbox,
  CheckboxGroup,
} from 'vant'
import 'vant/lib/button/style'
import 'vant/lib/dialog/style'
import 'vant/lib/form/style'
import 'vant/lib/field/style'
import 'vant/lib/list/style'
import 'vant/lib/row/style'
import 'vant/lib/col/style'
import 'vant/lib/divider/style'
import 'vant/lib/dropdown-menu/style'
import 'vant/lib/dropdown-item/style'

export default {
  install(Vue) {
    axios(Vue)
    title(Vue)
    services(Vue)
    nprogress(Vue)
    authorize(Vue)
    decimal(Vue)

    // 使用 vant 组件: 按需引入形式

    Vue.use(NavBar)
      .use(Dialog)
      .use(Toast)
      .use(Button)
      .use(Form)
      .use(Field)
      .use(List)
      .use(Row)
      .use(Col)
      .use(Divider)
      .use(DropdownMenu)
      .use(DropdownItem)
      .use(PasswordInput)
      .use(NumberKeyboard)
      .use(Checkbox)
      .use(CheckboxGroup)
  },
}
