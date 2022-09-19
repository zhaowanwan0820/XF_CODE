import Vue from 'vue'

import HeaderView from './HeaderView'
import HeaderItem from './HeaderItem'

import InfoTextItem from './InfoTextItem'
import InfoRadioItem from './InfoRadioItem'
import InfoRadioList from './InfoRadioList'
import InfoToggleItem from './InfoToggleItem'

import FlowRadioItem from './FlowRadioItem'
import FlowRadioList from './FlowRadioList'

import FormInputItem from './FormInputItem'
import FormTextItem from './FormTextItem'

import TopList from './TopList'
import BaseList from './BaseList'
import EmptyItem from './EmptyItem'
import Webview from './Webview'

import Button from './Button'
import CountdownButton from './CountdownButton'

import ImageCropper from './ImageCropper'

import PopupPhotoShare from './PopupPhotoShare'

import PopupShareFriendPay from './PopupShareFriendPay'
import PopupMlmShare from './PopupMlmShare'

import ActivityIconOnProduct from './ActivityIconOnProduct'

import ServeIcon from './ServeIcon'

// 全局注册组件
Vue.component(HeaderView.name, HeaderView)
Vue.component(HeaderItem.name, HeaderItem)

Vue.component(InfoTextItem.name, InfoTextItem)
Vue.component(InfoRadioItem.name, InfoRadioItem)
Vue.component(InfoRadioList.name, InfoRadioList)
Vue.component(InfoToggleItem.name, InfoToggleItem)

Vue.component(FlowRadioItem.name, FlowRadioItem)
Vue.component(FlowRadioList.name, FlowRadioList)

Vue.component(FormInputItem.name, FormInputItem)
Vue.component(FormTextItem.name, FormTextItem)

Vue.component(TopList.name, TopList)
Vue.component(BaseList.name, BaseList)
Vue.component(EmptyItem.name, EmptyItem)

Vue.component(Webview.name, Webview)

Vue.component(Button.name, Button)
Vue.component(CountdownButton.name, CountdownButton)

Vue.component(ImageCropper.name, ImageCropper)

Vue.component(PopupPhotoShare.name, PopupPhotoShare)

Vue.component(PopupShareFriendPay.name, PopupShareFriendPay)
Vue.component(PopupMlmShare.name, PopupMlmShare)

Vue.component(ActivityIconOnProduct.name, ActivityIconOnProduct)
Vue.component(ServeIcon.name, ServeIcon)

module.export = {
  HeaderView,
  HeaderItem,
  CountdownButton,
  InfoTextItem,
  InfoRadioItem,
  InfoRadioList,
  InfoToggleItem,
  Button,
  ImageCropper,
  PopupPhotoShare,
  PopupShareFriendPay,
  PopupMlmShare,
  TopList,
  BaseList,
  EmptyItem,
  ServeIcon
}
