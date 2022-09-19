# coding:utf-8  
import email  
import smtplib  
class Mailer:  
    def __init__(self, smtp_host, smtp_user, smtp_passwd, smtp_port = 25) :  
        self.smtp_host = smtp_host  
        self.smtp_user = smtp_user  
        self.smtp_passwd = smtp_passwd  
        self.smtp_port = smtp_port  
        self.mail = email.MIMEMultipart.MIMEMultipart('related')  
        self.alter = email.MIMEMultipart.MIMEMultipart('alternative')  
        self.mail.attach(self.alter)  
        self.attachments = []  
    def mailfrom(self, mail_from) :  
        self._from = mail_from  
        self.mail['from'] = mail_from  
    def mailto(self, mail_to) :  
        """ 
        mail_to : comma separated emails 
        """  
        self._to = mail_to  
        if type(mail_to) == list:  
            self.mail['to'] = ','.join(mail_to)  
        elif type(mail_to) == str :  
            self.mail['to'] = mail_to  
        else :  
            raise Exception('invalid mail to')  
    def mailsubject(self, mail_subject) :  
        self.mail['subject'] = mail_subject  
    def text_body(self, body, encoding = 'utf-8') :  
        self.alter.attach(email.MIMEText.MIMEText(body, 'plain', encoding))  
    def html_body(self, body, encoding = 'utf-8') :  
        self.alter.attach(email.MIMEText.MIMEText(body, 'html', encoding))  
    def addattach(self, filepath, mime_type = 'octect-stream', rename = None) :  
        import os  
        f = open(filepath, 'rb')  
        filecontent = f.read()  
        f.close()  
        mb = email.MIMEBase.MIMEBase('application', mime_type)  
        mb.set_payload(filecontent)  
        email.Encoders.encode_base64(mb)  
        fn = os.path.basename(filepath)  
        mb.add_header('Content-Disposition', 'attachment', filename = rename or fn)  
        self.mail.attach(mb)  
    def send(self):  
        self.mail['Date'] = email.Utils.formatdate( )  
        smtp = False  
        try:  
            smtp = smtplib.SMTP()  
            smtp.set_debuglevel(0)  
            smtp.connect(self.smtp_host, self.smtp_port)  
            smtp.login(self.smtp_user, self.smtp_passwd)  
            smtp.sendmail(self._from, self._to, self.mail.as_string())  
            return  True  
        except Exception, e:  
            import traceback  
            print traceback.format_exc()  
            return False  
        #finally :  
        smtp and smtp.quit()
        