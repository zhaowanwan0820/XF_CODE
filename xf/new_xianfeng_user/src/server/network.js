import { Toast } from "vant";
import axios from "axios";
import utils from "../util/util";
import qs from "qs";
import store from "../store/index";
import router from "../router";

// 使用线上还是开发环境的后端接口
const origin =
  location.host === "m.xfuser.com"
    ? "https://api.xfuser.com"
    // : "http://qa1.zichanhuayuan.com";
    :"http://qa1api.xfuser.com";  //本地开发使用，发版后注释掉，打开上一行

// 新建一个axios实例，不受全局的其他axios影响
const instance = axios.create({
  baseURL: origin,
  timeout: 50000
});

function fetchEndpoint(reqUrl, methodType, data = {}, config = {}) {
  if (!reqUrl) {
    return;
  }

  if (methodType == "GET" || methodType === "PUT") {
    let dataStr = ""; //数据拼接字符串
    Object.keys(data).forEach(key => {
      if (data[key] || data[key] == 0) {
        dataStr += key + "=" + data[key] + "&";
      }
    });

    if (dataStr !== "") {
      dataStr = dataStr.substr(0, dataStr.lastIndexOf("&"));
      reqUrl = reqUrl + "?" + dataStr;
    }
  }

  // 给Get url添加随机串防止缓存
  if (methodType === "GET") {
    const randomStr = new Date().getTime() + Math.random();
    reqUrl +=
      (reqUrl.indexOf("?") > -1 ? "&" : "?") + `randomGetKey=${randomStr}`;
  }

  // token
  let token = null;
  if (store.getters.token) {
    token = store.getters.token;
  }
  let postBody = qs.stringify(data);

  return new Promise((resolve, reject) => {
    instance
      .request(
        Object.assign(
          {
            method: methodType,
            url: reqUrl,
            data: postBody,
            headers: {
              "X-HH-AUTHORIZATION": token,
              "Content-Type": "application/x-www-form-urlencoded"
            }
          },
          config
        )
      )
      .then(
        res => {
          resolve(res.data);
          if (res.data.code == 1016) {
            Toast(res.data.info);
            store.commit("clearToken", "");
            let name = router.currentRoute.name;
            if (name && name !== "login") {
              router.push({
                name: "login"
              });
            }
            return;
          }
          if (res.data.code && res.data.code != 0 &&res.data.code != 1098) {
            Toast(res.data.info);
          }
        },
        error => {
          let info = "";
          if (error.errorMsg) {
            info = error.errorMsg;
          } else if (typeof error === "string" && /^\<\!DOCTYPE/.test(error)) {
            // 接口代码报错
            // Toast('网络繁忙，连接失败')
            router.push({
              path: "/noNetWork"
            });
          } else {
            info = "网络繁忙，请稍后重试";
          }
          if (info) {
            Toast(info);
          }
          // 不把服务端的错误reject给业务层
          resolve({
            code: -1,
            data: {},
            info
          });
        }
      );
  });
}

export function fetchGet(url, data = {}, config = {}) {
  return fetchEndpoint(url, "GET", data, config);
}

export function fetchPost(url, data = {}, config = {}) {
  return fetchEndpoint(url, "POST", data, config);
}

export function fetchGetBlob(url, data = {}, config = {}) {
  return fetchPost(
    url,
    data,
    Object.assign(
      {
        responseType: "blob",
        timeout: 200e3
      },
      config
    )
  );
}
