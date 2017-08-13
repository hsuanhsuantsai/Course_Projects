<!--
profile.tpl - Display user information
Flora Tsai
-->

%include('views/header.tpl')
<!-- Main Content -->
<div class="container">
  <div class="page-header">
    <h2>My Account</h2>
  </div>

  <div class="row">
    <div class="col-sm-4">
      <b>Full Name</b> {{auth.current_user.description}}
      </br>
      <b>Username</b> {{auth.current_user.username}}
      </br>
      <b>Current Subscription Level</b> {{auth.current_user.role}}
      </br>

      %if auth.current_user.role == 'free_user':
        <a href="/subscribe">Upgrade to Premium</a>
      %end
    </div>
  </div>
</div> <!-- container -->

%rebase('views/base.tpl', title="My Account")
