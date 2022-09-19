import { fetchEndpoint } from '../server/network'

// 获取问卷接口
export const getQusetion = type => fetchEndpoint('/Launch/XfDebtGarden/GetQuestionnaire', 'POST', { type })

// 提交问卷接口
export const submitQuestions = params => fetchEndpoint('/Launch/XfDebtGarden/SendQuestionnaire', 'POST', params)

