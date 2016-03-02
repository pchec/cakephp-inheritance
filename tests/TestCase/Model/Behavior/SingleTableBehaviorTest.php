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
class TestPeopleTable extends Table
{
    public function initialize(array $config)
    {
        $this->table('test_people');
        $this->addBehavior('Inheritance.SingleTable');
    }
}

class TestClientsTable extends TestPeopleTable
{
    public function initialize(array $config)
    {
        $this->table('test_people');
        $this->addBehavior('Inheritance.SingleTable');
    }
}


class TestUsersTable extends TestPeopleTable
{
    public function initialize(array $config)
    {
        //$this->table('people');
        $this->addBehavior('Inheritance.SingleTable', [
            'table' => 'test_people',
            'field_name' => 'type',
            'hierarchy' => false,
        ]);
    }
}

class TestPerson extends Entity
{
}

class TestClient extends TestPerson
{
}

class TestUser extends TestPerson
{
}

/**
 * Inheritance\Model\Behavior\SingleTableBehavior Test Case
 */
class SingleTableBehaviorTest extends TestCase
{
//    use \FriendsOfCake\TestUtilities\AccessibilityHelperTrait;

    /**
     * @var array
     */
    public $fixtures = [
        'plugin.inheritance.test_people'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->People = TableRegistry::get('TestPeople', [
            'entityClass' => 'Inheritance\Test\TestCase\Model\Behavior\TestPerson',
            'className' => 'Inheritance\Test\TestCase\Model\Behavior\TestPeopleTable',
        ]);
        $this->People->addBehavior('Inheritance.SingleTable');

        $this->Clients = TableRegistry::get('TestClients', [
            'entityClass' => 'Inheritance\Test\TestCase\Model\Behavior\TestClient',
            'className' => 'Inheritance\Test\TestCase\Model\Behavior\TestClientsTable',
        ]);
        $this->Clients->addBehavior('Inheritance.SingleTable');

        $this->Users = TableRegistry::get('TestUsers', [
            'entityClass' => 'Inheritance\Test\TestCase\Model\Behavior\TestUser',
            'className' => 'Inheritance\Test\TestCase\Model\Behavior\TestUsersTable',
        ]);
        $this->Users->addBehavior('Inheritance.SingleTable');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->People);
        unset($this->Clients);
        unset($this->Users);
        TableRegistry::clear();

        parent::tearDown();
    }

    /**
     * Test saving TestPerson entity
     *
     * @return void
     */
    public function testBeforeSave()
    {
        // Test saving child class of Table
        $person = $this->People->newEntity();
        $this->assertTrue(empty($person->type));
        $this->People->save($person);
        $this->assertFalse(empty($person->type));
        $this->assertEquals('|TestPeople|', $person->type);

        // Test saving descendant entity with 'hierarchy' == true (default)
        $client = $this->Clients->newEntity();
        $this->assertTrue(empty($client->type));
        $this->Clients->save($client);
        $this->assertFalse(empty($client->type));
        $this->assertEquals('|TestClients|TestPeople|', $client->type);

        // Test saving descendant entity with 'hierarchy' == false
        $user = $this->Users->newEntity();
        $this->assertTrue(empty($user->type));
        $this->Users->save($user);
        $this->assertFalse(empty($user->type));
        $this->assertEquals('|TestUsers|', $user->type);
    }

    /**
     * Test beforeFind method
     *
     * @return void
     */
    public function testBeforeFindOk()
    {
        $results = $this->People->find();
        $this->assertEquals(3, $results->count());

        $results = $this->Clients->find();
        $this->assertEquals(1, $results->count());
    }

    /**
     * Test beforeFind method where records should not be found
     *
     * @return void
     */
    public function testBeforeFindError()
    {
        // Recrod with that ID is a person and a client, but not a user.
        $this->setExpectedException('Cake\Datasource\Exception\RecordNotFoundException');
        $record = $this->Users->get('6c5aefcc-6699-49e1-86be-2aac9d61fb78');
    }

    /**
     * Test beforeDelete method
     *
     * @return void
     */
    public function testBeforeDeleteOk()
    {
        $client = $this->Clients->get('6c5aefcc-6699-49e1-86be-2aac9d61fb78');
        $this->Clients->delete($client);
        $results = $this->Clients->find()->where(['id' => '6c5aefcc-6699-49e1-86be-2aac9d61fb78']);
        $this->assertEquals(0, $results->count());
    }

    /**
     * Test beforeDelete method
     *
     * @return void
     */
    public function testBeforeDeleteError()
    {
        $client = $this->Clients->get('6c5aefcc-6699-49e1-86be-2aac9d61fb78');
        // This client is not a user.
        $this->Users->delete($client);
        $results = $this->Clients->find()->where(['id' => '6c5aefcc-6699-49e1-86be-2aac9d61fb78']);
        $this->assertEquals(1, $results->count());
    }
}
