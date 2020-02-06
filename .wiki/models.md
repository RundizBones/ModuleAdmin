# Models

Model is not required for all modules. Model files should be in **Models** folder of your module. Example.

```
ModuleName/
    Models/
        MyModel.php
```

Any model files should extends `\System\Core\Models\BaseModel` to access the database class (`\System\Libraries\Db`) easily via `Db` property.<br>
RundizBones is using PDO as main database component to connect with MariaDB, MySQL server.

Example:

```php
<?php

namespace Modules\ModuleName\Models;

class MyModel extends \System\Core\Models\BaseModel
{
    public function getData()
    {
        $sql = 'SELECT `id`, `name` FROM `' . $this->Db->tableName('customers') . '` ORDER BY `id` LIMIT 0, 1';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();

        return $result;
    }
}
```

To access your model just use auto load feature. Example.

```php
<?php
// in your controller.
$MyModel = new \Modules\ModuleName\Models\MyModel($this->Container);
$result = $MyModel->getData();
print_r($result);
```