@extends('pages.settings.ajaxwrapper')
@section('settings-page')
<!-- action buttons -->
@include('misc.list-pages-actions')
<!-- action buttons -->

<!--heading-->
@include('pages.settings.sections.leads.table.table')
<div>
    <!--settings documentation help-->
    <a href=""  target="_blank" class="btn btn-sm btn-info  help-documentation"><i class="ti-info-alt"></i> {{ cleanLang(__('lang.help_documentation')) }}</a>
</div>

@endsection