/**
 * 实现可自由拖动 参考：https://cloud.tencent.com/developer/article/1491482
 */

export class MoveBuild {
  constructor(ele) {
    // console.log(ele, 'element')
    this.ele = ele

    // 窗口尺寸
    this.client.width = document.documentElement.clientWidth
    this.client.height = document.documentElement.clientHeight
    // 元素的初始位置
    const { left, top, right, bottom } = this.ele.getBoundingClientRect()
    this.eleClientRect = { left, top, right, bottom }

    this.init()
  }

  client = {
    width: 0,
    height: 0
  }

  eleClientRect = {}

  domPosition = {
    x: 0,
    y: 0
  }

  startPosition = {
    x: 0,
    y: 0
  }

  openMove = false

  setMove(x = 0, y = 0) {
    this.ele.style.transform = `translate( ${x}px, ${y}px )`
  }

  init() {
    this.ele.addEventListener('touchstart', e => this.start(e))
    this.ele.addEventListener('touchmove', e => this.move(e))
    this.ele.addEventListener('touchend', e => this.end(e))
    this.setMove()
  }

  start(e) {
    const el = this.ele
    const { pageX, pageY } = e.changedTouches[0]
    this.startPosition = {
      x: pageX,
      y: pageY
    }
    const domPosition = el.style.transform.match(/\-?[\d]+.?[\d]*px/gim)
    this.domPosition = {
      x: parseInt(domPosition[0].replace('px', '')),
      y: parseInt(domPosition[1].replace('px', ''))
    }

    this.openMove = true
  }

  move(e) {
    if (!this.openMove) {
      return
    }
    e.preventDefault()
    e.stopPropagation()

    const { pageX, pageY } = e.changedTouches[0]
    const movePoisition = {
      x: pageX,
      y: pageY
    }

    const x = movePoisition.x - this.startPosition.x + this.domPosition.x
    const y = movePoisition.y - this.startPosition.y + this.domPosition.y

    // 不能超出当前可视区域
    // TODO 用户拖动并不一定是绝对的水平或垂直，此时若X轴超出，单Y轴未超出，可让Y轴继续拖动。
    const { top, right, bottom, left } = this.eleClientRect
    if (left + x <= 0 || top + y <= 0 || right + x >= this.client.width || bottom + y >= this.client.height) {
      return
    }

    this.setMove(x, y)
  }

  end(e) {
    this.openMove = false
  }
}
