<?php
global $MIXStripePlansManager;
$plansResponse = $MIXStripePlansManager->getPlans();
    ?>
<a href="javascript:void(0)" id="update-stripe-plans">Update Plans Info</a>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Currency</th>
        <th>Interval</th>
        <th>Amount</th>
        <th>Description</th>
        <th></th>
    </tr>
    <?php foreach($plansResponse as $plan): ?>
    <tr data-id="<?php echo $plan->ID ?>">
        <td><?php echo $plan->plan_id ?></td>
        <td><?php echo $plan->name ?></td>
        <td><?php echo $plan->currency ?></td>
        <td><?php echo $plan->interv ?></td>
        <td><?php echo $plan->amount ?></td>
        <td><textarea class="description" style="width: 300px; height: 150px; resize: none;"><?php echo $plan->description ?></textarea></td>
        <td><button class="change-description">Change</button></td>
    </tr>
    <?php endforeach ?>
</table>
<script>
jQuery("#update-stripe-plans").on('click', function(){
    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: "getStripePlans",
        },
        dataType: 'json',
        success: function(res){
            if(res.success){
                window.location.reload();
            }
        }
    });
});

jQuery(".change-description").on('click', function(){
    var id = jQuery(this).closest("tr").attr("data-id");
    var description = jQuery(this).closest("tr").find(".description").val();
    jQuery.ajax({
        url: ajaxurl,
        type: 'post',
        dataType: 'json',
        data: {
            action: 'updateStripePlan',
            id: id,
            description: description
        },
        success: function(res){
            if(res.success){
                window.location.reload();
            }else{
                alert("somthing went wrong");
            }
        }
    });
});
</script>

