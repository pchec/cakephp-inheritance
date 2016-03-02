<?php
namespace Inheritance\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PeopleFixture
 *
 */
class PeopleFixture extends TestFixture
{

    /**
     * Tabke
     *
     * @var string
     */
    public $table = 'people';

    /**
     * Fields
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'uuid'],
        'type' => ['type' => 'string'],
        'first_name' => ['type' => 'string'],
        'last_name' => ['type' => 'string'],
        'email' => ['type' => 'string'],
        'password' => ['type' => 'string'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 'e90f8fb3-4076-4aa5-b626-34b1aabe026d',
            'type' => '|Users|People|',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'test',
        ],
        [
            'id' => '1361a501-c900-4f6f-bce0-d6efed4bf9c1',
            'type' => '|People|',
            'first_name' => 'John',
            'last_name' => 'Adams',
        ],
        [
            'id' => '6c5aefcc-6699-49e1-86be-2aac9d61fb78',
            'type' => '|Clients|People|',
            'first_name' => 'Mary',
            'last_name' => 'Connor',
            'email' => 'mary@example.com',
        ],
    ];
}
