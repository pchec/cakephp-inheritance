# Inheritance plugin for CakePHP

This plugin enables Single Table Inheritance in CakePHP ORM.

## Key Features

This plugin allows you to create descendant classes and store their data in one table. One field is used to determine which class the record belongs to. The plugin has two main modes of operation:
- with class hierarchy
- without class hierarchy

Class hierarchy is built as a concatenated string delimitted by `|` character. It is put into the `type` field together with the current type name. This allows ancestors to be aware of their descendants and interact with them using the methods and properties they both share. This is an important feature and can have numerous applications.

If you don't need that feature, you can disable it and use only basic mode, where ancestors and descendants don't directly interact with each other but simply share methods and properties. This is achieved by having the `type` field contain only the current class name.

To illustrate where class hierarchy might be useful, let's look at an example.

Contact is the base class. Client and Supplier are its children. Client has orders, Supplier has deliveries. All Contacts have addresses. A user with permission to see Contact details can potentially view and correct all the addresses, regardless of which descendant class they belong to. However, from the Contact level there is no access to more specific attributes of the children, like orders or deliveries.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require pchec/Inheritance
```

## Usage

In the base table you intend to use, create the `type` column and make it long, e.g. `varchar(255)`, to give enough room to store more extensivie class hierarchies. By convention, it will be used by the behavior to store the information about the type of the class that the record belongs to. You can use a column with a different name as well, but you will have to define it later in the configuration.

Load the behavior in all the models which are going to use it. Make all the models use the same table name. It is recommend it you name it after the base class.

```
class YourTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('your_table_name');
        $this->addBehavior('Inheritance.SingleTable');

        // Rest of the initialization code...
    }

    // Rest of the code...
}
```

After you have the base class defined, you can create child classes extending it.

```
class YourChildTable extends YourTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('your_table_name'); // Needs to be the same as in the parent
        $this->addBehavior('Inheritance.SingleTable');

        // Rest of the initialization code...
    }

    // Rest of the code...
}
```

## Custom Configuration

You can turn off class hierarchy generation by setting the `hierarchy` option to `false`.
```
$this->addBehavior('Inheritance.SingleTable', [
	'hierarchy' => false,
]);
```

You can use your own field name instead of `type` by passing it in a `fieldName` array key.

```
$this->addBehavior('Inheritance.SingleTable', [
	'fieldName' => 'your_field_name',
]);
```

You can also use another table for the behavior column instead of the table used by the class, by setting the `table` option.

```
$this->addBehavior('Inheritance.SingleTable', [
	'table' => 'your_table_name',
]);
```

By default, the class name `YourClassTable` receives the type of `YourClass`. You can customize that by passing the `type` option.

```
$this->addBehavior('Inheritance.SingleTable', [
	'type' => 'your_type_name',
]);
```
