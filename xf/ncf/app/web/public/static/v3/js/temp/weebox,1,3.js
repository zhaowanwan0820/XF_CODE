import { StyleSheet } from 'react-native';

export default StyleSheet.create({
  'dialog-loading': {
    'background': 'url(images/loading.gif) no-repeat center',
    'width': [{ 'unit': '%H', 'value': 1 }],
    'height': [{ 'unit': '%V', 'value': 1 }]
  },
  'dialog-mask': {
    'opacity': '0.35 !important',
    'border': [{ 'unit': 'px', 'value': 0 }],
    'background': '#000',
    'margin': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'padding': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'position': 'absolute',
    'top': [{ 'unit': 'px', 'value': 0 }],
    'left': [{ 'unit': 'px', 'value': 0 }],
    'zIndex': '999'
  },
  'dialog-box': {
    'background': '#fff',
    'width': [{ 'unit': 'px', 'value': 300 }]
  },
  'dialog-box dialog-header': {
    'padding': [{ 'unit': 'px', 'value': 8 }, { 'unit': 'px', 'value': 8 }, { 'unit': 'px', 'value': 8 }, { 'unit': 'px', 'value': 8 }],
    'height': [{ 'unit': 'px', 'value': 15 }],
    'borderBottom': [{ 'unit': 'string', 'value': 'solid' }, { 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': '#ccc' }],
    'position': 'relative'
  },
  'dialog-box dialog-title': {
    'float': 'left',
    'textAlign': 'left',
    'fontSize': [{ 'unit': 'px', 'value': 12 }],
    'position': 'relative'
  },
  'dialog-box dialog-close': {
    'float': 'right',
    'cursor': 'pointer',
    'margin': [{ 'unit': 'px', 'value': 3 }, { 'unit': 'px', 'value': 3 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 11 }],
    'width': [{ 'unit': 'px', 'value': 11 }]
  },
  'dialog-box dialog-content': {
    'clear': 'both',
    'margin': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'padding': [{ 'unit': 'px', 'value': 6 }, { 'unit': 'px', 'value': 6 }, { 'unit': 'px', 'value': 6 }, { 'unit': 'px', 'value': 6 }],
    'color': '#666666',
    'fontSize': [{ 'unit': 'px', 'value': 13 }],
    'overflowY': 'auto'
  },
  'dialog-box dialog-button': {
    'clear': 'both',
    'borderTop': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#cccccc' }],
    'textAlign': 'center',
    'margin': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'paddingTop': [{ 'unit': 'px', 'value': 5 }]
  },
  'errorbox': {
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#924949' }]
  },
  'errorbox dialog-content': {
    'background': '#fff url(images/e_bg.jpg) bottom right no-repeat'
  },
  'errorbox dialog-header': {
    'background': 'url(images/e_hd.gif) repeat-x',
    'color': '#6f2c2c'
  },
  'warningbox': {
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#c5a524' }]
  },
  'warningbox dialog-content': {
    'background': '#fff url(images/w_bg.jpg) bottom right no-repeat'
  },
  'warningbox dialog-header': {
    'background': 'url(images/w_hd.gif) repeat-x',
    'color': '#957c17'
  },
  'successbox': {
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#60a174' }]
  },
  'successbox dialog-content': {
    'background': '#fff url(images/s_bg.jpg) bottom right no-repeat'
  },
  'successbox dialog-header': {
    'background': 'url(images/s_hd.gif) repeat-x',
    'color': '#3c7f51'
  },
  'promptbox': {
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#cccccc' }]
  },
  'promptbox dialog-content': {
    'background': '#fff url(images/p_bg.jpg) bottom right no-repeat'
  },
  'promptbox dialog-header': {
    'background': '#edf3f7',
    'color': '#355468'
  },
  'dialogbox': {
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#cccccc' }]
  },
  'dialogbox dialog-content': {
    'background': '#fff'
  },
  'dialogbox dialog-header': {
    'background': '#f0f0f0',
    'color': '#999'
  },
  'boxbox': {
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#cccccc' }]
  },
  'boxbox dialog-content': {
    'background': '#fff'
  },
  'boxbox dialog-header': {
    'background': '#edf3f7',
    'color': '#355468'
  },
  'weedialog': {
    'background': '#fff',
    'border': [{ 'unit': 'px', 'value': 5 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#8d8d8d' }],
    'borderRadius': '5px'
  },
  'weedialog dialog-title': {
    'backgroundImage': 'none'
  },
  'weedialog dialog-header': {
    'height': [{ 'unit': 'px', 'value': 57 }],
    'lineHeight': [{ 'unit': 'px', 'value': 57 }],
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#eeeeee' }],
    'cursor': 'move',
    'fontSize': [{ 'unit': 'px', 'value': 16 }],
    'width': [{ 'unit': '%H', 'value': 1 }],
    'clear': 'both',
    'textAlign': 'left',
    'position': 'relative',
    'top': [{ 'unit': 'px', 'value': 0 }],
    'left': [{ 'unit': 'px', 'value': 0 }],
    'background': '#FFF'
  },
  'weedialog dialog-title': {
    'color': '#333333',
    'float': 'left',
    'paddingLeft': [{ 'unit': 'px', 'value': 30 }]
  },
  'weedialog dialog-top': {
    'height': [{ 'unit': 'px', 'value': 0 }],
    'position': 'relative',
    'overflow': 'hidden'
  },
  'weedialog dialog-tl': {
    'position': 'absolute',
    'left': [{ 'unit': 'px', 'value': 0 }],
    'top': [{ 'unit': 'px', 'value': 0 }],
    'width': [{ 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-tc': {
    'marginLeft': [{ 'unit': 'px', 'value': 8 }],
    'marginRight': [{ 'unit': 'px', 'value': 8 }],
    'width': [{ 'unit': 'string', 'value': 'auto' }],
    'height': [{ 'unit': 'px', 'value': 0 }],
    'overflow': 'hidden'
  },
  'weedialog dialog-tr': {
    'position': 'absolute',
    'right': [{ 'unit': 'px', 'value': 0 }],
    'top': [{ 'unit': 'px', 'value': 0 }],
    'width': [{ 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-close': {
    'cursor': 'pointer',
    'float': 'right',
    'margin': [{ 'unit': 'px', 'value': 20 }, { 'unit': 'px', 'value': 20 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 16 }],
    'width': [{ 'unit': 'px', 'value': 16 }],
    'background': 'url(images/wee_close_new.png) no-repeat'
  },
  'weedialog dialog-close:hover': {
    'cursor': 'pointer',
    'float': 'right',
    'margin': [{ 'unit': 'px', 'value': 20 }, { 'unit': 'px', 'value': 20 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 16 }],
    'width': [{ 'unit': 'px', 'value': 16 }],
    'background': 'url(images/wee_close_new.png) no-repeat'
  },
  'weedialog dialog-close:active': {
    'cursor': 'pointer',
    'float': 'right',
    'margin': [{ 'unit': 'px', 'value': 20 }, { 'unit': 'px', 'value': 20 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 16 }],
    'width': [{ 'unit': 'px', 'value': 16 }],
    'background': 'url(images/wee_close_new.png) no-repeat'
  },
  'weedialog dialog-close:hover': {
    'opacity': '0.8'
  },
  'weedialog dialog-content': {
    'clear': 'both',
    'padding': [{ 'unit': 'px', 'value': 35 }, { 'unit': 'px', 'value': 35 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 35 }],
    'overflowY': 'auto',
    'overflowX': 'hidden',
    'backgroundColor': '#fff',
    'paddingBottom': [{ 'unit': 'px', 'value': 0 }],
    'fontSize': [{ 'unit': 'px', 'value': 14 }],
    'color': '#333'
  },
  'weedialog dialog-cl': {
    'width': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-cr': {
    'width': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-button': {
    'textAlign': 'center',
    'fontSize': [{ 'unit': 'px', 'value': 16 }],
    'padding': [{ 'unit': 'px', 'value': 30 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 49 }, { 'unit': 'px', 'value': 0 }],
    'clear': 'both',
    'background': '#fff',
    'height': [{ 'unit': 'px', 'value': 40 }]
  },
  'weedialog btn-base': {
    'display': 'inline-block',
    'paddingLeft': [{ 'unit': 'px', 'value': 0 }],
    'color': '#FFF',
    'fontSize': [{ 'unit': 'px', 'value': 16 }]
  },
  'weedialog btn-base span': {
    'display': 'inline-block',
    'padding': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 27 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 22 }],
    'float': 'left'
  },
  'weedialog dialog-ok': {
    'border': [{ 'unit': 'px', 'value': 0 }],
    'fontSize': [{ 'unit': 'px', 'value': 16 }],
    'height': [{ 'unit': 'px', 'value': 40 }],
    'lineHeight': [{ 'unit': 'px', 'value': 40 }],
    'color': '#fff',
    'cursor': 'pointer',
    'borderRadius': '3px',
    'backgroundImage': 'none'
  },
  'weedialog dialog-ok': {
    'width': [{ 'unit': 'px', 'value': 155 }],
    'height': [{ 'unit': 'px', 'value': 40 }],
    'lineHeight': [{ 'unit': 'px', 'value': 40 }],
    'background': '#ee4634'
  },
  'weedialog dialog-ok:hover': {
    'width': [{ 'unit': 'px', 'value': 155 }],
    'height': [{ 'unit': 'px', 'value': 40 }],
    'lineHeight': [{ 'unit': 'px', 'value': 40 }],
    'background': '#ee4634'
  },
  'weedialog dialog-ok:active': {
    'width': [{ 'unit': 'px', 'value': 155 }],
    'height': [{ 'unit': 'px', 'value': 40 }],
    'lineHeight': [{ 'unit': 'px', 'value': 40 }],
    'background': '#ee4634'
  },
  'weedialog dialog-ok span': {
    'height': [{ 'unit': 'px', 'value': 40 }],
    'lineHeight': [{ 'unit': 'px', 'value': 40 }],
    'background': 'none',
    'width': [{ 'unit': '%H', 'value': 1 }],
    'padding': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-button-disabled': {
    'overflow': 'visible',
    'overflowY': 'hidden',
    'border': [{ 'unit': 'px', 'value': 1 }, { 'unit': 'string', 'value': 'solid' }, { 'unit': 'string', 'value': '#999' }],
    'background': 'url(img/btn_cancel.gif) top',
    'height': [{ 'unit': 'px', 'value': 24 }],
    'lineHeight': [{ 'unit': 'px', 'value': 24 }],
    'color': '#666',
    'cursor': 'pointer',
    'padding': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 5 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 5 }],
    'margin': [{ 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }, { 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-bot': {
    'clear': 'both',
    'height': [{ 'unit': 'px', 'value': 0 }],
    'position': 'relative',
    'fontSize': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-bl': {
    'position': 'absolute',
    'left': [{ 'unit': 'px', 'value': 0 }],
    'top': [{ 'unit': 'px', 'value': 0 }],
    'width': [{ 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-bc': {
    'marginLeft': [{ 'unit': 'px', 'value': 0 }],
    'marginRight': [{ 'unit': 'px', 'value': 0 }],
    'width': [{ 'unit': 'string', 'value': 'auto' }],
    'height': [{ 'unit': 'px', 'value': 0 }]
  },
  'weedialog dialog-br': {
    'position': 'absolute',
    'right': [{ 'unit': 'px', 'value': 0 }],
    'top': [{ 'unit': 'px', 'value': 0 }],
    'width': [{ 'unit': 'px', 'value': 0 }],
    'height': [{ 'unit': 'px', 'value': 0 }]
  },
  'shuhui_box but-disabled': {
    'background': 'url(images/wee-icon.png) no-repeat left -277px !important'
  },
  'shuhui_box but-disabled span': {
    'height': [{ 'unit': 'px', 'value': 38 }],
    'lineHeight': [{ 'unit': 'px', 'value': 36 }],
    'background': 'url(images/wee-icon.png) no-repeat right -61px',
    'color': '#fff'
  },
  'ui_send_msg wee-send send-input p': {
    'paddingBottom': [{ 'unit': 'px', 'value': 16 }]
  },
  // 操作成功或失败
  'weedialog iicon-pop-suc': {
    'backgroundImage': 'url(images/suc_icon.png)',
    'backgroundRepeat': 'no-repeat',
    'display': 'inline-block',
    'width': [{ 'unit': 'px', 'value': 43 }],
    'height': [{ 'unit': 'px', 'value': 43 }],
    'verticalAlign': 'middle',
    'marginRight': [{ 'unit': 'px', 'value': 12 }]
  },
  'weedialog iicon-pop-fail': {
    'backgroundImage': 'url(images/suc_icon.png)',
    'backgroundRepeat': 'no-repeat',
    'display': 'inline-block',
    'width': [{ 'unit': 'px', 'value': 43 }],
    'height': [{ 'unit': 'px', 'value': 43 }],
    'verticalAlign': 'middle',
    'marginRight': [{ 'unit': 'px', 'value': 12 }]
  },
  'weedialog iicon-pop-ts': {
    'backgroundImage': 'url(images/suc_icon.png)',
    'backgroundRepeat': 'no-repeat',
    'display': 'inline-block',
    'width': [{ 'unit': 'px', 'value': 43 }],
    'height': [{ 'unit': 'px', 'value': 43 }],
    'verticalAlign': 'middle',
    'marginRight': [{ 'unit': 'px', 'value': 12 }]
  },
  'weedialog iicon-pop-suc': {
    'backgroundPosition': '0 -49px',
    'marginRight': [{ 'unit': 'px', 'value': 13 }]
  },
  'weedialog iicon-pop-fail': {
    'backgroundPosition': '0 0'
  },
  'weedialog iicon-pop-ts': {
    'backgroundPosition': '0 -99px'
  },
  // 通行证浮层
  'supernatant': {
    'position': 'absolute',
    'left': [{ 'unit': 'px', 'value': 0 }],
    'top': [{ 'unit': 'px', 'value': 0 }],
    'width': [{ 'unit': '%H', 'value': 1 }],
    'height': [{ 'unit': '%V', 'value': 1 }],
    'zIndex': '999999',
    'background': '#000 url(images/guidance.png) no-repeat 106.5% 61.7%',
    'filter': 'alpha(opacity=80)',
    'MozOpacity': '0.80',
    'KhtmlOpacity': '0.8',
    'opacity': '0.80',
    'display': 'none'
  }
});
