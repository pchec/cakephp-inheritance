<?php
namespace Inheritance\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Inheritance\Model\Behavior\SingleTableBehavior;

/**
 * Test classes
 *
 */
class PeopleTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Inheritance.SingleTable');
    }
}

class ClientsTable extends PeopleTable
{
    
}


class UsersTable extends PeopleTable
{
    
}

class Person extends Entity
{

}

class Client extends Person
{

}

class User extends Person
{

}

/**
 * Inheritance\Model\Behavior\SingleTableBehavior Test Case
 */
class SingleTableBehaviorTest extends TestCase
{

    /**
     * @var array
     */
    public $fixtures = [
        'plugin.inheritance.people'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->People = TableRegistry::get('People');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->People);

        parent::tearDown();
    }

    /**
     * Test getType method
     *
     * @return void
     */
    public function testGetType()
    {
        $this->assertEquals('People', $this->People->getType());
    }

    /**
     * Test setType method
     *
     * @return void
     */
    public function testSetType()
    {
        $this->People->type = 'Test';
        $this->assertEquals('Test', $this->People->type);
        $this->People->type = 'People';
    }

    /**
     * Test beforeSave method
     *
     * @return void
     */
    public function testBeforeSave()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test beforeFind method
     *
     * @return void
     */
    public function testBeforeFind()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test beforeDelete method
     *
     * @return void
     */
    public function testBeforeDelete()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
