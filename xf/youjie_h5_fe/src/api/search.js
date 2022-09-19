import { fetchEndpoint } from '../server/network'

// 热门关键词
export const searchKeywordList = () => fetchEndpoint('/hh/hh.search.keyword.list', 'POST')
