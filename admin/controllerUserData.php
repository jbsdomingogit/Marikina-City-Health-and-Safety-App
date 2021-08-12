<?php 
session_start();
require "connection.php";
$email = "";
$name = "";

$lname = "";
$fname = "";
$phone = "";
$email2 = "";
$type = "";
$date = "";
$time = "";
$venue = "";
$message = "";
$errors = array();

//if user signup button
if(isset($_POST['signup'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $cpassword = mysqli_real_escape_string($conn, $_POST['cpassword']);
    if($password !== $cpassword){
        $errors['password'] = "CONFIRM PASSWORD NOT MATCHED!";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "INVALID EMAIL FORMAT!";
    }
    $name_check = "SELECT * FROM admin WHERE `name` = '$name'";
    $res = mysqli_query($conn, $name_check);
    if(mysqli_num_rows($res) > 0){
        $errors['name'] = "THE USERNAME YOU HAVE ENTERED IS ALREADY TAKEN!";
    }
    $email_check = "SELECT * FROM admin WHERE email = '$email'";
    $res = mysqli_query($conn, $email_check);
    if(mysqli_num_rows($res) > 0){
        $errors['email'] = "THE EMAIL YOU HAVE ENTERED ALREADY EXIST!";
    }
    if(count($errors) === 0){
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $code = rand(999999, 111111);
        $status = "notverified";
        $insert_data = "INSERT INTO admin (name, email, password, code, status)
                        values('$name', '$email', '$encpass', '$code', '$status')";
        $data_check = mysqli_query($conn, $insert_data);
        if($data_check){
            $subject = "Email Verification Code"; 
            $message = "Your verification code is $code";
            $sender = "From: mchealthsafetyapp@gmail.com";
            if(mail($email, $subject, $message, $sender)){
                $_SESSION['statusMsg'] = "success";
				$_SESSION['success_message'] = "WE HAVE SENT AN OTP CODE TO YOUR EMAIL - $email";
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                header('location: user-otp.php');
                exit();
            }else{
                $errors['otp-error'] = "FAILED WHILE SENDING CODE!";
            }
        }else{
            $errors['db-error'] = "FAILED WHILE INSERTING DATA TO DATABASE!";
        }
    }

}
//if user click verification code submit button
if(isset($_POST['check'])){
    $info = "WE HAVE SENT A CODE TO YOUR EMAIL - .";
    $_SESSION['info'] = $info;
    $otp_code = mysqli_real_escape_string($conn, $_POST['otp']);
    $check_code = "SELECT * FROM admin WHERE code = $otp_code";
    $code_res = mysqli_query($conn, $check_code);
    if(mysqli_num_rows($code_res) > 0){
        $fetch_data = mysqli_fetch_assoc($code_res);
        $fetch_code = $fetch_data['code'];
        $email = $fetch_data['email'];
        $code = 0;
        $status = 'verified';
        $update_otp = "UPDATE admin SET code = $code, status = '$status' WHERE code = $fetch_code";
        $update_res = mysqli_query($conn, $update_otp);
        if($update_res){
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header('location:account-verified.php');
            exit();
        }else{
            $errors['otp-error'] = "FAILED WHILE UPDATING CODE!";
        }
    }else{
        $errors['otp-error'] = "YOU'VE ENTERED INCORRECT CODE!";
    }
}


//if user click login button
if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $check_email = "SELECT * FROM admin WHERE email = '$email'";
    $res = mysqli_query($conn, $check_email);
    if(mysqli_num_rows($res) > 0){
        $fetch = mysqli_fetch_assoc($res);
        $fetch_pass = $fetch['password'];
        if(password_verify($password, $fetch_pass)){
            $_SESSION['email'] = $email;
            $status = $fetch['status'];
            if($status == 'verified'){
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                header('location: index.php');
            }else{
                $_SESSION['statusMsg'] = "warning";
                $_SESSION['success_message'] = "YOUR EMAIL HAS NOT YET BEEN VERIFIED. VERIFY YOUR EMAIL - $email";
                header('location: user-otp.php');
            }
        }else{
            $errors['email'] = "THE PASSWORD YOU'VE ENTERED IS INCORRECT!";
        }
    }else{
        $errors['email'] = "THE EMAIL YOU'VE ENTERED DOES NOT EXIST!";
    }
}

//if user click continue button in forgot password form
if(isset($_POST['check-email'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $check_email = "SELECT * FROM admin WHERE email='$email'";
    $run_sql = mysqli_query($conn, $check_email);
    if(mysqli_num_rows($run_sql) > 0){
        $code = rand(999999, 111111);
        $insert_code = "UPDATE admin SET code = $code WHERE email = '$email'";
        $run_query =  mysqli_query($conn, $insert_code);
        if($run_query){
            $subject = "Password Reset Code";
            $message = "Your password reset code is $code";
            $sender = "From: mchealthsafetyapp@gmail.com";
            if(mail($email, $subject, $message, $sender)){
                $_SESSION['statusMsg'] = "success";
                $_SESSION['success_message'] = "WE HAVE SENT AN OTP CODE TO YOUR EMAIL - $email";
                $_SESSION['email'] = $email;
                header('location: reset-code.php');
                exit();
            }else{
                $errors['otp-error'] = "FAILED WHILE SENDING CODE!";
            }
        }else{
            $errors['db-error'] = "SOMETHING WENT WRONG!";
        }
    }else{
        $errors['email'] = "YOUR SEARCH DID NOT RETURN ANY RESULTS! PLEASE TRY AGAIN.";
    }
}

//if user click check reset otp button
if(isset($_POST['check-reset-otp'])){
    $info = "WE HAVE SENT AN OTP CODE TO YOUR EMAIL - .";
    $_SESSION['info'] = $info;
    $otp_code = mysqli_real_escape_string($conn, $_POST['otp']);
    $check_code = "SELECT * FROM admin WHERE code = $otp_code";
    $code_res = mysqli_query($conn, $check_code);
    if(mysqli_num_rows($code_res) > 0){
        $fetch_data = mysqli_fetch_assoc($code_res);
        $email = $fetch_data['email'];
        $_SESSION['email'] = $email;
        $info = "Please enter a new password.";
        $_SESSION['info'] = $info;
        header('location: change-password.php');
        exit();
    }else{
        $errors['otp-error'] = "YOU'VE ENTERED INCORRECT CODE!";
    }
}

//if user click change password button
if(isset($_POST['change-password'])){
    $_SESSION['info'] = "";
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $cpassword = mysqli_real_escape_string($conn, $_POST['cpassword']);
    if($password !== $cpassword){
        $errors['password'] = "CONFIRM PASSWORD NOT MATCHED!";
    }else{
        $code = 0;
        $email = $_SESSION['email']; //getting this email using session
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $update_pass = "UPDATE admin SET code = $code, password = '$encpass' WHERE email = '$email'";
        $run_query = mysqli_query($conn, $update_pass);
        if($run_query){
            $statusMsg = "success";
            $_SESSION['statusMsg'] = $statusMsg;
            $info = "YOUR PASSWORD HAS BEEN CHANGED! YOU CAN NOW LOGIN WITH YOUR NEW PASSWORD.";
            $_SESSION['info'] = $info;
            header('Location: password-changed.php');
        }else{
            $errors['db-error'] = "FAILED TO CHANGE YOUR PASSWORD!";
        }
    }
}

//if login now button click
if(isset($_POST['login-now'])){
    header('Location: login.php');
}

//if logout button is clicked
if(isset($_GET['logout'])){
    if($_GET['logout'] == 1){
        session_start();
        session_unset();
        session_destroy();
        header('location: login.php');
    }
}
?>