import Vue from 'vue'
import { AxiosInstance } from 'axios'
import { NProgress } from 'nprogress'

import {
  ExchangeService,
  PhoneService
} from './services'
interface Services {
  exchange: typeof ExchangeService
  phone: typeof PhoneService
}

declare module 'vue/types/vue' {
  interface Vue {
    $axios: AxiosInstance
    $nprogress: NProgress
    $services: Services
    $title: string
  }
}
