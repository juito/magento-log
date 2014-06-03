<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class EbayEnterprise_MageLog_Model_Stream extends Zend_Log_Writer_Stream
{
	protected $_helper;

	/**
	 * Get helper instantiated object.
	 *
	 * @return EbayEnterprise_MageLog_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = new EbayEnterprise_MageLog_Helper_Data;
		}
		return $this->_helper;
	}

	public function __construct($streamOrUrl, $mode=null)
	{
		if(!$streamOrUrl){
			// in case the stream or file is an empty array or null value give it the default 'system.log' file.
			$streamOrUrl = $this->_getHelper()->getLogFile();
		}
		parent::__construct($streamOrUrl, $mode);
	}

	protected function _write($event)
	{
		// allow logging at the config level where the priority is less or equal to the admin config log level.
		if(isset($event['priority']) && $event['priority'] <= $this->_getHelper()->getLogLevel()){
			parent::_write($event);
		}

		// check if e-mail alert logging is enabled.
		if($this->_getHelper()->isEnableEmailLogging()){
			// check if the priority level is less than or equal to the config email level logging.
			if(isset($event['priority']) && $event['priority'] <= $this->_getHelper()->getEmailLoggingLevel()){
				// check if logging e-mail address is config in the backend
				if(trim($this->_getHelper()->getLoggingEmailAddress()) !== ''){
					// proceed to send logging e-mail
					$this->_sendLoggingEmail($event);
				}
			}
		}
	}

	/**
	 * send logging e-mail.
	 *
	 * @return void
	 */
	protected function _sendLoggingEmail($event)
	{
		if($event){
			$mail = new Zend_Mail();
			$mail->setFrom($this->_getHelper()->getFromEmail())
				->addTo($this->_getHelper()->getLoggingEmailAddress());

			$writer = new Zend_Log_Writer_Mail($mail);
			$writer->setSubjectPrependText($event['priorityName'] . ': logging error alert');
			$writer->addFilter($event['priority']);
			$log = new Zend_Log();
			$log->addWriter($writer);
			$log->addFilter($event['priority']);

			$log->log($event['message'], $event['priority']);
		}
	}
}
