<!--
annotation_list.tpl - Display a list of annotation jobs
Flora Tsai
-->

%include('views/header.tpl')
<!-- Main Content -->
<div class="container">
  <div class="page-header">
    <h2>Annotations List</h2>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Request ID</th>
        <th>Request Time</th>
        <th>VCF File Name</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      %for i in list:
        <tr>
          <th><a href="{{get_url('annotations_list')}}/{{i[0]}}">{{i[0]}}</a></th>
          <th>{{i[1]}}</th>
          <th>{{i[2]}}</th>
          <th>{{i[3]}}</th>
        </tr>
      %end
    </tbody>
  </table>
</div>

</div> <!-- container -->

%rebase('views/base.tpl', title="Annotation List")
