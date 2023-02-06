<?php
include("./common/header.php");


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
//class name the same with table name
class registration
{
    //use the same name as column but not necessary
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

$displayCourseAvaHtml = false;
$studentNameLogin = $_SESSION["studentNameLogin"];
$studentId = $_SESSION["studentIdLogin"];
//$mySelectedSemester = $_SESSION["mySelectedSemester"];

$chBoxCourses = $_POST['courseCodeCheckBox'];
$hoursLeft = 16;
$registedHours = 0;
$errorMsg = '';

function getPDO()
{
    $dbConnection = parse_ini_file("Lab5.ini");
    extract($dbConnection);
    return new PDO($dsn, $scriptUser, $scriptPassword);
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function getSemester()
{
    $pdo = getPDO();
    $sql = "SELECT SemesterCode, Year, Term FROM semester";
    $resultSet = $pdo->query($sql);
    return $resultSet;
}

function getCoursesAva()
{
    $pdo = getPDO();
    $studentId = $_SESSION["studentIdLogin"];
    $mySelectedSemester = $_SESSION["mySelectedSemester"];
    $sql = "SELECT Course.CourseCode Code, Title,  WeeklyHours "
        . "FROM Course INNER JOIN CourseOffer ON Course.CourseCode = CourseOffer.CourseCode "
        . "WHERE CourseOffer.SemesterCode = '$mySelectedSemester' "
        . "AND Course.CourseCode NOT IN (SELECT CourseCode FROM Registration WHERE StudentId = '$studentId')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultSet = $stmt->fetchAll();
    return $resultSet;
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST["btnSubmitSemester"])) {

    $_SESSION["mySelectedSemester"] = $_POST['mySelectedSemester'];
    $mySelectedSemester = $_SESSION["mySelectedSemester"];
    $disPlayCoursesAva = getCoursesAva();
    $displayCourseAvaHtml = true;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST["submitBtnCourses"])) {
    $_POST['mySelectedSemester'] = $_SESSION["mySelectedSemester"];
    $mySelectedSemester = $_SESSION["mySelectedSemester"];
    $studentId = $_SESSION["studentIdLogin"];

    if (empty($_POST["courseCodeCheckBox"])) {
        $errorMsg = "You need select at least one course!";
        $disPlayCoursesAva = getCoursesAva();
        $displayCourseAvaHtml = true;
    } else {
        $_SESSION["selectedCourseCode"] = $_POST['courseCodeCheckBox'];
        $pdo = getPDO();
        ///////////////////////////////check total hour
        $sql = "SELECT SUM(C.WeeklyHours) totHours FROM Course C INNER JOIN Registration R ON C.CourseCode = R.CourseCode INNER JOIN Semester S ON R.SemesterCode = S.SemesterCode WHERE R.StudentId = '$studentId' AND S.SemesterCode = '$mySelectedSemester' GROUP BY S.SemesterCode;";
        $resultSet = $pdo->query($sql);
        if ($resultSet) {
            $total = $resultSet->fetch(PDO::FETCH_ASSOC);
            if ($total) {
                $aSumRegis = $total["totHours"];
                $bHLeft = 16 - $aSumRegis;
            } else {
                $aSumRegis = 0;
                $bHLeft = 16;
            }
        }

        if ($bHLeft > 0) {
            try {
                for ($i = 0; $i < sizeof($chBoxCourses); $i++) {
                    $pdo = getPDO();
                    $sql = "SELECT WeeklyHours FROM course WHERE CourseCode = '$chBoxCourses[$i]'";
                    $resultSet = $pdo->query($sql);
                    if ($resultSet) {
                        $total = $resultSet->fetch(PDO::FETCH_ASSOC);
                        if ($total) {
                            //                            $aa = $a;
                            $calTotHours += $total["WeeklyHours"];
                            $calHoursLeft = 16 - $calTotHours;
                        }
                    }
                }
                /////////////////////check new hour couse with already regis course
                $calTotHours = $calTotHours + $aSumRegis;
                if (($calTotHours > 16) || ($calHoursLeft < 0) || ($registedHours > 16) || ($hoursLeft < 0)) {
                    $errorMsg = "Your selection exceed the max weekly hours!";
                    $_POST['mySelectedSemester'] = $_SESSION["mySelectedSemester"];
                    $mySelectedSemester = $_SESSION["mySelectedSemester"];
                    $studentId = $_SESSION["studentIdLogin"];
                    $disPlayCoursesAva = getCoursesAva();
                    $displayCourseAvaHtml = true;
                } else {
                    for ($v = 0; $v < sizeof($chBoxCourses); $v++) {
                        $pdo = getPDO();
                        $sql = "INSERT INTO registration (StudentId, CourseCode, SemesterCode)VALUES( '$studentId', '$chBoxCourses[$v]', '$mySelectedSemester')";
                        //                        $stmt = $pdo->prepare($sql);
                        //                        $stmt->execute(['StudentId' => $StudentId, 'CourseCode' => $CourseCode, 'SemesterCode' => $SemesterCode]);
                        $pdoStmt = $pdo->query($sql);
                    }
                    $disPlayCoursesAva = getCoursesAva();
                    $displayCourseAvaHtml = true;
                }
            } catch (Exception $e) {
                die("The system is currently not available, try again later");
            }
        } else {
            $errorMsg = "Your selection exceed the max weekly hours!";
            $disPlayCoursesAva = getCoursesAva();
            $displayCourseAvaHtml = true;
        }
    }
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
<div class="container">
    <h1 style='text-align: center'>Course Selection</h1>
    <p>Welcome <b><?php echo $studentNameLogin; ?></b> ! (not you? change user <a href="Login.php">here</a>)</p>
    <?php
    $pdo = getPDO();
    $sql = "SELECT SUM(C.WeeklyHours) totHours FROM Course C INNER JOIN Registration R ON C.CourseCode = R.CourseCode INNER JOIN Semester S ON R.SemesterCode = S.SemesterCode WHERE R.StudentId = '$studentId' AND S.SemesterCode = '$mySelectedSemester' GROUP BY S.SemesterCode;";
    $resultSet = $pdo->query($sql);
    if ($resultSet) {
        $total = $resultSet->fetch(PDO::FETCH_ASSOC);
        if ($total) {
            $registedHours = $total["totHours"];
            $hoursLeft = 16 - $registedHours;
        }
    }
    ?>
    <p>You have register <b><?php echo $registedHours; ?> </b>hours for the selected semester.</p>
    <p>You can register <b><?php echo $hoursLeft; ?> </b> more hours of the course(s) for the semester</p>
    <p>Please note that the courses you have registered will not be displayed in the list</p>

    <form method="post" id="myform" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <?php $displaySemester = getSemester() ?>
        <?php
        echo '<select name="mySelectedSemester" style="float:right; width: 200px;" onchange="semesterChoosen();" id="SelectedSemester">';
        echo '<option value="-1"> Choose Semester ....</option>';
        foreach ($displaySemester as $row) {
            echo '<option value="' . $row['SemesterCode'] . '"';
            if (isset($_POST['mySelectedSemester']) && ($_POST['mySelectedSemester']) == $row['SemesterCode']) {
                echo "selected='selected'";
            }
            echo '>' . $row["Year"] . ' ' . $row["Term"] . '</option>';
        }
        echo '</select>';
        ?>
        <div class="col-sm-3">
            <button type="submit" hidden id="submitbtn" name='btnSubmitSemester'></button>
        </div>
    </form>
</div>


<?php if ($displayCourseAvaHtml) { ?>
    <div class="container">
        <p class="text-danger"><?php echo $errorMsg; ?></p>
        <form method="post" name="selectCourseForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Title</th>
                        <th>Hours</th>
                        <th>Select</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disPlayCoursesAva as $row) { ?>
                        <tr>
                            <td><?php echo $row["Code"]; ?></td>
                            <td><?php echo $row["Title"]; ?></td>
                            <td><?php echo $row["WeeklyHours"]; ?></td>
                            <td>
                                <input type="checkbox" name="courseCodeCheckBox[]" value="<?php echo $row['Code'] ?>" <?php
                                                                                                                        if (isset($_POST['courseCodeCheckBox']) && is_array($_POST['courseCodeCheckBox']) && in_array($row["Code"], $_POST['courseCodeCheckBox'])) {
                                                                                                                            echo "checked";
                                                                                                                        }
                                                                                                                        ?>>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="col-sm-3" style="float: right;width: 180px;">
                <input type='submit' class='btn btn-primary' name='submitBtnCourses' value='Submit' />
                <input type='submit' class='btn btn-primary' name='submitBtnCoursesClear' value='Clear' />
            </div>
    </div>
<?php } ?>

<script>
    function semesterChoosen() {
        mySubmitButton = document.getElementById("submitbtn");
        mySubmitButton.click();
    }
</script>
<?php
include('./common/footer.php');
