import Dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'

// 支持 UTC 时间操作
Dayjs.extend(utc)

export default Dayjs
