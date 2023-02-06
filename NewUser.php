<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler(function ($errno, $error) {
    if (!str_starts_with($error, 'Undefined array key')) {
        return false;  //default error handler.
    } else {
        trigger_error($error, E_USER_NOTICE);
        return true;
    }
}, E_WARNING);
ini_set("error_reporting",  E_ALL & ~E_NOTICE & ~E_USER_NOTICE);


class User
{

    private $userId;
    private $name;
    private $phone;

    public function __construct($userId, $name, $phone)
    {
        $this->userId = $userId;
        $this->name = $name;
        $this->phone = $phone;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPhone()
    {
        return $this->phone;
    }
}

function getPDO()
{
    $dbConnection = parse_ini_file("Lab5.ini");
    extract($dbConnection);
    return new PDO($dsn, $scriptUser, $scriptPassword);
}

$nameErrorMsg = $passwordErrorMsg = $passwordAgainErrorMsg = $phoneErrorMsg = $studentIdErrorMsg = "";
$name = $passwordAgain = $PhoneNum = $password = $studentId = "";

//if (isset($_SESSION["studentId"])) {
//    $studentId = $_SESSION["studentId"];
//}
//
//if (isset($_SESSION["name"])) {
//    $name = $_SESSION["name"];
//}
//
//if (isset($_SESSION["phoneNum"])) {
//    $PhoneNum = $_SESSION["phoneNum"];
//}
//if (isset($_SESSION["mypassword"])) {
//    $password = $_SESSION["mypassword"];
//}
//
//if (isset($_SESSION["passwordAgain"])) {
//    $passwordAgain = $_SESSION["passwordAgain"];
//}


if (isset($_POST['btnSubmit'])) {

    function test_input($myInput)
    {
        $myInput = trim($myInput);
        return $myInput;
    }

    function ValidateStudentId($studentId)
    {
        if (empty($studentId)) {
            $studentIdErrorMsg = "Student Id cannot be blank!";
            return $studentIdErrorMsg;
        } else {
            $studentIdErrorMsg = '';
            return $studentIdErrorMsg;
        }
    }

    function ValidateName($name)
    {
        if (empty($name)) {
            $nameErrorMsg = "Name cannot be blank!";
            return $nameErrorMsg;
        } else {
            $nameErrorMsg = '';
            return $nameErrorMsg;
        }
    }

    function ValidatePhoneNumber($PhoneNum)
    {
        $phoneNumRegex = "/\b([2-9][0-9][0-9]-){2}[0-9]{4}\b/";
        if (preg_match($phoneNumRegex, $PhoneNum)) {
            $phoneErrorMsg = '';
            return $phoneErrorMsg;
        } else {
            $phoneErrorMsg = "Invalid Phone number";
            return $phoneErrorMsg;
        }
    }

    function ValidatePassword($password)
    {
        $passwordRegex = "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{6,}$/";
        if (preg_match($passwordRegex, $password)) {
            $passwordErrorMsg = '';
            return $passwordErrorMsg;
        } else {
            $passwordErrorMsg = "Password should contain at least 6 characters long, one upper case, one lowercase and one digit!";
            return $passwordErrorMsg;
        }
    }

    function ValidatePasswordAgain($passwordAgain)
    {
        if ($_POST["password"] != $_POST["passwordAgain"]) {
            $passwordAgainErrorMsg = "Passwords do not match!";
            return $passwordAgainErrorMsg;
        } else {
            $passwordAgainErrorMsg = '';
            return $passwordAgainErrorMsg;
        }
    }

    if (empty($_POST["studentId"])) {
        $studentIdErrorMsg = "Student Id cannot be blank!";
    } else {
        $studentId = test_input($_POST["studentId"]);
        $studentIdErrorMsg = ValidateStudentId($studentId);
        if ($nameErrorMsg == '') {
            $_SESSION["studentId"] = $studentId;

            function getUserByIdAndPassword($studentId)
            {
                $pdo = getPDO();
                $sql = "SELECT StudentId, Name, Phone FROM Student WHERE StudentId = '$studentId'";
                $resultSet = $pdo->query($sql);
                if ($resultSet) {
                    $row = $resultSet->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        return new User($row['StudentId'], $row['Name'], $row['Phone']);
                    } else {
                        return null;
                    }
                } else {
                    throw new Exception("Query failed! SQL statement: $sql");
                }
            }

            try {
                $user = getUserByIdAndPassword($studentId);
            } catch (Exception $e) {
                die("The system is currently not available, try again later");
            }

            if ($user == null) {
                $studentIdErrorMsg = '';
            } else {
                $studentIdErrorMsg = 'The student with this ID already register!';
            }
        }
    }


