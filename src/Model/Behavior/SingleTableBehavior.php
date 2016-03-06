<?php

namespace Inheritance\Model\Behavior;

use ArrayAccess;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

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
        'type' => null,
    ];

    /**
     * Initialize method. You can pass the following configuration options in an array:
     *
     * - table: Name of the table which is going to store data for all classes.
     *   Defaults to the current class table.
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
    }

    /**
     * Accessor for the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->config('type');
    }

     /**
     * Mutator for the type value.
     *
     * @param string|null $type Type value.
     * @return string
     */
    public function setType($type = null)
    {
        if (!empty($type)) {
            $this->config('type', Inflector::camelize($type));
        } else {
            $type = $this->_trimClassName(get_class($this->_table));
            $this->config('type', $type);
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
        $fieldName = $this->config('field_name');
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
        if ($type) {
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
     * when field determining class type is still empty.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @param string $entity $fieldName
     * @return bool
     */
    protected function _setTypeAllowed(EntityInterface $entity)
    {
        $fieldName = $this->config('field_name');
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
        // Begin the hierarchy with the current class type.
        // It will always be set for descendants of Cake\ORM\Table
        // using this behavior.
        $hierarchy = $this->_formatTypeName();

        // Append ancestor names unless this option has been disabled.
        if ($this->config('hierarchy')) {
            $parentType = $this->_getParentTypes();
            $hierarchy .= $parentType;
        }

        // Trailing delimitter needs to be added to the hierarchy.
        return $hierarchy . '|';
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
        $field = $this->_table->aliasField($this->config('field_name'));
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
            $fieldName = $this->config('field_name');

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
     * @param string|bool $className Class name with full path
     * @return string
     */
    protected function _formatTypeName($className = null)
    {
        // Assume current table class if no argument passed.
        if (empty($className)) {
            $type = $this->getType();
        } else {
            // If function getType does not exist, we have reached
            // the main Table class and need to end.
            //if (!method_exists($className, 'getType')) {
            if ($className == 'Cake\ORM\Table') {
                return false;
            }
            $class = new $className;
            $type = $class->getType();
        }

        $type = $this->_trimClassName($type);

        return '|' . $type;
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
        $currentClass = $this->_table;
        $parentType = null;

        // Build a list of parent type names, separated by '|'.
        while ($parentType !== false and $i < 20) {
            $parentClass = get_parent_class($currentClass);
            $parentType = $this->_formatTypeName($parentClass);
            if ($parentType !== false) {
                $types .= $parentType;
            }
            $currentClass = $parentClass;
            $i++;
        }

        return $types;
    }

    /**
     * Strips leading class path and the word Table from the end.
     *
     * @param string $className
     * @return string
     */
    protected function _trimClassName($className) {
        $trimmedName = $className;

        // Strip out the class path
        $foundAt = strpos($className, '\\');
        if ($foundAt !== false) {
            $trimmedName = substr(strrchr($trimmedName, '\\'), 1);
        }

        // Class names end with 'Table' by convention,
        // so strip it out as well if found.
        $foundAt = strpos($trimmedName, 'Table');
        if ($foundAt !== false) {
            $trimmedName = substr($trimmedName, 0, $foundAt);
        }

        return $trimmedName;
    }
}
