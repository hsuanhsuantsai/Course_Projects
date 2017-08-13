<!--
subscribe.tpl - Get user's credit card details to send to Stripe service
Copyright (C) 2011-2017 Vas Vasiliadis <vas@uchicago.edu>
University of Chicago
-->

%include('views/header.tpl')
<!-- Captures the user's credit card information and uses Javascript to send to Stripe -->

<div class="container">
	<div class="page-header">
		<h2>Subscribe</h2>
	</div>

	<p>You are subscribing to the GAS Premium plan. Please enter your credit card details to complete your subscription.</p><br />

	<form role="form" action="/subscribe" method="post" id="subscribe_form" name="subscribe_submit">
	  <div class="form-group">
	    <label for="name">Name on credit card</label>
	    <input class="form-control input-lg required" type="text" size="20" data-stripe="name" />
	  </div>
	  <div class="form-group">
	    <label for="number">Credit card number</label>
	    <input class="form-control input-lg required" type="text" size="20" data-stripe="number" />
	  </div>
	  <div class="form-group">
	    <label for="cvc">Credit card verification code</label>
	    <input class="form-control input-lg required" type="text" size="20" data-stripe="cvc" />
	  </div>
	  <div class="form-group">
	    <label for="exp-month">Credit card expiration month</label>
	    <input class="form-control input-lg required" type="text" size="20" data-stripe="exp-month" />
	  </div>
	  <div class="form-group">
	    <label for="exp-year">Credit card expiration year</label>
	    <input class="form-control input-lg required" type="text" size="20" data-stripe="exp-year" />
	  </div>
	  <!-- <button type="submit" class="btn btn-default">Submit</button> -->
	  <div class="form-actions">
	  	<input id="bill-me" class="btn btn-lg btn-primary" type="submit" value="Subscribe">
	  </div>
	</form>


</div> <!-- container -->

%rebase('views/base', title='GAS - Subscribe')