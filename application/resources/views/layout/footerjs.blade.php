<!--ALL THIRD PART JAVASCRIPTS-->
<script src="public/vendor/js/vendor.footer.js?v={{ config('system.versioning') }}"></script>

<!--nextloop.core.js-->
<script src="public/js/core/ajax.js?v={{ config('system.versioning') }}"></script>

<!--MAIN JS - AT END-->
<script src="public/js/core/boot.js?v={{ config('system.versioning') }}"></script>

<!--EVENTS-->
<script src="public/js/core/events.js?v={{ config('system.versioning') }}"></script>

<!--CORE-->
<script src="public/js/core/app.js?v={{ config('system.versioning') }}"></script>

<!--BILLING-->
<script src="public/js/core/billing.js?v={{ config('system.versioning') }}"></script>

 <script>
  $(document).on("click", ".modal-body", function () {
    //   $("#reminder_date").datepicker({
    //     minDate: 0,
    //     onSelect: function(date) {
    //    $("#reminder_date").datepicker('option', 'minDate', date);
    //  }                                   
    // });
    $('#reminder_date').datepicker({ 

      startDate: new Date()

    }); 
  });

</script> 
@if(@config('visibility.projects_d3_vendor'))
<script src="public/vendor/js/d3/d3.min.js?v={{ config('system.versioning') }}"></script>
<script src="public/vendor/js/c3-master/c3.min.js?v={{ config('system.versioning') }}"></script>
@endif