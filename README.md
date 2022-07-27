# EzCrud

What? _CRUD_ can be easy like this? Try out

## Recruitments

- Bootstrap 5
- Php 7

## Documentation

1. Include the EzCrud to projects

    ```php
    require_once('EzCrud.php');
    ```

2. Initialize EzCrud Class

    ```php
    $config = [
        'db' => [
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'dbname'
        ]
    ];

    $ezcrud = new EzCrud($config);
    ```

3. Usage
    - For The Handler

        ```php
        $ezcrud->handler('table_name');
        ```

    - For the form

        ```php
        $ezcrud->form('table_name');
        ```

4. Done
