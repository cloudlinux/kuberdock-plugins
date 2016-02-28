<div class="center" style="margin-top: 10px"><h3>Statistic</h3></div>

<?php if($pods): ?>
    <table class="client product-info">
        <tr>
            <th>Name</th>
            <th>Images</th>
            <th>Kubes</th>
            <th>Kube type</th>
            <th>Price</th>
            <th>Next due date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php foreach($pods as $pod):
            // TODO: relations
            $billableItem = null;
            if(isset($items[$pod['id']])) {
                $billableItem = \base\models\CL_BillableItems::model()->loadById($items[$pod['id']]['billable_item_id']);
            }
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
                <td>
                    <?php echo $billableItem ? $currency->getFullPrice($billableItem->amount) . ' \ ' . $product->getReadablePaymentType() : ''?>
                </td>
                <td>
                    <?php echo $billableItem ? \base\CL_Tools::getFormattedDate(\base\CL_Tools::sqlDateToDateTime($billableItem->duedate)) : ''?>
                </td>
                <td><?php echo $pod['status']?></td>
                <td>
                    <a href="<?php printf('kdorder.php?a=addKubes&podId=%s&serviceId=%s', $pod['id'], $service->id)?>">
                        <button class="btn btn-default">Upgrade</button>
                    </a>
                </td>
            </tr>
        <?php endforeach;?>
    </table>
<?php else:?>
    <div>No pods yet</div>
<?php endif; ?>