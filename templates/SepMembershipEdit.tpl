
<div id="sep_edit" class="crm-accordion-wrapper">
    <div class="crm-accordion-header">{ts}Someone Else Pays{/ts}</div>
    <div class="crm-accordion-body">
        <div class="crm-section">
            <div class="label">{$form.sep_payer_id.label}</div>
            <div class="content">{$form.sep_payer_id.html}</div>
            <div class="clear"></div>
        </div>
        <table class="crm-info-panel sep_details_table">
            <thead>
            <tr>
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
                <td>{$sep_data.total_amount|crmMoney:$sep_data.currency}</td>
                <td>{$sep_data.financial_type}</td>
                <td>{$sep_data.contribution_status}</td>
                <td>{$sep_data.receive_date|truncate:10:''|crmDate}</td>
                <td>{$sep_data.invoice_id}</td>
                <td>{$sep_data.creditnote_id}</td>
            </tr>
            </tbody>
        </table>
    </div>

</div>
</div>

{literal}
    <script type="text/javascript">
        cj(cj('#sep_edit').html()).insertBefore('#customData');
        cj('#contri').remove();
        cj('#sep_edit').remove();
    </script>
{/literal}