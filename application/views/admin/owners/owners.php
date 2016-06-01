<div class="row">
    <div class="columns">
        <table class="table table-condensed">
            <tr>
                <th>Name</th><th>Team</th><th>Phone</th>
            </tr>
            <?php foreach ($owners as $owner){ ?>

            <tr>
                <td><?php echo $owner->first_name.' '.$owner->last_name; ?></td>
                <td><?php echo $owner->team_name; ?></td>
                <td><?php echo $owner->phone_number; ?></td>
            </tr>
            <?php }?>
        </table>
    </div>
</div>
