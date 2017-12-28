<!DOCTYPE html>
<html>
<head>
	<title>Cancel order</title>
</head>
<body>
	<h1>Cancel Order</h1>

<?php
function input_sanitization($data) {
	$data = trim($data);
	$data = htmlspecialchars($data);
	return $data;
}

// Connection parameters 
$host = 'mpcs53001.cs.uchicago.edu';
$username = '';
$password = '';
$database = $username.'DB';

// Connect to db
$conn = mysqli_connect($host, $username, $password, $database)
   or die('Could not connect ' . mysqli_connect_error());

// Input parameters
$firstname = input_sanitization($_REQUEST['firstName']);
$lastname = input_sanitization($_REQUEST['lastName']);
$esign = input_sanitization($_REQUEST['esign']);
$orderID = input_sanitization($_REQUEST['orderID']);

// Get the order in the database
$query = "SELECT orderID, status 
			FROM Orders 
			JOIN Users 
			USING(userID) 
			WHERE firstName = '$firstname' 
			AND lastName = '$lastname'
			AND esign = '$esign'
			AND orderID = '$orderID'";

$result = mysqli_query($conn, $query)
  or die('Find order failed ' . mysqli_error());

if (mysqli_num_rows($result) === 0)
	print '<h3>No result found!</h3>';
else {
	$tuple = mysqli_fetch_row($result);
	if ($tuple[1] != "Pending")
		print "<h3>You cannot cancel this order because its status is $tuple[1]!</h3>";
	// cancel the order
	else {
		$query2 = "UPDATE Orders
					SET status = 'Cancelled',
					orderDate = NOW()
					WHERE orderID = $orderID";
		$result2 = mysqli_query($conn, $query2)
			or die('Order cancellation failed ' . mysqli_error());
		print '<h3>Your order is cancelled!</h3>';

		mysqli_free_result($result2);
	}
}


// Free result
mysqli_free_result($result);

// Close connection
mysqli_close($conn);
?>

	</body>
</html>