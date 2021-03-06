<?php
/*********************************************************************************
 * OpenACH is an ACH payment processing platform
 * Copyright (C) 2011 Steven Brendtro, ALL RIGHTS RESERVED
 * 
 * Refer to /legal/license.txt for license information, or view the full license
 * online at http://openach.com/community/license.txt
 ********************************************************************************/

// API Test for OpenACH

// Load the OpenACH SDK
require_once( '../../lib/OpenACHSdk.php' );

// First create a Connection object.
// The OAConnection constructor takes a ClientConfig instance to get the APIKey and APIToken references.
// A default class that uses an INI file is provided.
// The default directory is the API installation directory.
// The default filename is ./conf/connection.ini (which ought to be chmod 400 and owned by the application daemon user)
// We override that by submitting ".." as the configruation directory (a filename could also be provided if needed).

echo "Creating a new OAConnection Object" . PHP_EOL;
$oaConn = new OAConnection( new OAClientConfigIni('../..') );

// Here's an example showing an INI Config file stored in the /etc/myapp/oaconf/local_connection.ini file
//$oaConn = new OAConnection( new OAClientConfigIni('/etc/myapp', '/conf/local_connection.ini') );


// Now that we've got the connection, connect to the server and get an existing Payment Profile and update it
echo "Connecting to server..." . PHP_EOL;
$connected = '';
$attempts = 3;
try {
	while ( !$connected and $attempts > 0)
	{
		echo "Connecting: $attempts remaining" . PHP_EOL;
		$oaConn->connect();
		$connected = $oaConn->isConnected();
		if ($connected) {
			echo "Successfully connected!" . PHP_EOL;
		}


		$attempts = $attempts - 1;
	}
	if (!$connected) exit("Failed to establish a connection with the OpenACH server.");


	// Load payment profile using either an external_account_id or a payment_profile_id
	// Make sure you pick an ID that actually exists in your account!
	// NOTE:  To find one, log into your OpenACH account, and find a payment profile by clicking on:
	//   Your originator -> Your Bank -> Your Origination Account -> Payment Profiles
	// A very common use case is for end users to have a 1:1 correlation with payment profiles

	#echo "Retrieving Payment Profile ID from a custom defined app identifier" . PHP_EOL;
	$stored_user_profile_id = 'secret_agent_man';  // This could be the user's internal app id, email, or whatever 

	// NOTE: The use of OAConnection::IDTYPE_APP/OAConnection::IDTYPE_API to identify which type of ID is being provided.
try {
	$paymentProfile = $oaConn->getPaymentProfile( $stored_user_profile_id, OAConnection::IDTYPE_APP );
} catch (Exception $e) {}
	if (! isset( $paymentProfile->payment_profile_id ) ) {
		print "Failed to retrieve Payment Profile, trying another Profile ID..." . PHP_EOL;
		//$paymentProfile = $oaConn->getPaymentProfile('535fee85-3fbe-4873-8bb0-82dac008e979', OAConnection::IDTYPE_API );
		$paymentProfile = $oaConn->getPaymentProfile('3f65a649-7666-4209-9b55-b1c307e01aaf', OAConnection::IDTYPE_API );
	}

	if ( !isset( $paymentProfile->payment_profile_id ) ) {
		print "Failed to retrieve Payment Profile" . PHP_EOL;
	} else {
		print "Retrived Payment Profile ..." . PHP_EOL;
		printObj( $paymentProfile, 1 );

		// Now that we have the PaymentProfile we can set the API Payment Profile ID so it can be used by default on other calls
		echo "Setting the default Payment Profile ID for the connection" . PHP_EOL;
		$oaConn->setPaymentProfileId( $paymentProfile->payment_profile_id,  OAConnection::IDTYPE_API );
	
		print "Updating Payment Schedules ..." . PHP_EOL;

		// Retrieve all currently active paymentSchedules associated to this external account.
		// Useful to view / modify / discontinue / disable payments
		echo "\tGetting Payment Schedules..." . PHP_EOL;
		#$paymentSchedules = $oaConn->getPaymentSchedulesSummary( $extAcct->external_account_id);
		$paymentSchedules = $oaConn->getPaymentSchedulesSummary();
		foreach ( $paymentSchedules as $paySched )
		{
			print "\tPayment Schedule: " . $paySched->payment_schedule_id . PHP_EOL;
			printObj ( $paySched, 2 );

			// // Modify the Payment Schedule and Save it back
			print "Modifying Payment Schedule: " . $paySched->payment_schedule_id . PHP_EOL;
			$paymentSchedule = $oaConn->getPaymentSchedule( $paySched->payment_schedule_id );
			$payment_schedule_amount = $paymentSchedule->payment_schedule_amount;
			$paymentSchedule->payment_schedule_amount = $payment_schedule_amount + 100.00;
			$response = $oaConn->savePaymentSchedule( $paymentSchedule );
			if ( isset( $response->payment_schedule_id ) ) {
				print "Updated Payment Schedule: was-'" . $payment_schedule_amount  . "' is now-'" . $response->payment_schedule_amount . "'" . PHP_EOL;
			}
		}

	}
} catch(Exception $e) {
	echo $e->getMessage() . PHP_EOL;
} 

if ( $connected ) {
	print "Disconnecting ";
	$res = $oaConn->disconnect();
	printObj( $res );
}

function printObj ( $obj, $depth=0 ) {
	foreach ( $obj as $key => $value )
	{
		print str_repeat("\t", $depth) . "$key = $value" . PHP_EOL;
	}
	print PHP_EOL;
}

