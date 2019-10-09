<div class="crm-accordion-wrapper sep-wrapper">
  <div class="crm-accordion-header sep-header">Someone Else Pays Details</div>
  <div class="crm-accordion-body sep-body">
    <table class="crm-info-panel sep_details_table">
      <thead>
        <tr>
          <th>{ts}Payer{/ts}</th>
          <th>{ts}Beneficiary{/ts}</th>
          <th>{ts}Amount{/ts}</th>
          <th>{ts}Event Type{/ts}</th>
          <th>{ts}Title{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Role{/ts}</th>
          <th>{ts}Event Date{/ts}</th>
          <th>&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&context=$context&cid=`$sep_data.payer_id`"}" title="{ts}View payer summary{/ts}">{$sep_data.payer_name}</td>
          <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&context=$context&cid=`$sep_data.beneficiary_id`"}" title="{ts}View beneficiary summary{/ts}">{$sep_data.beneficiary_name}</td>
          <td>{$sep_data.total_amount|crmMoney:$sep_data.currency}</td>
          <td>{$sep_data.event_type}</td>
          <td>{$sep_data.event_title}</td>
          <td>{$sep_data.participant_status}</td>
          <td>{$sep_data.participant_role}</td>
          <td>{$sep_data.event_date|truncate:10:''|crmDate}</td>
          <td>
            <span>{foreach from=$sep_action_links item=action_link}{$action_link}&nbsp;{/foreach}</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

{literal}
  <script type="text/javascript">
    if (!cj('.sep-wrapper').length) {
      cj(cj('.sep-wrapper').html()).insertAfter('.crm-info-panel');
      cj('.sep-wrapper').remove();
    }
  </script>
{/literal}
