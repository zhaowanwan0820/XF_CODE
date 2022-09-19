#-*-coding:utf-8-*- 

import sys,os,datetime
from NCFMailLib import ncf_maillib

reload(sys)
sys.setdefaultencoding('utf8')
class CrawlerException:
  TO_USER_KEY='CRAWLER_TO_USER_KEY'
  def __ininEmail(self,db):    
    db.query('select * from firstp2p_mail_server where is_effect=\'1\'')
    row=db.fetchRow();
    if row:
      self.mailer = ncf_maillib.SSLMail(row[1],465)
      self.fro=row[2]
      self.pwd=row[3]
      
      db.query('select id from firstp2p_dictionary where `key`=\'%s\'' %(self.TO_USER_KEY))
      row=db.fetchRow();
      
      db.query('select value from firstp2p_dictionary_value where key_id=\'%s\'' %(row[0]))
      userlist=db.fetchAll();
      to_user=[]
      for useremail in userlist:
        to_user.append(useremail['value'])
      user_list=to_user
      
      if len(user_list)>=1:
        self.to=user_list
        return True
      else:
        return False
    else:
      return False
  
  def throwException(self,body):
    if(True):
      body+='<br/>\n文件：%s<br/>\n函数：%s<br/>\n行数：%s行 <br/>\n<br/>\n' \
        %(sys._getframe().f_back.f_code.co_filename,\
        sys._getframe().f_back.f_code.co_name,\
        sys._getframe().f_back.f_lineno)
        
      self.__saveLog(body)
      # self.mailer.mailto(self.fro,self.to,title,body,self.fro,self.pwd)
      
      # return self.mailer.send()
    else:
      body+=u'\n文件：%s\n函数：%s\n行数：%s行\n' \
      %(sys._getframe().f_back.f_code.co_filename,\
      sys._getframe().f_back.f_code.co_name,\
      sys._getframe().f_back.f_lineno)
      print body
      return True;
      
  def __saveLog(self,content):
    parent_path=os.path.abspath(os.path.join(sys.path[0],os.path.pardir))
    print '[save]'
    path=parent_path+os.sep+'runtime'+os.sep+'crawler'
    if not os.path.exists(path):
      os.makedirs(path)
    file_object=open(path+os.sep+'email.tmp','a')
    file_object.write(content)
    file_object.close()
    return path+os.sep+'email.tmp'
    
  def sendMail(self,db,title=u'网信理财 爬虫异常警告'):
    parent_path=os.path.abspath(os.path.join(sys.path[0],os.path.pardir))
    path=parent_path+os.sep+'runtime'+os.sep+'crawler'+os.sep+'email.tmp'
    
    if os.path.isfile(path):
      if self.__ininEmail(db)==False:
        return False
      else:
        file_object = file(path)
        try:
          content = file_object.readlines()
        finally:        
          file_object.close()
        
        if content:
          content='<br/>'.join(content)
          now_str=datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
          content+=u'\n[发送时间：%s]\n' %(now_str)
          os.remove(path)
          print 'send CrawlerException'
          self.mailer.mailto(self.fro,self.to,title,content,self.fro,self.pwd)
    
    
