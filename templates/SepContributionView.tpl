<div class="crm-accordion-wrapper sep-wrapper">
  <div class="crm-accordion-header sep-header">Someone Else Pays Details</div>
  <div class="crm-accordion-body sep-body">
    <table class="crm-info-panel sep_details_table">
      <thead>
        <tr>
          <th>{ts}Payer{/ts}</th>
          <th>{ts}Beneficiary{/ts}</th>
          <th>{ts}Amount{/ts}</th>
          <th>{ts}Type{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Received{/ts}</th>
          <th>{ts}Invoice ID{/ts}</th>
          <th>{ts}CreditNote ID{/ts}</th>
          <th>&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&context=$context&cid=`$sep_data.payer_id`"}" title="{ts}View payer summary{/ts}">{$sep_data.payer_display_name}</td>
          <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&context=$context&cid=`$sep_data.beneficiary_id`"}" title="{ts}View beneficiary summary{/ts}">{$sep_data.beneficiary_display_name}</td>
          <td>{$sep_data.total_amount|crmMoney:$sep_data.currency}</td>
          <td>{$sep_data.financial_type}</td>
          <td>{$sep_data.contribution_status}</td>
          <td>{$sep_data.receive_date|truncate:10:''|crmDate}</td>
          <td>{$sep_data.invoice_id}</td>
          <td>{$sep_data.creditnote_id}</td>
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
