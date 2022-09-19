import Vue from 'vue'
import { MoveBuild } from './MoveBuild'

// 可拖动
Vue.directive('move', {
  inserted: el => {
    new MoveBuild(el)
  }
})
