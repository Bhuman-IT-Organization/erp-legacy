<script type="text/javascript">
    $(function(){
        'use strict';

        var quality_team_params = {
            "user_for_filter": "[name='user_for_filter']",
        };
        var quality_team_table = $('table.table-quality_team_table');
        var _table_api = initDataTable(quality_team_table, admin_url+'quality/team_member_table', [0], [0], quality_team_params, ['2', 'asc']);
        var hidden_columns = [0];
        $('.table-quality_team_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(quality_team_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-quality_team_table').DataTable().ajax.reload();
            });
        });
        
        $( document ).ready(function() {

            $('#add_edit_quality_team').appFormValidator({
                rules: {
                    team_member:'required',
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "quality/quality_team_days_off_exists",
                            type: 'post',
                            data: {
                                id: function() {
                                    return $('input[name="id"]').val();
                                },
                                name: function() {
                                    return $('input[name="name"]').val();
                                }
                            }
                        }
                    }
                },
                onSubmit: manage_quality_team,
                messages: {
                    name: '<?php echo _l("quality_quality_team_already_exists"); ?>',
                },
            });

          });

        $('#quality_team').on('hidden.bs.modal', function(event) {
            'use strict';
            
            $('#quality_team input[name="name"]').val('');
            $('#quality_team select[name="team_member"]').val('').change();

            $('#quality_team #quality_team_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_quality_team(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-quality_team_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#quality_team').modal('hide');
        });
        return false;
    }

    function new_quality_team(){
        'use strict';

        $('#quality_team').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_quality_team(invoker,id){
        'use strict';

        var name = $(invoker).data('name');
        var team_member = $(invoker).data('team_member');

        $('#quality_team_additional').append(hidden_input('id',id));
        $('#quality_team input[name="name"]').val(name);
        $('#quality_team select[name="team_member"]').val(team_member).change();

        $('#quality_team').modal('show');
        $('.add-title').addClass('hide');
    }
</script>