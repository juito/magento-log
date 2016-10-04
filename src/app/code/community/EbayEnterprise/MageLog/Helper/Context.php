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

/**
 * This class is responsible for building PSR-3 logging context array.
 * The main entry point is EbayEnterprise_MageLog_Helper_Context::getMetaData(), which takes
 * as its first parameter a class name, then an optional array of data and an optional
 * Exception instance.
 */
class EbayEnterprise_MageLog_Helper_Context
{
    const KEY_APP_CONTEXT = 'app_context';
    const KEY_APP_NAME = 'app_name';
    const KEY_DATA_CENTER = 'data_center';
    const KEY_HOST = 'host';
    const KEY_LOG_TYPE = 'log_type';
    const KEY_RESOURCE = 'resource';
    const KEY_RESOURCE_CLASS = 'resource_class';
    const KEY_APP_REQUEST_URL = 'app_request_url';
    const KEY_EXCEPTION_CLASS = 'exception_class';
    const KEY_EXCEPTION_MESSAGE = 'exception_message';
    const KEY_EXCEPTION_STACKTRACE = 'exception_stacktrace';
    const KEY_ROM_REQUEST_URL = 'rom_request_url';
    const KEY_ROM_REQUEST_HEADER = 'rom_request_header';
    const KEY_ROM_REQUEST_BODY = 'rom_request_body';
    const KEY_ROM_RESPONSE_HEADER = 'rom_response_header';
    const KEY_ROM_RESPONSE_BODY = 'rom_response_body';
    const KEY_SESSION_ID = 'session_id';

    const EXCEPTION_LOG_TYPE = 'exception';
    const SYSTEM_LOG_TYPE = 'system';

    /** @var array */
    protected $_metaData = [];
    /** @var EbayEnterprise_MageLog_Helper_Config */
    protected $_config;

    protected function _getConfig()
    {
        if (!$this->_config) {
            $this->_config = Mage::helper('ebayenterprise_magelog/config');
        }
        return $this->_config;
    }

    /**
     * Get logging context meta data.
     * @param  string $className
     * @param  array $data
     * @param  Exception $e
     * @return array
     */
    public function getMetaData($className, array $data=[], Exception $exception=null)
    {
        return array_merge(
            $this->_getRequiredData($className, $data, $exception),
            $this->_getOptionalAppRequestUrl(),
            $this->_getOptionalExceptionData($exception),
            $data,
            $this->_getOptionalSessionData()
        );
    }
    /**
     * @param  string $className
     * @param  array $data
     * @param  Exception $exception
     * @return array
     */
    protected function _getRequiredData($className, array $data=[], Exception $exception=null)
    {
        return [
            static::KEY_APP_CONTEXT => $this->_getAppContext($data),
            static::KEY_APP_NAME => $this->_getConfig()->getAppName(),
            static::KEY_DATA_CENTER => $this->_getDataCenter(),
            static::KEY_HOST => $this->_getHostname(),
            static::KEY_LOG_TYPE => $exception ? static::EXCEPTION_LOG_TYPE : static::SYSTEM_LOG_TYPE,
            static::KEY_RESOURCE => $this->_getResource($className),
            static::KEY_RESOURCE_CLASS => $className,
        ];
    }
    /**
     * @param  string $className
     * @return string
     */
    protected function _getResource($className)
    {
        $data = explode('_', $className);
        $size = count($data);
        return ($size > 1) ? $data[0] . '_' . $data[1] : $className;
    }
    /**
     * @return array
     */
    protected function _getOptionalAppRequestUrl()
    {
        $requestUrl = $this->_getAppRequestUrl();
        return $requestUrl ? [static::KEY_APP_REQUEST_URL => $requestUrl] : [];
    }
    /**
     * @param  Exception $e
     * @return array
     */
    protected function _getOptionalExceptionData(Exception $exception=null)
    {
        return $exception ? [
            static::KEY_EXCEPTION_CLASS => get_class($exception),
            static::KEY_EXCEPTION_MESSAGE => $exception->getMessage(),
            static::KEY_EXCEPTION_STACKTRACE => $exception->getTraceAsString(),
        ] : [];
    }
    /**
     * @param  array $data
     * @return array
     */
    protected function _getAppContext(array $data=[])
    {
        return isset($data[static::KEY_APP_CONTEXT]) ? $data[static::KEY_APP_CONTEXT] : $this->_getConfig()->getAppContext();
    }
    /**
     * @return string
     */
    protected function _getAppRequestUrl()
    {
        return Mage::helper('core/url')->getCurrentUrl();
    }
    /**
     * @return string
     */
    protected function _getDataCenter()
    {
        $dataCenter = $this->_getConfig()->getDataCenter();
        return ($dataCenter !== 'external')
            ? $this->_deriveDataCenter()
            : $dataCenter;
    }
    /**
     * @return string
     */
    protected function _getHostname()
    {
        return Mage::app()->getRequest()->getHttpHost();
    }
    /**
     * @return string
     */
    protected function _deriveDataCenter()
    {
        $hostname = $this->_getHostname();
        $data = explode('.', $hostname);
        $size = count($data);
        return ($size > 1) ? $data[$size - 2] . '.' . $data[$size - 1] : $hostname;
    }
    /**
     * @param  array $data
     * @return array
     */
    protected function _getOptionalSessionData()
    {
        $sessionId = Mage::getSingleton('core/session')->getSessionId();
        return $sessionId ? [static::KEY_SESSION_ID => hash('sha256', $sessionId)] : [];
    }
}
