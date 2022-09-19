#!/usr/bin/python
# -*- encoding:utf-8 -*-  
import smtplib  
from email.mime.text import MIMEText
from email.header import Header
class Mail:
    #邮件发送类
    sender = 'wenchao'
    receiver = []
    subject = ''
    smtpserver = ''
    username = ''
    password = ''
    msg = ''
    
    #初始化类
    def __init__(self):
        print 'init'


        
    #增加多个邮件地址
    def addAddress(self,reciver):
        if(len(self.receiver)==0):
            self.receiver = reciver
        else:
            self.receiver = self.receiver.append(reciver)
        
        
    '''   
    def AddReplyTo(self,receiver):
        if(len(Mail.receiver)==0):
            Mail.receiver = reciver
        else:
            Mail_receiver = Mail.receiver.append(reciver)
    '''
    #发送邮件
    def sendEmail(self):
        
        msg = MIMEText(self.msg,'plain','utf-8')#中文需参数‘utf-8’，单字节字符不需要
        msg['Subject'] = Header(self.subject, 'utf-8')
        print self.receiver
#         exit()
        try:
            smtp = smtplib.SMTP()
            smtp.connect(self.smtpserver)
            smtp.login(self.username, self.password)
            smtp.sendmail(self.sender, self.receiver, msg.as_string())
            smtp.quit()
        except Exception,ex:
            emsg = Exception,":",ex
            return emsg
        else:
            return '1'


'''
if(__name__=='__main__'):
    print ''
else:
    m = Mail()
    




#s = Student("Peter", 25, 90)
'''       
        