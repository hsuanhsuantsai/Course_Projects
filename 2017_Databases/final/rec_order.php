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

$firstname = input_sanitization($_REQUEST['firstName']);
$lastname = input_sanitization($_REQUEST['lastName']);
$esign = input_sanitization($_REQUEST['esign']);
$recommendation = $_REQUEST['my_recommendation'];

$query = "SELECT userID
			FROM Users
			WHERE firstName = '$firstname'
			AND lastName = '$lastname'
			AND esign = '$esign'";
$result = mysqli_query($conn, $query)
	or die('Find user failed ' . mysqli_error());

if (mysqli_num_rows($result) === 0)
	print "Oops! We could not find you!";
else {
	$tuple = mysqli_fetch_row($result);
	$query3 = "INSERT INTO Orders (userID, orderDate)
				VALUES
				($tuple[0], NOW())";
	$result3 = mysqli_query($conn, $query3)
		or die('Create new order failed: ' . mysqli_error());

	$query4 = "SELECT LAST_INSERT_ID()";
	$result4 = mysqli_query($conn, $query4)
		or die('Select new order failed ' . mysqli_error());
	$orderID = mysqli_fetch_row($result4);

	foreach ($recommendation as $value) {
		$query2 = "UPDATE Recommendation
					SET counter = counter+1,
					lastDate = NOW()
					WHERE recommendationID = $value";
		$result2 = mysqli_query($conn, $query2)
			or die('Recommendation update failed ' . mysqli_error());

		$query5 = "INSERT INTO OrderMenuItems(orderID, menuItemID, quantity)
					SELECT $orderID[0] AS orderID, menuItemID, 1 AS quantity
					FROM RecommendMenuItems
					WHERE recommendationID = $value";
		
		$result5 = mysqli_query($conn, $query5)
			or die('Order dish failed ' . mysqli_error());

		mysqli_free_result($result2);
		mysqli_free_result($result5);
	}

	print "Recommendation(s) is(are) added to Order ID: $orderID[0]";
	
	mysqli_free_result($result3);
	mysqli_free_result($result4);
}

// Free result
mysqli_free_result($result);

// Close connection
mysqli_close($conn);
?>