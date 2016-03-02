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
     * Default configuration
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'field_name' => 'type',
        'hierarchy' => true,
    ];

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
     * Initialize method. You can pass the following configuration options in an array:
     *
     * - table: Name of the table which is going to store data for all classes.
     * - field_name: Name of the column storing class type. Defaults to 'type'.
     * - hierarchy: Whether to save class hierarchy, making parents aware of their
     *   descendants. Defaults to true.
     * - type: Name to be used as type value. Defaults to class name without the 'Table'
     *   word, i.e. MyClassTable becomes MyClass.
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

        if (isset($config['field_name'])) {
            $this->_fieldName = $config['field_name'];
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
        if ($this->_setTypeAllowed($entity)) {
            $classHierarchy = $this->_getClassHierarchy($entity);
            $entity->set($fieldName, $classHierarchy);
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
            $query->where($this->_getQueryCondition($type));
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
        if ($this->_deleteAllowed($entity)) {
            return true;
        } else {
            $event->stopPropagation();
            return false;
        }
    }

    /**
     * Checks if class type field can still be set. Setting it is only possible
     * when $_fieldName determining class type is still empty.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @param string $entity $fieldName
     * @return bool
     */
    protected function _setTypeAllowed(EntityInterface $entity)
    {
        $fieldName = $this->_fieldName;
        return !empty($entity->$fieldName) ? false : true;
    }

    /**
     * Gets full class hierarchy as a string separated by pipes (|).
     * If 'hierarchy' option has been set to false in the config
     * then returns only the current class, without ancestors.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return string
     */
    protected function _getClassHierarchy(EntityInterface $entity)
    {
        $fieldName = $this->_fieldName;
        $currentType = $this->_formatTypeName();
        if ($currentType === false) {
            return null;
        }
        $hierarchy = $currentType;
        // Append ancestor names unless this option has been disabled.
        if ($this->_hierarchy) {
            $parentType = $this->_getParentTypes();
            // Remove leading '|' from $parentType before concatenating.
            $hierarchy .= substr($parentType, 1);
        }

        return $hierarchy;
    }

    /**
     * Returns query condition selecting all records with the particular
     * class type in its hierarchy.
     *
     * @param string $type Class type enclosed by pipes: |ClassType|
     * @return array For example: ['type LIKE' => '%|ClassType|%']
     */
    protected function _getQueryCondition($type)
    {
        $field = $this->_table->aliasField($this->_fieldName);
        $condition = [$field . ' LIKE' => '%' . $type . '%'];

        return $condition;
    }

    /**
     * Checks if the entity can be deleted.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    protected function _deleteAllowed(EntityInterface $entity)
    {
        $type = $this->_formatTypeName();

        if ($type !== false) {
            $fieldName = $this->_fieldName;

            if ($entity->has($fieldName) && strpos($entity->get($fieldName), $type) === false) {
                return false;
            }
        }

        return true;
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
        if ($type == '') {
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
            // Format the type name appropriately
            $parent = $this->_formatTypeName($parentClass);
            // Loop ends when the Table class is reached (its name reduced to '||').
            if (!($parent === false)) {
                $types .= $parent;
                // Instantinate the class to prepare for the next loop.
                $currentClass = new $parentClass();
            }
            // $i is a safety net to end the loop after 20 iterations in case
            // of any bugs.
            // The loop can also end if there are no further class parents.
            $i++;
        } while ($parent != '' and $parent !== false and $i < 20);

        return $types;
    }
}
