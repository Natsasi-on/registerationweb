<?php
include("./common/header.php");
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
session_start();

class registration
{

    private $StudentId;
    private $CourseCode;
    private $SemesterCode;

    public function __construct($StudentId, $CourseCode, $SemesterCode)
    {
        $this->StudentId = $StudentId;
        $this->CourseCode = $CourseCode;
        $this->SemesterCode = $SemesterCode;
    }

    public function getSID()
    {
        return $this->StudentId;
    }

    public function getCourseCode()
    {
        return $this->CourseCode;
    }

    public function getSemCode()
    {
        return $this->SemesterCode;
    }
}

if (!isset($_SESSION["usernameLogin"])) {
    header("Location: Login.php");
    exit();
}
$studentNameLogin = $_SESSION["studentNameLogin"];
$studentId = $_SESSION["studentIdLogin"];
$errorMsg = '';

//////////////////////////////////////////////////////////////////////////////////////////////////
function getPDO()
{
    $dbConnection = parse_ini_file("Lab5.ini");
    extract($dbConnection);
    return new PDO($dsn, $scriptUser, $scriptPassword);
}

function getRegisteredCourses()
{
    $studentId = $_SESSION["studentIdLogin"];
    $pdo = getPDO();
    $sql = "SELECT SemesterCode FROM registration GROUP BY SemesterCode";
    $resultSet = $pdo->query($sql);
    $semester = array();
    foreach ($resultSet as $row) {
        $semester[] = $row["SemesterCode"];
    }

    foreach ($semester as $term) {
        $pdo = getPDO();
        $sql = "SELECT C.CourseCode CCode, C.Title Title, C.WeeklyHours WeeklyHours, "
            . "S.SemesterCode SCode, S.Year Year, S.Term Term "
            . "FROM Course C INNER JOIN Registration R ON C.CourseCode = R.CourseCode "
            . "INNER JOIN Semester S ON R.SemesterCode = S.SemesterCode "
            . "WHERE R.StudentId = '$studentId' AND S.SemesterCode = '$term'";
        $results = $pdo->query($sql);
        foreach ($results as $row) {
            echo '<tr>'
                . '<td>' . $row["Year"] . '</td>'
                . '<td>' . $row["Term"] . '</td>'
                . '<td>' . $row["CCode"] . '</td>'
                . '<td>' . $row["Title"] . '</td>'
                . '<td>' . $row["WeeklyHours"] . '</td>'
                . '<td><input type="checkbox" name="courseCodeCheckBox[]" value="' . $row["CCode"] . '"><input hidden name="listSemester[]" value="' . $row["SCode"] . '"></td></tr>';
        }

        $sql = "SELECT SUM(C.WeeklyHours) FROM Course C INNER JOIN Registration R ON C.CourseCode = R.CourseCode INNER JOIN Semester S ON R.SemesterCode = S.SemesterCode WHERE R.StudentId = '$studentId' AND S.SemesterCode = '$term' GROUP BY S.SemesterCode;";
        $resultSet = $pdo->query($sql);
        if ($resultSet) {
            $total = $resultSet->fetch(PDO::FETCH_ASSOC);
            if ($total) {
                $totalHoursRegis = array_values($total);
                $registedHours = $totalHoursRegis[0];
                echo '<tr><td></td><td></td><td></td><td style="text-align:right;"><b>Total Weekly Hours</b></td><td><b>' . $registedHours . '</b></td><td></td></tr>';
            }
        }
    }
    return $results;
}

//////////////////////////////////////////////////////////////////////////////////////////////////
$chBoxCourses = $_POST['courseCodeCheckBox'];
$chBoxSemester = $_POST['listSemester'];
if (isset($_POST["deleteCourseBtn"])) {

    if (empty($_POST["courseCodeCheckBox"])) {
        $errorMsg = "You need select at least one course!";
    } else {
        for ($i = 0; $i < sizeof($chBoxSemester); $i++) {
            for ($b = 0; $b < sizeof($chBoxCourses); $b++) {
                $pdo = getPDO();
                $sql = "SELECT * FROM registration WHERE StudentId= '$studentId' AND CourseCode='$chBoxCourses[$b]' AND SemesterCode='$chBoxSemester[$i]'";
                //                $stmt = $pdo->prepare($sql);
                //                $stmt->execute(['StudentId' => $StudentId, 'CourseCode' => $CourseCode, 'SemesterCode' => $SemesterCode]);
                //                $row = $stmt->fetch(PDO::FETCH_ASSOC);//get 1 row
                $pdoStmt = $pdo->query($sql);
                $row = $pdoStmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $sql = "DELETE FROM registration WHERE StudentId= '$studentId' AND CourseCode='$chBoxCourses[$b]' AND SemesterCode='$chBoxSemester[$i]'";
                    //                    $stmt = $pdo->prepare($sql);
                    //                    $stmt->execute(['StudentId' => $StudentId, 'CourseCode' => $CourseCode, 'SemesterCode' => $SemesterCode]);
                    $pdoStmt = $pdo->query($sql);
                }
            }
        }
    }
}
?>
<div class="container">
    <h1 style='text-align: center'>Course Registrations</h1>
    <p>Hello <b><?php echo $studentNameLogin; ?></b> ! (not you? change user <a href="Login.php">here</a>),
        The followings are your current registrations
    </p>
    <p class="text-danger"><?php echo $errorMsg; ?></p>
    <form method="post" id="myform" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <table class="table">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Term</th>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Hours</th>
                    <th>Select</th>
                </tr>
            </thead>
            <tbody>
                <?php $disPlayRegistedCoursed = getRegisteredCourses(); ?>
            </tbody>
        </table>
        <div class="col-sm-8" style="float: right;width: 230px;">
            <input type='submit' class='btn btn-primary' name='deleteCourseBtn' value='Delete Selected' onclick="return confirm('The selected registration will be deleted!');" />
            <input type='submit' class='btn btn-primary' name='clearBtn' value='Clear' />
        </div>
    </form>
</div>
<script>
    var valuetwo = [];
    valuetwo = checkbox.getAttribute("data-valuetwo");
</script>
<?php
include('./common/footer.php');
