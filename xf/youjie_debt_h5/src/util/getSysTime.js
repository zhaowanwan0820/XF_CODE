import store from '../store/index'

export const getSysTime = () => {
  return new Date().getTime() + store.state.app.localTimeClockOffset
}
