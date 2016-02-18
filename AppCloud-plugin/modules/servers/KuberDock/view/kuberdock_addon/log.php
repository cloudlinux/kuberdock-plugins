<?php
/**
 * @var $logs array Change log
 * @var $currency \base\models\CL_Currency
 */
?>

<div class="container-fluid">

    <div class="row offset-top">
        <p class="text-right">
        </p>
    </div>

    <div class="row">
        <table id="kubes_table" class="table table-bordered">
            <thead>
            <tr class="active">
                <th>Login</th>
                <th>Time</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($logs as $item): ?>
                <tr>
                    <td><?php echo $item['login']?></td>
                    <td><?php echo $item['time']?></td>
                    <td><?php echo $item['description']?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php echo $paginator->links(); ?>

</div>