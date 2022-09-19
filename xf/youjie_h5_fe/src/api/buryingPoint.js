import { fetchEndpoint } from '../server/network'
import utils from '../util/util'

export const sendBuryingPointInfo = ({
  click_position = '',
  referer = document.referrer,
  url = document.location.href,
  deviceID = utils.getDeviceID()
}) =>
  fetchEndpoint('/hh/hh.page.view.info', 'POST', {
    click_position: click_position,
    referer: referer,
    url: url,
    deviceID: deviceID
  })
