<!DOCTYPE html>
<html>
	<head>
		<title>Order</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<style>
			table, td, th {
				border: 1px solid black;
			}
			th, td {
				padding: 5px;
				text-align: center;
			}
		</style>
	</head>
	<body>
<?php
function input_sanitization($data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}

$host = 'mpcs53001.cs.uchicago.edu';
$username = '';
$password = '';
$database = $username.'DB';

// Connect to db
$conn = mysqli_connect($host, $username, $password, $database)
	or die('Could not connect ' . mysqli_connect_error());

// Input parameters
if (isset($_REQUEST['firstName']))
	$firstname = input_sanitization($_REQUEST['firstName']);
else
	$firstname = '';

if (isset($_REQUEST['lastName']))
	$lastname = input_sanitization($_REQUEST['lastName']);
else
	$lastname = '';

if (isset($_REQUEST['esign']))
	$esign = input_sanitization($_REQUEST['esign']);
else
	$esign ='';


if (isset($_REQUEST['orderSubmit'])) {
	if (array_key_exists('h1', $_REQUEST)) {
		$_order = $_REQUEST['h1'];
		$dishID = $_REQUEST['dishID'];
		$qty = $_REQUEST['quantity'];

		$query = "INSERT INTO OrderMenuItems (orderID, menuItemID, quantity)
					VALUES
					($_order, $dishID, $qty)";

		
		$result = mysqli_query($conn, $query)
			or die('Dish order failed ' . mysqli_error());
		print "<h4>Dish ID: $dishID, Quantity: $qty added to Order ID: $_order successfully!</h4>";
		print "<form method=\"post\" >
					<b>Dish ID</b>
					<input type=\"number\" name=\"dishID\" min=\"1\" required>
					<b>Quantity</b>
					<input type=\"number\" name=\"quantity\" min=\"1\" required>
					<input type=\"hidden\" name=\"firstName\" value=\"$firstname\">
					<input type=\"hidden\" name=\"lastName\" value=\"$lastname\">
					<input type=\"hidden\" name=\"h1\" value=\"$_order\">
					<input type=\"submit\" name=\"orderSubmit\" value=\"Order!\">
				</form>";

		mysqli_free_result($result);
	}
}
else {

	// Check if there's an existing account in the database
	$query = "SELECT userID
				FROM Users
				WHERE firstName = '$firstname'
				AND lastName = '$lastname'
				AND esign = '$esign'";

	$result = mysqli_query($conn, $query)
		or die('Check user account failed ' . mysqli_error());

	if (mysqli_num_rows($result) === 0)
		print '<h3>Oops! We could not find you!</h3>';
	else {
		$tuple = mysqli_fetch_row($result);

		$query2 = "INSERT INTO Orders (userID, orderDate)
					VALUES
					($tuple[0], NOW())";
		$result2 = mysqli_query($conn, $query2)
			or die('Create new order failed ' . mysqli_error());
		print '<h3>You can start ordering!</h3>';

		// get newly-created orderID
		$query3 = "SELECT LAST_INSERT_ID()";
		$result3 = mysqli_query($conn, $query3)
			or die('Select new order failed ' . mysqli_error());
		$order = mysqli_fetch_row($result3);
		echo "<h4>Your order ID: $order[0]</h4>";
		print "<form method=\"post\" >
				<b>Dish ID</b>
			　	<input type=\"number\" name=\"dishID\" min=\"1\" required>
				<b>Quantity</b>
				<input type=\"number\" name=\"quantity\" min=\"1\" required>
				<input type=\"hidden\" name=\"firstName\" value=\"$firstname\">
				<input type=\"hidden\" name=\"lastName\" value=\"$lastname\">
			　	<input type=\"hidden\" name=\"h1\" value=\"$order[0]\">
			　	<input type=\"submit\" name=\"orderSubmit\" value=\"Order!\">
			</form>";
		mysqli_free_result($result2);
		mysqli_free_result($result3);
	}

	// Free result
	mysqli_free_result($result);
}

print "
<hr>
<div class=\"col-md-4\">
	<h4>Apply Promotion</h4>
	<form action=\"promotion.php\" method=\"post\">
		<b>First name</b><br>
		<input type=\"text\" name=\"firstName\" value=\"$firstname\" required><br>
		<b>Last name</b><br>
		<input type=\"text\" name=\"lastName\" value=\"$lastname\" required><br>
		<b>Customer e-signature</b><br>
		<input type=\"text\" name=\"esign\" required><br>
		<b>Order ID</b><br>
		<input type=\"number\" name=\"orderID\" min=\"1\" required><br>
		<b>Promotion code</b><br>
		<input type=\"text\" name=\"promotion\" required><br>
		<input type=\"submit\" value=\"Submit\">
	</form>
</div>
<div class=\"col-md-4\">
	<h4>Checkout</h4>
	<form action=\"checkout.php\" method=\"post\">
		<b>First name</b><br>
		<input type=\"text\" name=\"firstName\" value=\"$firstname\" required><br>
		<b>Last name</b><br>
		<input type=\"text\" name=\"lastName\" value=\"$lastname\" required><br>
		<b>Customer e-signature</b><br>
		<input type=\"text\" name=\"esign\" required><br>
		<b>Order ID</b><br>
		<input type=\"number\" name=\"orderID\" min=\"1\" required><br>
		<input type=\"submit\" value=\"Submit\">
	</form>
</div>
<div class=\"col-md-4\">
	<h4>Cancel Order</h4>
	<form action=\"cancel_order.php\" method=\"post\">
		<b>First name</b><br>
		<input type=\"text\" name=\"firstName\" value=\"$firstname\" required><br>
		<b>Last name</b><br>
		<input type=\"text\" name=\"lastName\" value=\"$lastname\" required><br>
		<b>Customer e-signature</b><br>
		<input type=\"text\" name=\"esign\" required><br>
		<b>Order ID</b><br>
		<input type=\"number\" name=\"orderID\" min=\"1\" required><br>
		<input type=\"submit\" value=\"Submit\">
	</form>
</div>
";
// Close connection
mysqli_close($conn);
?>

	</body>
</html>
