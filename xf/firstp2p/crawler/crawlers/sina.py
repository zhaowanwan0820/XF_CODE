#-*-coding:utf8-*- 

import datetime,time
from base import Base
from lib.exception import CrawlerException

class Sina(Base):
  def request(self,url):
    print '[sina]'    
    #方案一
    self.url='%s/?_=%s/&list=000198,f_000198' %(url,time.time()*1000)
    #方案二
    # self.url=url
    
    return Base.request(self,self.url)
      
  def parseHtml(self,html):  
    
    #方案一
    now=datetime.datetime.now()+datetime.timedelta(days = -1)
    now_str=now.strftime('%Y-%m-%d')  
    tmp=self.findStr(html,'"天弘增利宝货币,','"')
    strlist=tmp.split(',')
    
    if strlist[3]==now_str:
      point_str=strlist[1]
      point=point_str+'%' if (point_str) else False
      return point
      
    else:
      print 'error : date is error'
      body=u'新浪 天弘增利宝货币 抓取时间错误，抓取地址：%s ，抓取时间：%s ，系统时间：%s' %(self.url,strlist[3],now_str)
      CrawlerException().throwException(body)
      return False
      
    #方案二  
    now=datetime.datetime.now()+datetime.timedelta(days = -1)
    now_str='%s-%s-%s' %(now.year,now.month,now.day)
    
    tmp=self.findStr(html,'<th>日期','</tr>')
    date_str=self.findStr(tmp,'<th>','<th>')
    
    if date_str==now_str:
      tmp=self.findStr(html,'<th>七日年化收益率(%)','</tr>')
      point_str=self.findStr(tmp,'<td>','<td>')
      point=point_str if (point_str) else False
      return point
    else:
      print 'error : date is error'
      body=u'新浪 天弘增利宝货币 抓取时间错误，抓取地址：%s ，抓取时间：%s ，系统时间：%s' %(self.url,date_str,now_str)
      CrawlerException().throwException(body)
      return False
            