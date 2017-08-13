<!--
job_log.tpl - Display the log file for an annotation job
Flora Tsai
-->

%include('views/header.tpl')
<!-- Main Content -->
<div class="container">
  <div class="page-header">
    <h2>Annotation Log File</h2>
  </div>

  <div class="row">
      {{log_res}}
  </div>
</div> <!-- container -->

%rebase('views/base.tpl', title="Job log file")
