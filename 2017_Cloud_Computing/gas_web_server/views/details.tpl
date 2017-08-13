<!--
details.tpl - Display details of a specific annotation job
Flora Tsai
-->

%include('views/header.tpl')
<!-- Main Content -->
<div class="container">
  <div class="page-header">
    <h2>Annotation Details</h2>
  </div>

  <div class="row">
      <b>Request ID:</b> {{job_id}}
      </br>
      <b>Request Time:</b> {{submit_time}}
      </br>
      <b>VCF Input File:</b> <a href="{{input_url}}">{{input_file_name}}</a>
      </br>
      <b>Status:</b> {{job_status}}
      </br>

      %if job_status == 'COMPLETED':
        <b>Complete Time:</b> {{complete_time}}
        </br>
        <b>Annotated Results File:</b> 
        %if timeout and (auth.current_user.role == 'free_user'):
          <a href="/subscribe">upgrade to Premium for download</a>
        %else:
          <a href="{{signed_url}}">download</a>
        %end
        </br>
        <b>Annotation Log File:</b> <a href="/annotations/{{job_id}}/log">view</a>
        </br>
      %end
      
      <a href="/annotations" target="_blank">&larr;back to annotations list </a>

  </div>
</div> <!-- container -->

%rebase('views/base.tpl', title="Job Details")
