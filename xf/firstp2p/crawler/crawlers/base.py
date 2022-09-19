#!/usr/bin/python
#-*-coding:utf-8-*- 

import sys
from NCFStrLib import ncf_strlib
from NCFBrowserLib import ncf_browser_lib
from NCFUrlLib import ncf_urllib

from lib.exception import CrawlerException

reload(sys)
sys.setdefaultencoding('utf8')
class Base:
    def request(self,url):
        print 'connectting %s  ...' %(url)
        try:
          content = ncf_urllib.GetTextFromUrl(self.url,ncf_browser_lib.BrowserAgent.CHROME_LINUX_13)
          print 'connectting success'
          if not content:            
            print 'ERROR : socket error %s ' %(url)
            ex=CrawlerException()
            body='抓取地址错误，不能返回信息 <a href="%s">%s</a>' %(url,url)
            ex.throwException(body)            
            return False
            
          return content;
        except IOError:
            print 'ERROR : socket error %s ' %(url)
            ex=CrawlerException()
            body='抓取地址错误，不能返回信息 <a href="%s">%s</a>' %(url,url)
            ex.throwException(body)
            return False
        
    def findStr(self,str,start,end):
      (ratio, index) = ncf_strlib.GetStringBetween(str,start,end)
      return ratio
      if not str:
          return ''
      p1=str.find(start)
      if p1>=0:
          p1+=len(start)
          p2=str.find(end,p1)
          if(p2 >=0):
              content=str[p1:p2]
              return content.strip()
          else:
              print 'error p2'
              return ''             
      else:
          print 'error p1'
          return ''