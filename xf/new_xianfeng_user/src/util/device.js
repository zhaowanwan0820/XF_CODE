import MobileDetect from "mobile-detect";
import {getIphoneModel} from "./iphone";


// 获取手机型号
export function phoneModel() {
  let contains = function (deviceInfo, needle) {
    for (let i in deviceInfo) {
      if (deviceInfo[i].indexOf(needle) > 0) return i;
    }
    return -1;
  };
  let device_type = navigator.userAgent; //获取userAgent信息
  let md = new MobileDetect(device_type); //初始化mobile-detect
  let os = md.os(); //获取系统
  let model = "";
  if (os === "iOS") {
    //ios系统的处理
    os = md.os() + md.version("iPhone");
    model = getIphoneModel();
  } else if (os === "AndroidOS") {
    //Android系统的处理
    os = md.os() + md.version("Android");
    let sss = device_type.split(";");
    let i = contains(sss, "Build/");
    if (i > -1) {
      model = sss[i].substring(0, sss[i].indexOf("Build/"));
    }
  }
  const brand =
    md.mobile() !== "UnknownPhone"
      ? md.mobile()
      : md.phone() !== "UnknownPhone"
        ? md.phone()
        : "";
  return os === "iOS" ? `${model}(${os})` : `${brand}${model}(${os})`;
}
