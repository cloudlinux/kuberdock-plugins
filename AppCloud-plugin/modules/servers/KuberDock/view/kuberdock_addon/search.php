<div class="search-block<?php !$search ? ' hidden' : ''?>">
    <form class="form-inline search-form" method="post">
        <div class="form-group">
            <label for="product_id">Package</label>
            <select class="form-control" id="product_id" name="Search[product_id]">
                <option value="">All</option>
            <?php foreach($products as $row):?>
                <option value="<?php echo $row['id']?>"<?php echo (isset($search['product_id']) && $search['product_id'] == $row['id']) ? ' selected' : ''?>>
                    <?php echo $row['name']?>
                </option>
            <?php endforeach;?>
            </select>
        </div>
        <!--<div class="checkbox">
            <label>
                <input type="checkbox" name="kube_type"> Standart
            </label>
        </div>-->
        <button type="submit" class="btn btn-default">Search</button>
    </form>
</div>

<p class="lead text-right"><span class="search-show">Toggle search</span></p>