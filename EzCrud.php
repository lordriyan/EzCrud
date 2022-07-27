<?php

class EzCrud
{

    // Configurations variables
    public $config = [
        'db' => [
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'db_ezcrud'
        ]
    ];

    // Database connection object
    public $db;

    // HTML Input type constants (for field type)
    public $inputType = [
        'number' =>     ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'float', 'double', 'decimal'],
        'text' =>       ['char', 'varchar', 'tinytext'],
        'textarea' =>   ['text', 'mediumtext', 'longtext'],
        'date' =>       ['date', 'datetime', 'timestamp', 'time', 'year'],
        'select' =>     ['enum', 'set']
    ];

    /**
     * Constructor
     * @param array $config
     */
    public function __construct($config = [])
    {
        // Save config to public variable
        $this->config = $config;

        // Connect to database using config and save to public variable
        $this->db = mysqli_connect(
            $this->config['db']['hostname'],
            $this->config['db']['username'],
            $this->config['db']['password'],
            $this->config['db']['database']
        );
    }

    /**
     * Generate HTML form for table with given name
     * @param string $table
     * @return string
     */
    public function form($tableName = false, $action = 'create')
    {
        // Return error message if no table name is provided
        if (!$tableName) return '<h1>No table name provided on form</h1>';

        // Set handle for table name
        $this->handler($tableName);

        // Get table columns
        $columns = $this->getColumns($tableName);

        // Generate form HTML
        $form = '<form action="" method="post">';
        foreach ($columns as $value) {
            $form .= '<input type="hidden" name="ezcrud_action" value="' . $action . '">';
            $form .= '<input type="hidden" name="ezcrud_table" value="' . $tableName . '">';
            // $form .= '<input type="hidden" name="ezcrud_token" value="' . $token . '">';
            $form .= '<div class="form-group mb-3">';
            $form .= '<label class="mb-2" for="' . $value['field'] . '">' . $value['name'] . '</label>';
            $form .= $this->generateInput($value);
            $form .= '</div>';
        }
        $form .= '<button type="submit" class="btn btn-primary mt-3">Save</button>';
        $form .= '</form>';

        // Return form HTML
        return $form;
    }

    /**
     * Generate form request handler
     * @param string $table
     * @return void
     */
    public function handler($tableName = false)
    {
        // Return error message if no table name is provided
        if (!$tableName) return '<h1>No table name provided on handler</h1>';

        // Check if form is submitted
        if (!isset($_POST['ezcrud_table'])) return;

        // Parse form data
        $action = $_POST['ezcrud_action'];
        $table = $_POST['ezcrud_table'];
        // $token = $_POST['ezcrud_token'];
        $data = $_POST;
        unset($data['ezcrud_token']);
        unset($data['ezcrud_action']);
        unset($data['ezcrud_table']);

        switch ($action) {
            case 'create':
                // Create new record
                $this->db->query("INSERT
                                    INTO $table (" . implode(',', array_keys($data)) . ")
                                  VALUES ('" . implode("','", array_values($data)) . "')");

                break;
            case 'update':
                // Update record

                break;
            default:
                echo '<h1>Invalid action</h1>';
                break;
        }
    }

    /**
     * Generate HTML input for given column
     * @param array $column
     * @return string
     */
    public function generateInput($value)
    {
        $input = '';
        switch ($value['input']) {
            case 'number':
                $input .= '<input type="number" class="form-control" id="' . $value['field'] . '" name="' . $value['field'] . '" ' . ($value['required'] ? 'required' : '') . '>';
                break;
            case 'text':
                $input .= '<input type="text" class="form-control" id="' . $value['field'] . '" name="' . $value['field'] . '" ' . ($value['required'] ? 'required' : '') . '>';
                break;
            case 'textarea':
                $input .= '<textarea class="form-control" id="' . $value['field'] . '" name="' . $value['field'] . '" ' . ($value['required'] ? 'required' : '') . '></textarea>';
                break;
            case 'date':
                $input .= '<input type="date" class="form-control" id="' . $value['field'] . '" name="' . $value['field'] . '" ' . ($value['required'] ? 'required' : '') . '>';
                break;
            case 'select':
                $input .= '<select class="form-control" id="' . $value['field'] . '" name="' . $value['field'] . '" ' . ($value['required'] ? 'required' : '') . '>';
                if (!$value['required']) {
                    $input .= '<option value="">-</option>';
                }
                foreach ($value['options'] as $x) {
                    if ($value['isForeignKey']) {
                        $input .= '<option value="' . $x['value'] . '">' . $x['value'] . ' - ' . $x['display'] . '</option>';
                    } else {
                        $input .= '<option value="' . $x['value'] . '">' . $x['display'] . '</option>';
                    }
                }
                $input .= '</select>';
                break;
            default:
                $input .= '<input type="text" class="form-control" id="' . $value['field'] . '" name="' . $value['field'] . '" ' . ($value['required'] ? 'required' : '') . '>';
                break;
        }
        return $input;
    }

    /**
     * Get table columns
     * @param string $tableName
     * @return array
     */
    private function getColumns($tableName)
    {
        $sql = "SELECT *
                  FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_NAME = N'$tableName'";

        $sql = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        $columns = [];
        foreach ($sql as $v) {
            $isForeignKey = $v['COLUMN_KEY'] == 'MUL';

            $field = $v['COLUMN_NAME'];
            $data_type = $v['DATA_TYPE'];
            $name = $this->normalizeFieldName($field);
            $field_length = $v['CHARACTER_MAXIMUM_LENGTH'] ?? $v['NUMERIC_PRECISION'];
            $extra = $v['EXTRA'] ?? false;
            $input = ($isForeignKey) ? 'select' : $this->htmlInputType($data_type);
            $length = $this->fieldLength($input, $field_length);
            $options = false;
            $required = $v['IS_NULLABLE'] == 'NO';

            if ($input == 'select') {
                $options = ($isForeignKey)
                    ? $this->getOptionsFromTableReference($tableName, $field)
                    : $this->getOptionsFromDataType($field, $v['COLUMN_TYPE']);
            }

            $columns[] = [
                'field' => $field,
                'name' => $name,
                'data_type' => $data_type,
                'input' => $input,
                'length' => $length,
                'extra' => $extra,
                'options' => $options,
                'required' => $required,
                'isForeignKey' => $isForeignKey
            ];
        }

        return $columns;
    }

    /**
     * Get options from table reference
     * @param string $tableName
     * @param string $field
     * @return array
     */
    private function getOptionsFromTableReference($tableName, $fieldName)
    {
        // Get table name belong to field name
        $for_name = $this->config['db']['database'] . '/' . $tableName;

        $sql = "SELECT REF_NAME, REF_COL_NAME
                  FROM INFORMATION_SCHEMA.INNODB_SYS_FOREIGN f
                LEFT
                  JOIN INFORMATION_SCHEMA.INNODB_SYS_FOREIGN_COLS fc
                    ON f.ID = fc.ID
                 WHERE f.FOR_NAME = '$for_name'
                   AND fc.FOR_COL_NAME = '$fieldName'";

        $sql = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC)[0];

        $tableReference = [
            'table' => explode('/', $sql['REF_NAME'])[1],
            'field' => $sql['REF_COL_NAME']
        ];

        $sql = "SELECT *
                  FROM " . $tableReference['table'];

        $sql = $this->db->query($sql)->fetch_all(MYSQLI_NUM);

        $options = [];
        foreach ($sql as $v) {
            $options[] = [
                'value' => $v[0],
                'display' => $v[1]
            ];
        }

        return $options;
    }

    /**
     * Parse data type to get options
     * @param string $dataType
     * @return array
     */
    private function getOptionsFromDataType($field, $data_type)
    {
        $x = explode('(', $data_type);
        $y = explode(')', $x[1]);
        $z = explode(',', $y[0]);
        $o = [];
        foreach ($z as $v)
            $o[] = [
                'value' => substr($v, 1, -1),
                'display' => substr($v, 1, -1)
            ];
        return $o;
    }

    /**
     * Normalize field name
     * @param string $fieldName
     * @return string
     */
    private function normalizeFieldName($fieldName)
    {
        return ucwords(str_replace('_', ' ', $fieldName));
    }

    /**
     * Get HTML input type
     * @param string $fieldType
     * @return string
     */
    private function htmlInputType($fieldType)
    {
        foreach ($this->inputType as $type => $types)
            if (in_array($fieldType, $types)) return $type;
        return 'text';
    }

    /**
     * Get length of field
     * @param string $inputType
     * @return int
     */
    private function fieldLength($inputType, $len)
    {
        if ($inputType == 'select') return 0;
        if ($inputType == 'number') {
            $n = '';
            for ($i = 0; $i < $len; $i++) $n .= '9';
            return intval($n);
        } else {
            return intval($len);
        }
    }
}
