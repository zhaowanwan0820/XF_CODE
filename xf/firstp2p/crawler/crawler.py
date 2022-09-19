#-*-coding:utf-8-*- 
'''
Created on 2014-2-11

@author: yangqing@ucfgroup.com

#
# 文件名称：crawler.py
# 文件说明：爬虫主程序，包括页面的读取，解析，入库和报警功能。需要配置类库支持执行
#

'''

import sys,os,ConfigParser,time,datetime
from lib.mysql import MySQL
from lib.exception import CrawlerException

class Crawler:
  RATE_KEY='DEPOSIT_INTEREST_RATE'
  TURN_ON='TURN_ON_DEPOSIT_INTEREST'
  def __init__(self):
    print 'Crawler start'
    sys.path.append(".")
    self.__initDb()
  
  def __initDb(self):
    if('db' in dir(self)):
      return self.db
    else:
      if sys.argv[1]=='dev':
        from config.dev.config import Config
      elif sys.argv[1]=='test':
        from config.test.config import Config
      else:
        from config.online.config import Config
      
      self.RATE_FLOAT=Config.rate_float
      self.db=MySQL(Config.db_host,Config.db_user,Config.db_pass,Config.db_port)
      if(self.db):
        self.db.selectDb(Config.db_name)
        return self.db
      else:
        print 'ERROR : db init fail'
        sys.exit()
              
  def main(self):
    if self.__getTurnOnValue()!='1':
      print 'turn off'
      exit()
    
    now=datetime.datetime.now()+datetime.timedelta(days = -1)
    now_str=now.strftime('%Y-%m-%d')
    if self.__getRateStatus(now_str):
      print 'rate already exists'
      exit()
    
    if len(sys.argv)>2:
      type=sys.argv[2]
    else:
      type=''
      
    conf = ConfigParser.ConfigParser()
    conf.read(sys.path[0]+os.sep+"crawler.ini")
    
    if type == '':
      crawlers=conf.items('url')
    else:
      crawlers=[(type,conf.get('url',type))]
    point_list=[]
    for ob in crawlers:
      classname=ob[0].capitalize()
      module = __import__("crawlers."+ob[0])
      obj= getattr(getattr(module,ob[0]),classname)
      obj=obj()
      html=obj.request(ob[1])
      if(html):
        point=obj.parseHtml(html)            
        if point:
          print ob[0]+' point is : %s' %point
          data={'point':point,'source':ob[0],'ratetime':now_str,'updatetime':time.time()}
          self.__putData('firstp2p_crawler_rate',data)
          point=point.replace('%','')
          point_list.append((ob[0],("%.4f" %float(point))))  
        else:
          body=u'%s抓取错误，不能正确解析，抓取地址：%s' %(ob[0],ob[1])
          CrawlerException().throwException(body)
          print 'ERROR : not found point'
                
    print '==============\npoint list is: %s' %(point_list)
    ret=self.__compare(point_list) #对比各个数据源的结果
    if ret!=False:
      if self.__checkSize(ret):
        self.__setRateValue(ret)
        self.__updateRateStatus(point_list[0][0],now_str,1)
        
    CrawlerException().sendMail(self.db)  
    print 'finish'
    sys.exit()
    
  def __checkSize(self,num):
    if abs(float(num)-self.__getRateValue())>self.RATE_FLOAT:
      print 'point size change too big'
      return False
    else:
      return True
  def __getRateStatus(self,time):
    self.db.query('select id,source,point from firstp2p_crawler_rate where ratetime=\'%s\' and status=1' %(time))
    row=self.db.fetchRow();
    if row:
      print row
      return True
    else:
      return False
    
    
  def __updateRateStatus(self,source,time,status):
    self.db.update('firstp2p_crawler_rate',{'status':status},'source=\'%s\' and ratetime=\'%s\'' %(source,time))
    r=self.db.commit()
    if(r == None):
      print 'update rate status success '
      return True
    else:
      print 'ERROR : db update fail %s ' %r
      return False    
  
  def __getTurnOnValue(self):
    self.db.query('select value from firstp2p_conf where name=\'%s\'' %(self.TURN_ON))
    row=self.db.fetchRow();
    return row[0]
    
  def __getRateValue(self):
    self.db.query('select value from firstp2p_conf where name=\'%s\'' %(self.RATE_KEY))
    row=self.db.fetchRow();
    return float(row[0]) if (row) else False
    
  def __setRateValue(self,value):
    self.db.update('firstp2p_conf',{'value':value},'name=\'%s\'' %(self.RATE_KEY))
    r=self.db.commit()
    if(r == None):
      print 'update rate success '
      return True
    else:
      print 'ERROR : db update fail %s ' %r
      return False
    
  def __putData(self,tb,data):
    self.db.insert(tb,data)
    r=self.db.commit()
    if(r == None):
      print 'insert success %s' %(data)
    else:
      print 'ERROR : db update fail %s ' %r
      sys.exit()
  def __compare(self,point_list):
    if not point_list:
      print 'ERROR : list is null'
      body='不能对利率列表进行对比，列表为空'
      CrawlerException().throwException(body)
      return False
    point_tmp=point_list[0][1]
    for row in point_list:
      if point_tmp!=row[1]:
        print 'compre fail %s not equal to alipay' %(row[0])
        body=u'利息对比错误，%s 不匹配' %(row[0])
        CrawlerException().throwException(body)
        return False
        
    print u'compare success'
    return point_tmp
        
if __name__ == '__main__':
  app=Crawler()
  app.main()
