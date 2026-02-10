{if $form.is_membership_template_enabled}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#form-layout-compressed_template_id').insertAfter('.crm-scheduleReminder-form-block-active');
      $('#form-layout-compressed_is_membership_template_enabled').insertAfter('.crm-scheduleReminder-form-block-active');
    });
  </script>
{/literal}

<table id="form-layout-compressed_is_membership_card" class="form-layout-compressed"  style="display: none;">
  <tr id ="form-layout-compressed_is_membership_template_enabled" class="form-layout-compressed">
    <td class="label"></td>
    <td>{$form.is_membership_template_enabled.html} {$form.is_membership_template_enabled.label}</td>
  </tr>
  <tr id="form-layout-compressed_template_id" class="form-layout-compressed">
    <td class="label">{$form.template_id.label}</td>
    <td>{$form.template_id.html}</td>
  </tr>
</table>
{/if}
