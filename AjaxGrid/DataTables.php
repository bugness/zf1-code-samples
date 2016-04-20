<?php

namespace Ext\AjaxGrid;

/**
 * Class for describing fields of DataTables grid
 * 
 * Example:
 *  protected $_fields = array(
 *      array('name' => 'id'),
 *      array('name' => 'fullname', 'callback' => 'fullname'),
 *      array('name' => 'email'),
 *      array('name' => 'is_active', 'callback' => 'isQuestion', 'helper' => 't'),
 *      array('name' => 'created_at', 'helper' => 'dateTime'),
 *      array('name' => 'actions', 'callback' => 'actions')
 *  );
 *
 *  protected function _exampleCallback(Doctrine_Record $item, $field, $param = null)
 *  {
 *      return $item->{$field};
 *  }
 *
 * @package     Ext
 * @subpackage  AjaxGrid
 * @author      ISSArt LLC <contacts@issart.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.issart.com
 */
class DataTables extends BaseAbstract
{
    /**
     * @var string
     */
    protected $_viewHelper = 'dataTables';

    /**
     * @return void
     */
    public function sendResponse()
    {
        $this->getView()->iTotalRecords = $this->getPaginator()->getTotalItemCount();
        $this->getView()->iTotalDisplayRecords = $this->getPaginator()->getTotalItemCount();
        $this->getView()->aaData = $this->_prepareData($this->getPaginator());
        $this->getView()->sEcho = (int) $this->getRequest()->getParam('sEcho');
    }

    /**
     * @return array
     */
    protected function _getSortParams()
    {
        $sortColumn = $this->_fields[$this->getRequest()->getParam('iSortCol_0', 0)];
        $sortName = !empty($sortColumn) ? (is_array($sortColumn) ? $sortColumn['name'] : $sortColumn) : 'id';
        $sortName = str_replace('.', '_', $sortColumn['name']);
        $sortOrder = $this->getRequest()->getParam('sSortDir_0', 'asc');
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';
        
        if ($this->_isTableCallback($sortName . '_sort') === true) {
            return [
                $sortName . '_sort' => [
                    'field'  => $sortName,
                    'order'  => $sortOrder
                ]
            ];
        }

        return [
            $this->_sortCallbackName => [
                'field'  => $sortName,
                'order'  => $sortOrder
            ]
        ];
    }

    /**
     * @return int
     */
    protected function _getCurrentPage()
    {
        return ceil(($this->getRequest()->getParam('iDisplayStart', 0) + 1) / $this->getRequest()->getParam('iDisplayLength', 10));
    }

    /**
     * @return int
     */
    protected function _getPerPage()
    {
        return $this->getRequest()->getParam('iDisplayLength', 10);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    protected function _isTableCallback($fieldName)
    {
        $filter = new \Zend_Filter_Word_UnderscoreToCamelCase();
        $fieldName = $filter->filter($fieldName);
        return method_exists(
            \Doctrine::getTable($this->getEntityName()),
            '_' . $fieldName . 'Callback'
        );
    }

    /**
     * @param \Doctrine_Record $item
     * @param string $field
     * @param mixed $params
     * @return string - yes or no
     */
    protected function _isQuestionCallback(\Doctrine_Record $item, $field)
    {
        return ($item->{$field} ? 'yes' : 'no');
    }
}
