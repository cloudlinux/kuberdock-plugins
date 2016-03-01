<div class="center" style="margin-top: 10px"><h3>Pods</h3></div>

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
                    <a href="#login-modal" data-toggle="modal" data-target="#restartModal">
                        <button class="btn btn-success pod-restart">Restart</button>
                    </a>
                    <!--<a href="<?php /*printf('kdorder.php?a=addKubes&podId=%s&serviceId=%s', $pod['id'], $service->id)*/?>">
                        <button class="btn btn-default">Upgrade</button>
                    </a>-->
                </td>
            </tr>
        <?php endforeach;?>
    </table>
<?php else:?>
    <div>No pods yet</div>
<?php endif; ?>

<div class="modal fade" id="restartModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    Confirm restarting of application <?php echo $pod['name']?>
                </h4>
            </div>
            <div class="modal-body">
                You can wipe out all the data and redeploy the application or you can just restart and save data in Persistent storages of your application.
            </div>
            <div class="modal-footer">
                <a href="<?php printf('kdorder.php?a=restart&podId=%s&serviceId=%s&wipeOut=1', $pod['id'], $service->id)?>">
                    <button type="button" class="btn btn-danger">Wipe out</button>
                </a>
                <a href="<?php printf('kdorder.php?a=restart&podId=%s&serviceId=%s', $pod['id'], $service->id)?>">
                    <button type="button" class="btn btn-primary">Just restart</button>
                </a>
            </div>
        </div>
    </div>
</div>

<script>

</script>