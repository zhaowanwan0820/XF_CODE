##README ###技术栈

`vue2 + vuex + vue-router + webpack4 + es6 + axios + sass + flex`

###目录结构

```

├── README.md             # 项目说明
├── build                 # 打包
│   ├── build.js
│   ├── check-versions.js
│   ├── logo.png
│   ├── utils.js
│   ├── vue-loader.conf.js
│   ├── webpack.base.conf.js
│   ├── webpack.dev.conf.js
│   └── webpack.prod.conf.js
│
├── config                # 打包配置
│   ├── dev.env.js
│   ├── index.js
│   └── prod.env.js
│
├── dist                  # 打包后目录(若是根节点下有同级ecshop/h5目录则优先使用)
│   ├── index.html
│   └── static
│       ├── css
│       ├── img
│       └── js
│
├── index.html
├── package-lock.json
├── package.json
└── src                    # 代码目录
    ├── App.vue                 # 根组件
    ├── api                     # api模板（yumi生成，无需修改）
    │   └── index.js            # api索引（可选）
    │
    ├── assets                  # 资源文件
    │   ├── image               # 图片资源
    │   ├── font                # 字体文件
    │   ├── js                  # 第三方js插件
    │   └── style
    │       ├── _variable.scss  # scss变量文件
    │       ├── mixin.scss      # scss函数文件
    │       └── reset.scss      # 重置浏览器样式文件
    │
    ├── components              # 公共组件
    │   └── common
    │
    ├── config                  # 项目config目录
    │   ├── const.js            # 常量文件
    │   ├── enum.js             # 枚举文件
    │   └── env.js              # 环境配置
    │
    ├── main.js                 # 程序入口
    ├── server                  # 网络请求（api的公共部分）
    │   └── network.js
    │
    ├── page                    # 业务模块
    │   ├── auth
    │   ├── product             # 商品模块
    │   │   ├── child           # 附属的子模块组件
    │   │   ├── product.vue     # 模块组件
    │   │   └── static.js       # 组件所需要的静态数据(可选）
    │   └── home
    │
    ├── router                  # 路由组件
    │   └── router.js
    │
    ├── store                   # 状态管理vuex
    │   ├── actions.js          # 根级别的 action
    │   ├── getter.js           # 根级别的 getter
    │   ├── index.js
    │   ├── modules             # 模块部分
    │   │   ├── auth.js
    │   │   └── config.js
    │   └── mutations.js        # 根级别的 mutation
    │
    └── util                    # 公共的方法(原型链方式加上）
        └── util.js

```

###代码规范 ####命名规范 #####文件夹命名

- 统一命名为小写
- 业务模块文件夹（page）代表着模块的名字
  - 由名词组成（car、order、cart）
  - 单词只能有一个 （good: car order cart）（bad: carInfo carpage）

#####文件命名

- 组件文件命名 PascalCase (单词首字母大写命名)
  - PascalCase 是最通用的声明约定而 kebab-case 是最通用的使用约定（备注：参阅[组件命名约定](https://cn.vuejs.org/v2/guide/components.html#%E7%BB%84%E4%BB%B6%E5%91%BD%E5%90%8D%E7%BA%A6%E5%AE%9A)）
  - 尽量是名词
  - 大写开头，开头的单词就是所属模块名字（CarDetail、CarEdit、CarList）
  - 常用结尾单词有（Detail、Edit、List、Info）
- 其它 js 文件命名小写

####代码格式

- 使用 eslint -> prettier 自定义规范
  - npm install -g prettier
- 配置 sublime text
  - package: (JsPrettier)[https://github.com/jonlabelle/SublimeJsPrettier]
- 配置 visual studio code
  - extension: (ESLint)[https://marketplace.visualstudio.com/itemdetails?itemName=dbaeumer.vscode-eslint]
  - extension: (Prettier)[https://marketplace.visualstudio.com/itemdetails?itemName=esbenp.prettier-vscode]
  - extension: (Vetur)[https://marketplace.visualstudio.com/itemdetails?itemName=octref.vetur]
  - 配置 settings.json

```
{
    "eslint.validate": [
        "javascript",
        "javascriptreact",
        {
            "language": "vue",
            "autoFix": true
        }
    ],
    "eslint.autoFixOnSave": true,
    "vetur.format.defaultFormatter.html": "prettyhtml",
    "vetur.format.defaultFormatter.js": "prettier",
    "editor.formatOnSave": false,
}
```

###TODO: ###技术栈

- vuex - api template
- css
  - sass
- 页面适配
  - flex

####功能模块

- 缓存处理机制
- flex
- svg

###其它

- 打包自动更新的问题（微信端打开还是旧的版本）
- 首页卡片组加载速度
  - 目标：首页加载秒开

###公共组件库

- npm 打包
- transform

###扩展

- TypeScript 支持
- TypeScript 模板
- 日志插件

###开发疑难排解

- 如果出现 www.shop.itz ... 的报错，就将这个域名绑到本地 host
- .vue.exc 为原生但用不到的封存 vue
