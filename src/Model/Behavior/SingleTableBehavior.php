<?php

namespace Inheritance\Model\Behavior;

use ArrayAccess;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Single Table Inheritance behavior for CakePHP applications.
 *
 * @author Piotr Chęć
 * @license MIT
 */
class SingleTableBehavior extends Behavior
{

    /**
     * Current class type.
     *
     * @var string
     */
    protected $_type;

    /**
     * Name of the column storing class type.
     *
     * @var string
     */
    protected $_fieldName;

    /**
     * Whether to use class hierarchy or not.
     *
     * @var bool
     */
    protected $_hierarchy;

    /**
     * Initialize method.
     *
     * @param array $config
     */
    public function initialize(array $config)
    {
        if (isset($config['table'])) {
            $this->_table->table($config['table']);
        } 

        if (isset($config['type'])) {
            $this->setType($config['type']);
        } else {
            $this->setType();
        }

        if (isset($config['fieldName'])) {
            $this->_fieldName = $config['fieldName'];
        } else {
            $this->_fieldName = 'type';
        }

        $this->_hierarchy = (isset($config['hierarchy'])
            && ($config['hierarchy'] === false)) ? false : true;
    }

    /**
     * Accessor for the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

     /**
     * Mutator for the type value.
     *
     * @param string|null $type type value.
     * @return string
     */
    public function setType($type = null)
    {
        if ($type !== null) {
            $this->_type = $type;
        }

        if ($this->_type === null) {
            $this->_type = $this->_table->alias();
        }
    }

   /**
     * beforeSave callback
     *
     * @param \Cake\Event\Event $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayAccess $options
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayAccess $options)
    {
        $fieldName = $this->_fieldName;
        $savedType = ($entity->get($fieldName) == '') ? null : $entity->get($fieldName);

        // Changing the type is not possible. It is set only if empty.
        if (is_null($savedType)) {
            $currentType = $this->_formatTypeName();
            $hierarchy = '';
            if ($this->_hierarchy) {
                $parentType = $this->_getParentTypes();
                // Remove leading '|' from $parentType before concatenating.
                $hierarchy = substr($parentType, 1);
            }
            $entity->set($fieldName, $currentType . $hierarchy);
        }
    }

    /**
     * beforeFind callback
     *
     * @param \Cake\Event\Event $event
     * @param \Cake\ORM\Query $query
     * @param \ArrayAccess $options
     */
    public function beforeFind(Event $event, Query $query, ArrayAccess $options)
    {
        $type = $this->_formatTypeName();

        if ($type !== false) {
            $query->where([
                $this->_table->aliasField($this->_fieldName) . ' LIKE'
                    => '%' . $type . '%',
            ]);
        }
    }

    /**
     * beforeDelete callback
     *
     * @param \Cake\Event\Event $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayAccess $options
     */
    public function beforeDelete(Event $event, EntityInterface $entity, ArrayAccess $options)
    {
        $type = $this->_formatTypeName();

        if ($type !== false) {
            $fieldName = $this->_fieldName;

            if ($entity->has($fieldName) && strpos($entity->get($fieldName), $type) === false) {
                $event->stopPropagation();
                return false;
            }
        }
    }

    /**
     * Strips out class path from the front and 'Table' from the end,
     * leaving only the name of the class to be used as the type.
     * Assumes current table class if no argument passed.
     *
     * @param string|bool $class Class name with full path
     * @return string
     */
    protected function _formatTypeName($class = null)
    {
        // false can be passed by get_parent_class() if there is none.
        if ($class === false) {
            return false;
        }
        // Assume current table class if no argument passed.
        if ($class === null) {
            $class = get_class($this->_table);
        }
        // Strip out the class path
        $type = substr(strrchr($class, '\\'), 1);
        // Class names end with 'Table', so strip it out as well.
        $type = substr($type, 0, strpos($type, 'Table'));
        if ($type === '') {
            return false;
        } else {
            return '|' . $type . '|';
        }
    }

    /**
     * Builds a list of parent class names (without 'Table' at the end) to be put
     * into the 'type' field (or equivalent) of the table used by the behavior.
     * This allows parents to find their descendants later.
     *
     * @return string
     */
    protected function _getParentTypes() {
        $i = 0;
        $types = null;
        $parentClass = null;
        $currentClass = $this->_table;
        // Build a list of parent type names, separated by '|'.
        do {
            // Get parent class name.
            $parentClass = get_parent_class($currentClass);
            // Instantinate the class to prepare for the next loop.
            $currentClass = new $parentClass();
            // Format the type name appropriately
            $parent = $this->_formatTypeName($parentClass);
            // Loop ends when the Table class is reached (its name reduced to '||').
            if (!($parent === false)) {
                $types .= $parent;
            }
            // $i is a safety net to end the loop after 20 iterations in case
            // of any bugs.
            // The loop can also end if there are no further class parents.
            $i++;
        } while ($parent != '' and $parent !== false and $i < 20);

        return $types;
    }
}
