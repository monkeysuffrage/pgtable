PGTable
A 'Pretty Good' activerecord style db abstraction for php.
=========
Php version >= 5.3.0 is required.
```php
require 'pgtable.php';

class Product extends PGTable{
  public static $table_name = 'products';
}

PGTable::initialize('username', 'password', 'database', 'host');

foreach(Product::all() as $product){
  echo $product->attributes['name'] . "\n";
}
```

Magic methods: these don't need to be defined
```php
$product = Product::find_by_foo_and_bar;
$products = Product::find_all_by_foo_and_bar;
```

Check for duplicate before saving
```php
$product = Product::find_by_unique_id($unique_id);
if(!$product) $product = new Product();
$product->update_attributes(array('name' => 'foo', 'price' => '9.95'));
$product->save();
```

