<?php if($pods): ?>
    <table class="datatable product-info">
        <tr>
            <th>Id</th>
            <th>Name</th>
            <th>Images</th>
            <th>Kubes</th>
            <th>Kube type</th>
            <th>Status</th>
        </tr>

        <?php foreach($pods as $pod):
            array_walk($pod['containers'], function($e) use (&$pod) {
                $pod['kubes'] += $e['kubes'];
                $pod['images'][] = $e['image'];
            });
        ?>
        <tr>
            <td><?php echo $pod['id']?></td>
            <td><?php echo $pod['name']?></td>
            <td><?php echo implode(',', $pod['images'])?></td>
            <td><?php echo $pod['kubes']?></td>
            <td><?php echo $kubes[$pod['kube_type']]['kube_name']?></td>
            <td><?php echo $pod['status']?></td>
        </tr>
        <?php endforeach;?>
    </table>
<?php else:?>
    <div>No pods yet</div>
<?php endif; ?>