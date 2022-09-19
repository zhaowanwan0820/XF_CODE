import { fetchEndpoint } from '../server/network'

/**
 * Gets the brand list shown in home page
 *
 * @return     {Array}  a list with brand
 */
export const getHomeBrand = () => fetchEndpoint('/hh/hh.home.recommend.brand', 'POST')

/**
 * Gets the brand list with keyword
 *
 * @param      {String}  keyword  The keyword
 * @return     {Array}  The brand list.
 */
export const getBrandList = keyword =>
  fetchEndpoint('/hh/hh.brand.list', 'POST', {
    keyword: keyword
  })

/**
 * Gets the hot keyword list.
 *
 * @return     {Array}  The hot keyword list.
 */
export const getHotKeywordList = () => fetchEndpoint('/hh/hh.brand.keyword.list', 'POST')
