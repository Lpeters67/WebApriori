<?php
	include("config.php");

	//Import the PHPMailer class into the global namespace
	require './PHPMailer/src/PHPMailer.php';
	require './PHPMailer/src/Exception.php';
	require './PHPMailer/src/SMTP.php';
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

	session_start();
	$con1 = new mysqli(HOST, USERNAME, PWD, DB);
	
	/* check connection */
	if ($con1->connect_error) {
		http_response_code(403);
		$JsonReq = array('http_response_code' => 403, 'title' => 'Error', 'message' => 'Connection Error');
		print json_encode($JsonReq);
		die("Connection Error" . $con1->connect_error);
	}
	
	//erase expired orphan records
	$sql="DELETE FROM temp_usertable WHERE `created_at`<DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
	$result = mysqli_query($con1,$sql);

		
	// Check captcha
	// In case of capcha working uncomment the lines below
	// In case of capcha working uncomment the lines below
	// In case of capcha working uncomment the lines below
	// In case of capcha working uncomment the lines below
	// In case of capcha working uncomment the lines below
	$captcha_error = "";
	$captcha = $_POST['g-recaptcha-response'];
	if (!isset($_POST['g-recaptcha-response'])||empty($_POST['g-recaptcha-response'])) {
        $captcha_error = "Verification check has not been done";
	} else {
	 	$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6LdP7H8UAAAAAFZ5qAl0_FmLAqzdBUeD0G3ZaX0p&response=".$_POST['g-recaptcha-response'], False);
		
	 	$jresponse = json_decode($response, true);
	 	if(!$jresponse["success"] === true)
	 	{
	 		$captcha_error = true;
	 		echo "<script type='text/javascript'>alert('Verification check failed');
	 		window.location.href='../SignUp.html';</script>";
	 	}
	}
	
	/* Connection succesful*/
	$confirm_code=md5(uniqid(rand()));
	$email = mysqli_real_escape_string($con1, $_POST['email']);
	$passwd =md5(mysqli_real_escape_string($con1, $_POST['passwd']));
	$oname = mysqli_real_escape_string($con1, $_POST['oname']);
	$fname = mysqli_real_escape_string($con1, $_POST['fname']);
	$query ="SELECT * FROM usertable WHERE email='$email'";
	$result = mysqli_query($con1,$query);
	$num=mysqli_num_rows($result);

	/* User is registered */
	if($num==1) {
		http_response_code(201);
		$JsonReq = array('http_response_code' => 201, 'title' => 'Information', 'message' => 'You are already registered user!');
		print json_encode($JsonReq);
		exit();
	} 
	elseif(!$captcha_error) { /* New user @temp_usertable & send confirmation email */

		$query ="SELECT * FROM temp_usertable WHERE temail='$email'";
		$result = mysqli_query($con1,$query);
		$num=mysqli_num_rows($result);
		if($num==0) {                  

			$mail = new PHPMailer(TRUE);
			$mail->IsSMTP();
			$mail->CharSet="UTF-8";     
			$mail->Host = smtpHost;
			$mail->Port = smtpPort; 
			$mail->SMTPAuth = smtpAuth;                 
            $mail->Username = smtpUsername;  
            $mail->Password = smtpPassword;    
            $mail->SMTPSecure = smtpSecure; 
			$mail->setFrom(smtpFrom, smtpFromName);   
			$mail->addAddress($email);               
			$mail->Subject  = "Association rules mining. Please confirm your verification!";
			$mail->IsHTML(true);
			$mail->Body  = 	
				"<p>Your Confirmation link for the Association rules mining engine is,</p>
				<br>
				<h2><a href=\"{$_SERVER['SERVER_NAME']}/webapriori/phpfunctions/confirmation.php?passkey=$confirm_code\">here</a></h2>
				<br>
				<p>and it would be valid for 5 minutes.</p>"; 
			$mail->AltBody="Your Confirmation link for the Association rules mining engine is\r\n{$_SERVER['SERVER_NAME']}/webapriori/phpfunctions/confirmation.php?passkey=$confirm_code\r\nand it would be valid for 5 minutes.";
			if(!$mail->send()) {
				http_response_code(401);
				$JsonReq = array('http_response_code' => 401, 'title' => 'Error', 'message' => 'Email has not been sent. '.$mail->ErrorInfo);
				print json_encode($JsonReq);
				exit();
			} 

		    $sql="INSERT INTO temp_usertable(confirm_code,temail,tpasswd,toname,tfname)VALUES('$confirm_code','$email','$passwd','$oname','$fname')";
		    $result = mysqli_query($con1,$sql);
		    if($result) {
				http_response_code(200);
				$JsonReq = array('http_response_code' => 200, 'title' => 'Information', 'message' => 'Your Confirmation link Has Been Sent To Your Email Address.');
				print json_encode($JsonReq);
				exit();
			}
			else {
				http_response_code(401);
				$JsonReq = array('http_response_code' => 401, 'title' => 'Error', 'message' => 'Database error!!!');
				print json_encode($JsonReq);
				exit();
			}


		}
		else { /* Confirmation email has been already sent */

			http_response_code(201);
			$JsonReq = array('http_response_code' => 201, 'title' => 'Information', 'message' => 'Your confirmation link has been already sent to your email address '.$email.'. Please check your email again!');
			print json_encode($JsonReq);
			exit;

		}
	}
 
	session_destroy();
?>
