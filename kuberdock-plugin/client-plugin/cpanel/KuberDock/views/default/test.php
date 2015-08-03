<div style="margin: 20px 0; border-bottom: solid 1px; width: 100%; text-align: center">Test part</div>

<h1>WHMCS KuberDock products</h1>

<table class="table table-bordered">
    <tr>
        <th>Product Name</th>
        <th>Cube CPU</th>
        <th>Cube Memory</th>
        <th>Cube Disk Usage</th>
        <th>Cube Traffic</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php foreach($data as $id=>$row):
        $description = Tools::parseDescription($kuberProducts[$row['pid']]['description']);
        ?>
        <tr>
            <td><?php echo $kuberProducts[$row['pid']]['name']?></td>
            <td><?php echo $description[0]?></td>
            <td><?php echo $description[1]?></td>
            <td><?php echo $description[2]?></td>
            <td><?php echo $description[3]?></td>
            <td><?php echo $row['status']?></td>
            <td class="left">
                <a href="#">Suspend</a><br>
                <a href="#">UnSuspend</a><br>
                <a href="#">Terminate</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h1 style="margin-top: 10px">WHMCS KuberDock available products</h1>
<table class="table table-bordered">
    <tr>
        <th>Product Name</th>
        <th>Cube CPU</th>
        <th>Cube Memory</th>
        <th>Cube Disk Usage</th>
        <th>Cube Traffic</th>
        <th>Actions</th>
    </tr>

    <?php foreach($kuberProducts as $id=>$row):
        $description = Tools::parseDescription($kuberProducts[$row['pid']]['description']);
        ?>
        <tr>
            <td><?php echo $kuberProducts[$row['pid']]['name']?></td>
            <td><?php echo $description[0]?></td>
            <td><?php echo $description[1]?></td>
            <td><?php echo $description[2]?></td>
            <td><?php echo $description[3]?></td>
            <td>
                <a href="?a=order&pid=<?php echo $row['pid']?>">Order</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>