    if (empty($_POST["name"])) {
        $nameErrorMsg = "Name cannot be blank!";
    } else {
        $name = test_input($_POST["name"]);
        $nameErrorMsg = ValidateName($name);

        //        $_SESSION["name"] = $name;
    }

    if (empty($_POST["PhoneNum"])) {
        $phoneErrorMsg = "Phone cannot be blank!";
    } else {
        $PhoneNum = test_input($_POST["PhoneNum"]);
        $phoneErrorMsg = ValidatePhoneNumber($PhoneNum);

        //        $_SESSION["phoneNum"] = $PhoneNum;
    }

    if (empty($_POST["password"])) {
        $passwordErrorMsg = "Password cannot be blank!";
    } else {
        $password = test_input($_POST["password"]);
        $passwordErrorMsg = ValidatePassword($password);

        $password = hash("sha256", $password);
    }

    if (empty($_POST["passwordAgain"])) {
        $passwordAgainErrorMsg = "Cannot be blank!";
    } else {
        $passwordAgain = test_input($_POST["passwordAgain"]);
        $passwordAgainErrorMsg = ValidatePasswordAgain($passwordAgain);
        $passwordAgain = hash("sha256", $passwordAgain);
    }



    if (($nameErrorMsg == '') && ($passwordAgainErrorMsg == '') && ($phoneErrorMsg == '') && ($passwordErrorMsg == '') && ($studentIdErrorMsg == '')) {

        function addNewUser($studentId, $name, $PhoneNum, $password)
        {
            $pdo = getPDO();
            //            $sql = "INSERT INTO Student (StudentId, Name, Phone, Password) VALUES( '$studentId', '$name', '$PhoneNum', '$password')";
            //            $pdoStmt = $pdo->query($sql);

            $sql = "INSERT INTO Student VALUES( :StudentId, :Name, :Phone, :Password)"; //match table DB
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['StudentId' => $studentId, 'Name' => $name, 'Phone' => $PhoneNum, 'Password' => $password]);
            //green = name in DB, brown = variable in form
        }

        try {
            addNewUser($studentId, $name, $PhoneNum, $password);
            session_destroy();
            header("Location: Login.php");
            exit();
        } catch (Exception $e) {
            die("The system is currently not available, try again later");
        }
    }
}

if (isset($_POST['btnClear'])) {
    session_destroy();
    $name = $passwordAgain = $PhoneNum = $password = $studentId = "";
}


include("./common/header.php");
?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" name="myform">
    <div class="container">
        <h1>Sign Up</h1>
        <p>All field are required</p>
        <hr>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Student ID:</b></label>
            <div class="col-sm-3">
                <input type="text" class="form-control" name="studentId" value="<?php echo $studentId; ?>">
            </div>
            <div class="col-sm-3">
                <p class="text-danger"><?php echo $studentIdErrorMsg; ?></p>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Name:</b></label>
            <div class="col-sm-3">
                <input type="text" class="form-control" name="name" value="<?php echo $name; ?>">
            </div>
            <div class="col-sm-3">
                <p class="text-danger"><?php echo $nameErrorMsg; ?></p>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Phone Number:</b><br>(nnn-nnn-nnnn)</label>
            <div class="col-sm-3">
                <input type="text" class="form-control" name="PhoneNum" placeholder="xxx-xxx-xxx" value="<?php echo $PhoneNum; ?>">
            </div>
            <div class="col-sm-3">
                <p class="text-danger"><?php echo $phoneErrorMsg; ?></p>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Password:</b></label>
            <div class="col-sm-3">
                <input type="password" class="form-control" name="password" value="<?php echo $password; ?>">
            </div>
            <div class="col-sm-7">
                <p class="text-danger"><?php echo $passwordErrorMsg; ?></p>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Password Again:</b></label>
            <div class="col-sm-3">
                <input type="password" class="form-control" name="passwordAgain" value="<?php echo $passwordAgain; ?>">
            </div>
            <div class="col-sm-3">
                <p class="text-danger"><?php echo $passwordAgainErrorMsg; ?></p>
            </div>
        </div>


        <br>
        <input type='submit' class='btn btn-primary' name='btnSubmit' value='Submit' />
        <input type='submit' class='btn btn-primary' name='btnClear' value='Clear' />


</form>
</div>
<?php
include('./common/footer.php');
