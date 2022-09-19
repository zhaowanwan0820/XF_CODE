#-*-coding:utf-8-*- 

#[db]
class Config:
  db_host='127.0.0.1'
  db_name='firstp2p'
  db_port=3306
  db_user='root'
  db_pass=''

  rate_float=10

#[error1]
#抓取失败的报警内容
#subject=爬虫异常警告
#message=error

#[error2]
#数据源数据不一致的报警内容
#subject=爬虫异常警告
#message=error

#[error2]
#数据与前一天对比，变化过大的报警内容
#subject=爬虫异常警告
#message=error


