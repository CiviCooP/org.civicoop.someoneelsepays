{literal}
  <script type="text/javascript">
    cj(document).ready(function() {
      cj('.crm-membershiprenew-form-block-soft-credit-type').hide();
      cj('.helpicon').each(function() {
        if (this.title === "Contributor Help") {
          cj(this).hide();
        }
      });
    });
  </script>
{/literal}