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
$studentId = $password = '';
function getPDO()
{
    $dbConnection = parse_ini_file("Lab5.ini");
    extract($dbConnection);
    return new PDO($dsn, $scriptUser, $scriptPassword);
}

$studentIdErrorMsg = $passwordErrorMsg = $loginErrorMsg = "";

if (isset($_SESSION["studentIdLogin"])) {
    $studentId = $_SESSION["studentIdLogin"];
}
if (isset($_SESSION["passwordLogin"])) {
    $password = $_SESSION["passwordLogin"];
}

if (isset($_POST["btnSubmit"])) {

    if (empty($_POST["studentIdtxt"])) {
        $studentIdErrorMsg = "Cannot be blank!";
    } else {
        $studentId = $_POST["studentIdtxt"];
        $_SESSION["studentIdLogin"] = $studentId;
    }

    if (empty($_POST["passwordtxt"])) {
        $passwordErrorMsg = "Cannot be blank!";
    } else {
        $password = $_POST["passwordtxt"];
        $_SESSION["passwordLogin"] = $password;
    }

    if (($studentIdErrorMsg == '') && ($passwordErrorMsg == '')) {

        function getUserByIdAndPassword($studentId, $password)
        {
            $password = hash("sha256", $password);
            $pdo = getPDO();

            $sql = "SELECT StudentId, Name, Phone FROM Student WHERE StudentId = :userId AND Password = :password";
            //green canbe anything

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['userId' => $studentId, 'password' => $password]); //green match with above
            $row = $stmt->fetch(PDO::FETCH_ASSOC); //Fetch one row from the prepared statement after execution.
            if ($row) {
                $newUser = new User($row['StudentId'], $row['Name'], $row['Phone']);
                $_SESSION["studentNameLogin"] = $newUser->getName();
                $_SESSION["studentIdLogin"] = $newUser->getUserId(); //cleated class should have only needed property
                return $newUser;
            } else {
                return null;
            }
        }

        try {
            $user = getUserByIdAndPassword($studentId, $password);
        } catch (Exception $e) {
            die("The system is currently not available, try again later");
        }

        if ($user == null) {
            $loginErrorMsg = '<label>Incorrect Student ID or Password!</label>';
        } else {
            $_SESSION["usernameLogin"] = $_POST["studentIdtxt"];
            header("location:CourseSelection.php");
        }
    } else {
    }
}

if (isset($_POST['btnClear'])) {
    session_destroy();
    $studentId = $password = "";
}
include("./common/header.php");
?>

<div class="container">
    <h1>Login</h1>
    <p>You need to <a href='NewUser.php'>sign up,</a> if you are a new user</p>
    <br />
    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>' method='post'>
        <p class="text-danger"><?php echo $loginErrorMsg; ?></p>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Student ID:</b></label>
            <div class="col-sm-3">
                <input type="text" class="form-control" name="studentIdtxt" value="<?php echo $studentId; ?>">
            </div>
            <div class="col-sm-3">
                <p class="text-danger"><?php echo $studentIdErrorMsg; ?></p>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b>Password:</b></label>
            <div class="col-sm-3">
                <input type="password" class="form-control" name="passwordtxt" value="<?php echo $password; ?>">
            </div>
            <div class="col-sm-7">
                <p class="text-danger"><?php echo $passwordErrorMsg; ?></p>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><b></b></label>
            <div class="col-sm-3">
                <input type='submit' class='btn btn-primary' name='btnSubmit' value='Submit' />&nbsp;&nbsp;
                <input type='submit' class='btn btn-primary' name='btnClear' value='Clear' />
            </div>
        </div>
    </form>
</div>
<?php
include('./common/footer.php');
