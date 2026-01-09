<?php
/* ======================
   SESSION START (MUST)
====================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "includes/db.php";

$msg = "";
$type = "";

/* ======================
   SIGN UP
====================== */
if (isset($_POST['signup'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $cpass = $_POST['cpassword'];

    if ($name == "" || $email == "" || $pass == "" || $cpass == "") {
        $msg = "All fields required";
        $type = "error";
    } elseif ($pass !== $cpass) {
        $msg = "Passwords do not match";
        $type = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Email already registered";
            $type = "error";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name,email,password,status)
                 VALUES (?,?,?,'active')"
            );
            $stmt->bind_param("sss", $name, $email, $hash);
            $stmt->execute();

            $msg = "Account created successfully. Please login.";
            $type = "success";
        }
    }
}

/* ======================
   LOGIN
====================== */
if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if ($email == "" || $pass == "") {
        $msg = "All fields required";
        $type = "error";
    } else {

        $stmt = $conn->prepare(
            "SELECT id,name,password FROM users
             WHERE email=? AND status='active'"
        );

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (password_verify($pass, $user['password'])) {

                /* ======================
                   SESSION SET (CRITICAL)
                ====================== */
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                // Extra security
                session_regenerate_id(true);

                header("Location: splash.php");
                exit;
            } else {
                $msg = "Invalid password";
                $type = "error";
            }
        } else {
            $msg = "Account not found";
            $type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login | PROTECTORS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

    <style>
        /* Please ❤ this if you like it! */
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 300;
            font-size: 15px;
            line-height: 1.7;
            color: #c4c3ca;
            background-color: #1f2029;
            background-image: url('https://images.unsplash.com/photo-1519681393784-d120267933ba?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff !important;
        }

        .navbar-brand span {
            color: #4a6fa5;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(31, 32, 41, 0.85);
            z-index: -1;
        }

        a {
            cursor: pointer;
            transition: all 200ms linear;
            text-decoration: none;
        }

        a:hover {
            text-decoration: none;
        }

        .link {
            color: #c4c3ca;
        }

        .link:hover {
            color: #ffeba7;
        }

        p {
            font-weight: 500;
            font-size: 14px;
            line-height: 1.7;
        }

        h4 {
            font-weight: 600;
            color: #ffeba7;
            margin-bottom: 20px;
        }

        h6 {
            display: inline-block;
            margin: 0 15px;
            text-transform: uppercase;
            font-weight: 700;
            color: #c4c3ca;
            transition: all 300ms linear;
        }

        h6.active {
            color: #ffeba7;
            transform: scale(1.05);
        }

        .toggle-text {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }

        .toggle-text span {
            padding: 0 10px;
        }

        .section {
            position: relative;
            width: 100%;
            display: block;
        }

        .full-height {
            min-height: 100vh;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Toggle Switch */
        [type="checkbox"]:checked,
        [type="checkbox"]:not(:checked) {
            position: absolute;
            left: -9999px;
        }

        .checkbox:checked+label,
        .checkbox:not(:checked)+label {
            position: relative;
            display: block;
            text-align: center;
            width: 70px;
            height: 20px;
            border-radius: 10px;
            padding: 0;
            margin: 20px auto 40px;
            cursor: pointer;
            background-color: #ffeba7;
        }

        .checkbox:checked+label:before,
        .checkbox:not(:checked)+label:before {
            position: absolute;
            display: block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: #ffeba7;
            background-color: #102770;
            font-family: 'unicons';
            content: '\eb4f';
            z-index: 20;
            top: -10px;
            left: -10px;
            line-height: 40px;
            text-align: center;
            font-size: 24px;
            transition: all 0.5s ease;
            box-shadow: 0 4px 15px 0 rgba(0, 0, 0, 0.3);
        }

        .checkbox:checked+label:before {
            transform: translateX(50px) rotate(-270deg);
        }

        /* 3D Card */
        .card-3d-wrap {
            position: relative;
            width: 440px;
            max-width: 100%;
            height: 500px;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            perspective: 800px;
        }

        .card-3d-wrapper {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            transition: all 700ms ease-out;
        }

        .card-front,
        .card-back {
            width: 100%;
            height: 100%;
            background-color: rgba(42, 43, 56, 0.95);
            background-image: url('https://s3-us-west-2.amazonaws.com/s.cdpn.io/1462889/pat.svg');
            background-position: bottom center;
            background-repeat: no-repeat;
            background-size: 300%;
            position: absolute;
            border-radius: 10px;
            left: 0;
            top: 0;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            -webkit-backface-visibility: hidden;
            -moz-backface-visibility: hidden;
            -o-backface-visibility: hidden;
            backface-visibility: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-back {
            transform: rotateY(180deg);
        }

        .checkbox:checked~.card-3d-wrap .card-3d-wrapper {
            transform: rotateY(180deg);
        }

        .center-wrap {
            position: absolute;
            width: 100%;
            padding: 0 35px;
            top: 50%;
            left: 0;
            transform: translate3d(0, -50%, 35px) perspective(100px);
            z-index: 20;
            display: block;
        }

        /* Form Styles */
        .form-group {
            position: relative;
            display: block;
            margin-bottom: 20px;
        }

        .form-style {
            padding: 13px 20px 13px 55px;
            height: 48px;
            width: 100%;
            font-weight: 500;
            border-radius: 6px;
            font-size: 14px;
            line-height: 22px;
            letter-spacing: 0.5px;
            outline: none;
            color: #c4c3ca;
            background-color: rgba(31, 32, 41, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            -webkit-transition: all 200ms linear;
            transition: all 200ms linear;
            box-shadow: 0 4px 8px 0 rgba(21, 21, 21, 0.2);
        }

        .form-style:focus,
        .form-style:active {
            border: 1px solid #ffeba7;
            outline: none;
            box-shadow: 0 4px 8px 0 rgba(255, 235, 167, 0.1);
        }

        .input-icon {
            position: absolute;
            top: 0;
            left: 18px;
            height: 48px;
            font-size: 24px;
            line-height: 48px;
            text-align: left;
            color: #ffeba7;
            -webkit-transition: all 200ms linear;
            transition: all 200ms linear;
        }

        .form-group input::placeholder {
            color: #c4c3ca;
            opacity: 0.7;
            -webkit-transition: all 200ms linear;
            transition: all 200ms linear;
        }

        .form-group input:focus::placeholder {
            opacity: 0;
            -webkit-transition: all 200ms linear;
            transition: all 200ms linear;
        }

        /* Button */
        .btn {
            border-radius: 6px;
            height: 48px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            -webkit-transition: all 200ms linear;
            transition: all 200ms linear;
            padding: 0 40px;
            letter-spacing: 1px;
            display: -webkit-inline-flex;
            display: -ms-inline-flexbox;
            display: inline-flex;
            -webkit-align-items: center;
            -moz-align-items: center;
            -ms-align-items: center;
            align-items: center;
            -webkit-justify-content: center;
            -moz-justify-content: center;
            -ms-justify-content: center;
            justify-content: center;
            -ms-flex-pack: center;
            text-align: center;
            border: none;
            background-color: #ffeba7;
            color: #102770;
            box-shadow: 0 8px 24px 0 rgba(255, 235, 167, 0.2);
            width: 100%;
            margin-top: 10px;
        }

        .btn:active,
        .btn:focus {
            background-color: #102770;
            color: #ffeba7;
            box-shadow: 0 8px 24px 0 rgba(16, 39, 112, 0.2);
        }

        .btn:hover {
            background-color: #102770;
            color: #ffeba7;
            box-shadow: 0 8px 24px 0 rgba(16, 39, 112, 0.2);
            transform: translateY(-2px);
        }

        /* Additional Elements */
        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .logo {
            position: absolute;
            top: 30px;
            left: 30px;
            display: block;
            z-index: 100;
            transition: all 250ms linear;
        }

        .logo img {
            height: 36px;
            width: auto;
            display: block;
        }

        .footer {
            position: fixed;
            bottom: 30px;
            width: 100%;
            text-align: center;
            font-size: 13px;
            color: rgba(196, 195, 202, 0.7);
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            display: none;
        }

        .success {
            background-color: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

    </style>
</head>

<body>

    <div class="navbar-brand">
        PROTECT<span>ORS</span>
    </div>

    <div class="section">
        <div class="container">
            <div class="row full-height justify-content-center">
                <div class="col-12 text-center align-self-center py-5">

                    <?php if ($msg): ?>
                        <div class="message <?php echo $type; ?>" style="display:block">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <input class="checkbox" type="checkbox" id="reg-log">
                    <label for="reg-log"></label>

                    <div class="card-3d-wrap mx-auto">
                        <div class="card-3d-wrapper">

                            <!-- LOGIN -->
                            <div class="card-front">
                                <div class="center-wrap">
                                    <h4>Log In</h4>

                                    <form method="post">
                                        <div class="form-group">
                                            <input type="email" name="email" class="form-style" placeholder="Email"
                                                required>
                                            <i class="input-icon uil uil-at"></i>
                                        </div>

                                        <div class="form-group mt-2">
                                            <input type="password" name="password" class="form-style"
                                                placeholder="Password" required>
                                            <i class="input-icon uil uil-lock-alt"></i>
                                        </div>

                                        <button type="submit" name="login" class="btn mt-4">Login</button>
                                    </form>
                                </div>
                            </div>

                            <!-- SIGN UP -->
                            <div class="card-back">
                                <div class="center-wrap">
                                    <h4>Sign Up</h4>

                                    <form method="post">
                                        <div class="form-group">
                                            <input type="text" name="name" class="form-style" placeholder="Full Name"
                                                required>
                                            <i class="input-icon uil uil-user"></i>
                                        </div>

                                        <div class="form-group mt-2">
                                            <input type="email" name="email" class="form-style" placeholder="Email"
                                                required>
                                            <i class="input-icon uil uil-at"></i>
                                        </div>

                                        <div class="form-group mt-2">
                                            <input type="password" name="password" class="form-style"
                                                placeholder="Password" required>
                                            <i class="input-icon uil uil-lock-alt"></i>
                                        </div>

                                        <div class="form-group mt-2">
                                            <input type="password" name="cpassword" class="form-style"
                                                placeholder="Confirm Password" required>
                                            <i class="input-icon uil uil-lock-access"></i>
                                        </div>

                                        <button type="submit" name="signup" class="btn mt-4">Create Account</button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>