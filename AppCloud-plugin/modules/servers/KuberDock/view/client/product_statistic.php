<div class="center" style="margin-top: 10px"><h3>Statistic</h3></div>

<?php if($pods): ?>
    <table class="client product-info">
        <tr>
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
                <td>
                    <a href="<?php echo $service->getLoginByTokenLink() . '&next=#pods/' . $pod['id'];?>" target="_blank">
                        <?php echo $pod['name']?>
                    </a>
                </td>
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