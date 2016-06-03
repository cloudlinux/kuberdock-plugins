<div class="form-group">
    <label for="<?php echo $variable?>" class="col-sm-12"><?php echo $data['description']?></label>

    <div class="col-sm-5">
        <select name="<?php echo $variable?>" id="<?php echo $variable?>">
            <?php foreach($data['data'] as $k => $r):?>
                <option value="<?php echo $r['id']?>" data-pid="<?php echo $r['product_id']?>"<?php echo ($r['id'] == $data['default']) ? ' selected' : ''?>>
                    <?php echo $r['name']?>
                </option>
            <?php endforeach;?>
        </select>
    </div>
</div>