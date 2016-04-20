<?php

namespace Ext\AjaxGrid;

/**
 * Base class of ajax grid
 *
 * @package     Ext
 * @subpackage  AjaxGrid
 * @author      ISSArt LLC <contacts@issart.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.issart.com
 */
abstract class BaseAbstract
{
    /**
     * DoctrineRecord class
     * 
     * @var string
     */
    protected $_entityName;

    /**
     * Fields of grid
     * 
     * @var array
     */
    protected $_fields = [];

    /**
     * To override the filters derived from the request
     * 
     * @var array
     */
    protected $_customFilters = [];

    /**
     * @var array
     */
    protected $_gridOptions = [];

    /**
     * Url for getting data
     * 
     * @var string
     */
    protected $_url = null;

    /**
     * Necessary if on the page several grids
     * 
     * @var string
     */
    protected $_namespace = 'default';

    /**
     * Name of ViewHeper for render grid
     * 
     * @var string
     */
    protected $_viewHelper = null;

    /**
     * @var \Zend_View_Interface
     */
    protected $_view;

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    protected $_request;

    /**
     * @var \Zend_Paginator
     */
    protected $_paginator;

    /**
     * @var string
     */
    protected $_sortCallbackName = 'grid_sort';

    public function __construct($options = [])
    {
        $this->_init($options);
    }

    /**
     * @param array $options
     */
    protected function _init($options = [])
    {

    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * @param string $entity
     * @return BaseAbstract
     */
    public function setEntityName($entity)
    {
        $this->_entityName = $entity;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->_entityName;
    }

    /**
     * @param array $filters
     * @return \Doctrine_Query
     */
    public function getQuery($filters = [])
    {
        return \Doctrine::getTable($this->getEntityName())->getListQuery($filters);
    }

    /**
     * Retrieve view object
     *
     * @return \Zend_View_Interface|null
     */
    public function getView()
    {
        if (!$this->_view) {
            $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->_view = $viewRenderer->view;
        }
        return $this->_view;
    }

    /**
     * Retrieve request object
     *
     * @return \Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (!$this->_request) {
            $this->_request = \Zend_Controller_Front::getInstance()->getRequest();
        }
        return $this->_request;
    }

    /**
     * Set view object
     *
     * @param  \Zend_View_Interface $view
     * @return BaseAbstract
     */
    public function setView(\Zend_View_Interface $view = null)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * @return \Zend_Paginator
     */
    public function getPaginator()
    {
        if (!$this->_paginator) {

            $this->_paginator = new \Zend_Paginator(
                new \ZFDoctrine_Paginator_Adapter_DoctrineQuery(
                    $this->getQuery($this->getFilters())
                )
            );
            $this->_paginator->setCurrentPageNumber($this->_getCurrentPage());
            $this->_paginator->setItemCountPerPage($this->_getPerPage());
        }
        return $this->_paginator;
    }

    /**
     * @param array $filters
     * @return BaseAbstract
     */
    public function setCustomFilters($filters = [])
    {
        $this->_customFilters = $filters;
        return $this;
    }

    /**
     * @param string $sortCallbackName
     * @return BaseAbstract
     */
    public function setSortCallbackName($sortCallbackName) 
    {
        $this->_sortCallbackName = $sortCallbackName;
        return $this;
    }

    /**
     * @param array $gridOptions
     * @return BaseAbstract
     */
    public function setGridOptions(array $gridOptions)
    {
        $this->_gridOptions = $gridOptions;
        return $this;
    }

    /**
     * @return array
     */
    public function getGridOptions()
    {
        return $this->_gridOptions;
    }

    /**
     * @param string $url
     * @return BaseAbstract
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (!$this->_url) {
            $this->_url = $this->getView()->url(array('action' => 'list'));
        }
        return $this->_url;
    }

    /**
     * @param string $namespace
     * @return BaseAbstract
     */
    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * @param string $viewHelper
     * @return BaseAbstract
     */
    public function setViewHelper($viewHelper)
    {
        $this->_viewHelper = $viewHelper;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewHelper()
    {
        return $this->_viewHelper;
    }

    public function render()
    {
        $viewHelper = $this->getViewHelper();
        $this->_setFilterOptions();
        return $this->getView()->{$viewHelper}(
            $this->getNamespace(),
            $this->getUrl(),
            $this->getFields(),
            $this->getGridOptions()
        );
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_customFilters
            + array_map('urldecode', $this->getRequest()->getParams())
            + $this->_getSortParams();
    }

    abstract public function sendResponse();

    abstract protected function _getCurrentPage();

    abstract protected function _getPerPage();

    abstract protected function _getSortParams();

    /**
     * @return void
     */
    protected function _setFilterOptions()
    {
        
    }

    /**
     * @param \IteratorAggregate $collection
     * @return type
     */
    protected function _prepareData($collection)
    {
        $fields = $this->getFields();
        $rows = [];
        foreach ($collection->getIterator() as $item) {
            $row = [];
            foreach ($fields as $field) {
                $value = '';
                if (is_array($field)) {

                    /* If param exists */
                    $param = !empty($field['param']) ? $field['param'] : null;
                    /* param */

                    /* If callback exists */
                    if (!empty($field['callback']) 
                        && ($callback = '_' . $field['callback'] . 'Callback')
                        && method_exists($this, $callback)
                    ) {
                        $value = call_user_func_array(
                            [$this, $callback],
                            [$item, $field['name'], $param]
                        );
                    } else {
                        $value = $item->{$field['name']};
                    }
                    /* callback */

                    /* If helper exists */
                    if (!empty($field['helper'])) {
                        $value = $this->getView()->{$field['helper']}($value, $param);
                    }
                    /* helper */

                } else {
                    $value = $this->getView()->escape($item->$field);
                }
                $row[] = $value;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
