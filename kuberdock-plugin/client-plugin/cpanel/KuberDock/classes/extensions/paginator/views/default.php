<div class="row text-center">
    <nav>
        <ul class="pagination">
            <?php if($page > 1):?>
            <li>
                <a href="<?php echo $_this->getUrl($page-1)?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span> Previous
                </a>
            </li>
            <?php endif;?>
            <li>
                <a href="<?php echo $_this->getUrl($page+1)?>" aria-label="Next">
                    Next <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
</div>