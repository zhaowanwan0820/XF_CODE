#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, os, logging

class Log(object):
    def __init__(self):
        """Do nothing, by default."""
       
    @staticmethod 
    def log(msg):
        m_logger = logging.getLogger('tshr_root')
        m_hdlr = logging.FileHandler(sys.path[0]+os.sep+"log/email.log")
        m_formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
        m_hdlr.setFormatter(m_formatter)
        m_logger.addHandler(m_hdlr)
        m_logger.setLevel(logging.WARNING)
        m_logger.error(msg)
        m_hdlr.flush()
        m_logger.removeHandler(m_hdlr)
        m_hdlr.close()
