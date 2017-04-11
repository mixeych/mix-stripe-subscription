<h2>Orders</h2>
<div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
            <div class="meta-box-sortables ui-sortable">
                <form method="post">
                    <?php
                    $table->prepare_items();
                    $table->display(); ?>
                </form>
            </div>
        </div>
    </div>
    <br class="clear">
</div>