<div class="center" style="margin-top: 10px"><h3>Statistic</h3></div>

<?php if(is_array($stat) && isset($stat['pods_usage'])): ?>
    <table class="client product-info">
        <tr>
            <th>Id</th>
            <th>Kubes</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>

        <?php foreach($stat['pods_usage'] as $pod):
            if(strpos($pod['name'], KuberDock_Hosting::DELETED_POD_SIGN)) continue;
        ?>
        <tr>
            <td><?php echo $pod['id']?></td>
            <td><?php echo $pod['kubes']?></td>
            <td><?php echo $pod['name']?></td>
            <td></td>
        </tr>
        <?php endforeach;?>
    </table>
<?php else: ?>
    <div class="error">
        <?php echo is_array($stat) && !isset($stat['pods_usage']) ? 'Empty' : $stat; ?>
    </div>
<?php endif; ?>