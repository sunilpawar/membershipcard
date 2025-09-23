{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('.crm-membership-type-form-block-template_id').insertAfter('.crm-membership-type-form-block-period_type');
    });
  </script>
{/literal}

{crmScope extensionKey='com.skvare.inventory'}
  <table class="form-layout-compressed" style="display: none">
    <tr class="crm-membership-type-form-block-template_id">
      <td class="label">{$form.template_id.label}</td>
      <td>{$form.template_id.html}<br/>
        <span class="description">
          {ts}Choose the membership card template.{/ts}
        </span>
      </td>
    </tr>
  </table>
{/crmScope}
