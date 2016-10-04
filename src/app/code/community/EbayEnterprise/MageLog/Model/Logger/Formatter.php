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

use Psr\Log\LogLevel;

class EbayEnterprise_MageLog_Model_Logger_Formatter extends Zend_Log_Formatter_Simple
{
    const APPLICATION_NAME = 'Magento Webstore';
    const LOG_TYPE = 'error';
    const APP_CONTEXT = 'external';

    /** @var array $_metaData */
    protected $_metaData = [];
    /** @var array $_psrLogLevelToZend */
    protected $_zendLogLevelToPsr = [
        Zend_Log::EMERG  => LogLevel::EMERGENCY,
        Zend_Log::ALERT  => LogLevel::ALERT,
        Zend_Log::CRIT   => LogLevel::CRITICAL,
        Zend_Log::ERR    => LogLevel::ERROR,
        Zend_Log::WARN   => LogLevel::WARNING,
        Zend_Log::NOTICE => LogLevel::NOTICE,
        Zend_Log::INFO   => LogLevel::INFO,
        Zend_Log::DEBUG  => LogLevel::DEBUG,
    ];

    /**
     * @see Zend_Log_Formatter_Simple::format
     * Overriding this method in order to format the log message
     * in PSR-3 Distributed Logging Architecture and Standard format.
     *
     * @param  array  $event
     * @return string
     */
    public function format($event)
    {
        $this->_metaData = $event;
        $this->_addAppName()
            ->_addLogType()
            ->_addAppContext()
            ->_addHost()
            ->_addLevel()
            ->_addExceptionInformation()
            ->_interpolate()
            ->_cleanMetaData();
        return json_encode($this->_metaData) . PHP_EOL;
    }

    /**
     * Add application name key to the meta data array.
     *
     * @return self
     */
    protected function _addAppName()
    {
        if (!isset($this->_metaData['app_name'])) {
            $this->_metaData['app_name'] = static::APPLICATION_NAME;
        }
        return $this;
    }

    /**
     * Add log type key to the meta data array.
     *
     * @return self
     */
    protected function _addLogType()
    {
        if (!isset($this->_metaData['log_type'])) {
            $this->_metaData['log_type'] = static::LOG_TYPE;
        }
        return $this;
    }

    /**
     * Add app context key to the meta data array.
     *
     * @return self
     */
    protected function _addAppContext()
    {
        if (!isset($this->_metaData['app_context'])) {
            $this->_metaData['app_context'] = static::APP_CONTEXT;
        }
        return $this;
    }

    /**
     * Add host key to the meta data array.
     *
     * @return self
     */
    protected function _addHost()
    {
        if (!isset($this->_metaData['host'])) {
            $this->_metaData['host'] = Mage::app()->getFrontController()->getRequest()->getHttpHost();
        }
        return $this;
    }

    /**
     * Translate Zend Log level back to PSR-3 level.
     *
     * @param string $level
     * @return int
     */
    protected function _translateLevel($level)
    {
        return (trim($level) !== '' && isset($this->_zendLogLevelToPsr[$level]))
            ? $this->_zendLogLevelToPsr[$level]
            : LogLevel::DEBUG;
    }

    /**
     * Add level key to the meta data array.
     *
     * @return self
     */
    protected function _addLevel()
    {
        if (!isset($this->_metaData['level'])) {
            $lvl = isset($this->_metaData['priority']) ? $this->_metaData['priority'] : Zend_Log::DEBUG;
            $this->_metaData['level'] = strtoupper($this->_translateLevel($lvl));
        }
        return $this;
    }

    /**
     * Add exception information keys to the meta data array.
     *
     * @return self
     */
    protected function _addExceptionInformation()
    {
        if (isset($this->_metaData['exception'])) {
            $exception = $this->_metaData['exception'];
            if ($exception instanceof Exception) {
                $class = $this->_getExceptionClass($exception);
                $this->_addExceptionKeys($class, $exception->getMessage(), $exception->getTraceAsString());
                return $this;
            }
            try {
                $this->_metaData['exception_message'] = (string) $exception;
            } catch (Exception $innerException) {
                $this->_metaData['logger_exception'] = $innerException;
                $this->_metaData['logger_exception_message'] = 'Unable to log exception metadata: not a string or Exception instance.';
            }
            return $this;
        }
        if ($this->_hasExceptionMessage()) {
            $this->_extrapolateExceptionInfo(explode("\nStack trace:\n", $this->_metaData['message']));
        }
        return $this;
    }

    /**
     * Check if log has exception messages.
     *
     * @return bool
     */
    protected function _hasExceptionMessage()
    {
        return (
            isset($this->_metaData['level'])
            && ($this->_metaData['level'] === 'ERROR')
            && (count(explode("\nStack trace:\n", $this->_metaData['message'])) > 1)
        );
    }

    /**
     * @param  string $class
     * @param  string $message
     * @param  string $stacktrace
     * @return self
     */
    protected function _addExceptionKeys($class, $message, $stacktrace)
    {
        $this->_metaData['exception_class'] = $class;
        $this->_metaData['exception_message'] = $message;
        $this->_metaData['exception_stacktrace'] = $stacktrace;
        return $this;
    }

    /**
     * Get the exception class.
     *
     * @param Exception $exception
     * @return string | null
     */
    protected function _getExceptionClass(Exception $exception)
    {
        $stacktrace = $exception->getTrace();
        if ($stacktrace && isset($stacktrace[0]['class'])) {
            return $stacktrace[0]['class'];
        }
        return $exception->getFile();
    }

    /**
     * Extrapolate the exception data from a passed in array.
     *
     * @param array $info
     * @return self
     */
    protected function _extrapolateExceptionInfo(array $info)
    {
        if (!empty($info)) {
            $class = $this->_extractExceptionClass($info);
            $message = $this->_extractExceptionMessage($info);
            $stacktrace =$this->_extractExceptioStacktrace($info);
            $this->_addExceptionKeys($class, $message, $stacktrace);
        }
        return $this;
    }

    /**
     * @param array $info
     * @return string | null
     */
    protected function _extractExceptionClass(array $info)
    {
        $class = null;
        if (count($info) > 1) {
            $trace = explode(': ', $info[1]);
            $data = (count($trace) > 1) ? explode('->', $trace[1]) : [];
            $class = (count($data) > 1) ? $data[0] : $class;
        }
        return $class;
    }

    /**
     * @param array $info
     * @return string | null
     */
    protected function _extractExceptionMessage(array $info)
    {
        return (count($info) > 1) ? str_replace("\n", '', $info[0]) : null;
    }

    /**
     * @param array $info
     * @return string | null
     */
    protected function _extractExceptioStacktrace(array $info)
    {
        return (count($info) > 1) ? $info[1] : null;
    }

    /**
     * Remove any non Distributed Logging Architecture keys from the meta-data.
     *
     * @return self
     */
    protected function _cleanMetaData()
    {
        foreach (['exception', 'priority', 'priorityName'] as $key) {
            if (isset($this->_metaData[$key])) {
                unset($this->_metaData[$key]);
            }
        }
        return $this;
    }

    /**
     * Replace any brace place holder with key value from the passed in data parameter.
     *
     * @return self
     */
    protected function _interpolate()
    {
        $message = $this->_metaData['message'];
        foreach ($this->_metaData as $key => $value) {
            $placeHolder = '{' . $key .'}';
            if (strpos($message, $placeHolder) !== false) {
                // PSR-3 says we MUST NOT throw an exception nor raise any php error, warning or notice.
                $message = @str_replace($placeHolder, $value, $message);
            }
        }
        $this->_metaData['message'] = $message;
        return $this;
    }
}
