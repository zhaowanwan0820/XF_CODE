import { ExchangeService, PhoneService } from '@/services'

export default Vue => {
  // alias
  const services = {
    exchange: ExchangeService,
    phone: PhoneService,
  }

  // mount the services to Vue
  Object.defineProperties(Vue, {
    services: { get: () => services },
  })

  // mount the services to Vue component instance
  Object.defineProperties(Vue.prototype, {
    $services: { get: () => services },
  })
}
