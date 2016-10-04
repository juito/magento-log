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

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Provide self-contained logging methods that can easily be overridden and mocked.
 *
 * @category  EbayEnterprise
 * @package   EbayEnterprise_MageLog
 * @copyright Copyright (c) 2014 eBay Enterprise (http://ebayenterprise.com)
 */
class EbayEnterprise_MageLog_Helper_Data implements LoggerInterface
{
    use LoggerTrait;

    /** @var EbayEnterprise_MageLog_Model_Logger */
    protected $_logger;
    /** @var EbayEnterprise_Eb2cCore_Helper_Context */
    protected $_context;
    /** @var array $_psrLogLevelToZend */
    protected $_psrLogLevelToZend = [
        LogLevel::EMERGENCY  => Zend_Log::EMERG,
        LogLevel::ALERT      => Zend_Log::ALERT,
        LogLevel::CRITICAL   => Zend_Log::CRIT,
        LogLevel::ERROR      => Zend_Log::ERR,
        LogLevel::WARNING    => Zend_Log::WARN,
        LogLevel::NOTICE     => Zend_Log::NOTICE,
        LogLevel::INFO       => Zend_Log::INFO,
        LogLevel::DEBUG      => Zend_Log::DEBUG,
    ];

    /**
     * Stash the instance of 'ebayenterprise_magelog/context' helper into
     * the '_context' class property.
     * @return EbayEnterprise_Eb2cCore_Helper_Context
     */
    public function getContext()
    {
        if (!$this->_context) {
            $this->_context = Mage::helper('ebayenterprise_magelog/context');
        }
        return $this->_context;
    }
    /**
     * Stash the instance of 'ebayenterprise_magelog/log' model into
     * the '_logger' class property.
     * @return EbayEnterprise_MageLog_Model_Logger
     */
    protected function _getLogger()
    {
        if (!$this->_logger) {
            $this->_logger = Mage::getModel('ebayenterprise_magelog/logger');
        }
        return $this->_logger;
    }
    /**
     * Log an exception object to the exception.log file.
     *
     * @param  Exception $e any exception
     * @param  array     $context
     * @return self
     */
    public function logException(Exception $e, array $context=[])
    {
        $this->_getLogger()->logException($e, $this->_normalizeContext($context));
        return $this;
    }
    /**
     * @see    Mage::log()
     * @param  int    $level The level Log Priority (based on Zend_Log levels)
     * @param  string $message
     * @param  array  $context
     * @return self
     */
    public function log($level, $message, array $context=[])
    {
        $this->_getLogger()->log($message, $this->_translateLevel($level), '', false, $this->_normalizeContext($context));
        return $this;
    }
    /**
     * Translate PSR-3 Log level back to Zend_Log level.
     * @param string $level
     * @return int
     */
    protected function _translateLevel($level)
    {
        return (trim($level) !== '' && isset($this->_psrLogLevelToZend[$level]))
            ? $this->_psrLogLevelToZend[$level]
            : Zend_Log::DEBUG;
    }
    /**
     * Ensure all the none primitive values in the context meta data array
     * is converted properly into string value.
     *
     * @param  array
     * @return array
     */
    protected function _normalizeContext(array $context)
    {
        $normalContext = [];
        foreach ($context as $key => $value) {
            $normalContext[$key] = $this->_stringifyValue($value);
        }
        return $normalContext;
    }
    /**
     * Handle none primitive values.
     *
     * @param  mixed
     * @return string
     */
    protected function _stringifyValue($value)
    {
        switch (gettype($value)) {
            case 'array':
                return @json_encode($value);
            case 'resource':
                return 'resource';
            case 'object':
                return  method_exists($value, '__toString') ? (string) $value : get_class($value);
        }
        return $value;
    }
}
