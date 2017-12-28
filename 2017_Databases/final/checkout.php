<!DOCTYPE html>
<html>
<head>
	<title>Checkout</title>
</head>
<body>
	<h1>Checkout</h1>

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

// Search the order in the database
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
		print "<h3>Invalid order!</h3>";
	else {
		$query2 = "SELECT CASE WHEN SUM(A.quantity*B.price) IS NULL THEN 0
							ELSE SUM(A.quantity*B.price)
							END
					FROM OrderMenuItems A 
					JOIN MenuItem B
					USING(menuItemID)
					WHERE orderID = $orderID";

		$result2 = mysqli_query($conn, $query2)
			or die('Fetch data failed ' . mysqli_error());
		$tuple2 = mysqli_fetch_row($result2);

		$query3 = "UPDATE Orders
					SET status = 'Approved',
					orderDate = NOW(),
					total = $tuple2[0]
					WHERE orderID = $orderID";
		
		$result3 = mysqli_query($conn, $query3)
			or die('Checkout failed ' . mysqli_error());
		print '<h3>Your order is approved!</h3>';

		mysqli_free_result($result2);
		mysqli_free_result($result3);
	}
}


// Free result
mysqli_free_result($result);

// Close connection
mysqli_close($conn);
?>

	</body>
</html>