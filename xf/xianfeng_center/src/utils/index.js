/**
 * Utils
 */

export { default as axios } from './axios'
export { default as storage } from './storage'
export { default as nprogress } from './nprogress'
export { default as dayjs } from './dayjs'
export { default as decimal } from './decimal'

export const parseParams = (uri, params) => {
  const paramsArray = []
  Object.keys(params).forEach(key => params[key] && paramsArray.push(`${key}=${encodeURIComponent(params[key])}`))
  uri += `${uri.search(/\?/) === -1 ? '?' : '&'}${paramsArray.join('&')}`
  return uri
}
