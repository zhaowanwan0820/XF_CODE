#-*-coding:utf-8-*- 

import sys,datetime
from base import Base
from lib.exception import CrawlerException

class Alipay(Base):
  
  def request(self,url):
    print '[alipay]'
    self.url=url
    return Base.request(self,url)    
      
  def parseHtml(self,html):
    now=datetime.datetime.now()+datetime.timedelta(days = -1)
    now_str=now.strftime('%Y-%m-%d')
    date_str=self.findStr(html,'<em class="date">','</em>')    
    if date_str==now_str:
      point_str=self.findStr(html,'<span class="nianhuashouyilv">','</span>')
      
      # point_str=re.findall('<em\s*class="nianhualv"\s*>\s*(-?\d+.?\d+%)\s*</em>',html,re.I|re.M|re.S)
      point=point_str if (point_str) else False
      return point
    else:
      print 'error : date is error'
      body=u'余额宝抓取错误，日期不正确，抓取地址：%s ，抓取内容：%s ，系统时间：%s' %(self.url,date_str,now_str)
      CrawlerException().throwException(body)
      return False
            