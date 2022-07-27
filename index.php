<?php require_once('EzCrud.php');

$config = [
    'db' => [
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'db_ezcrud'
    ]
];

$ezcrud = new EzCrud($config);

?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<div class="container-fluid my-3">
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    tb_one
                </div>
                <div class="card-body">
                    <?= $ezcrud->form('tb_one') ?>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-header">
                    tb_two
                </div>
                <div class="card-body">
                    <?= $ezcrud->form('tb_two') ?>
                </div>
            </div>
        </div>
    </div>
</div>