<?php

//checks if the signup boxes are empty
function emptyInputSignup($name, $sname, $email, $pwd, $pwdRepeat){
    if (empty($name) || empty($sname) || empty($email) || empty($pwd) || empty($pwdRepeat)) {
        return true;
    }
    else{
        return false;
    }
}

//checks if the email format is valid
function invalidEmail($email){
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }
    else{
        return false;
    }
}

//checks that the entered passwords are matching
function pwdMatch($pwd, $pwdRepeat){
    if ($pwd !== $pwdRepeat) {
       return true;
    }
    else{
        return false;
    }
}

//checks if an email has already been used by another user
function emailExists($conn, $email){
    $sql ="SELECT * FROM users WHERE usersEmail = ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("location: ../index.php?error=stmtfailed");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $resultData = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($resultData)) {
        return $row;
    }
    else{
        $result = false;
        return $result;
    }

    mysqli_stmt_close($stmt);

}

//Function that creates a new user
function createUser($conn, $name, $sname, $email, $pwd, $type){
    $sql ="INSERT INTO users (usersName, usersSname, usersEmail, usersPwd, userType, auth) VALUES (?, ?, ?, ?, ?, ?);";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("location: ../index.php?error=stmtfailed2");
        exit();
    }

    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT); //hashes the password
    $status = 0;
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $sname, $email, $hashedPwd, $type, $status); // ss refers to 2 strings, if 3 were used only 1 s would be used
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    


}

//checks if the input boxes are empty
function emptyInputLogin($username, $pwd){
    if (empty($username) || empty($pwd)) {
        return true;
    }
    else{
       return false;
    }
}

//Function to log a user in
function loginUser($conn, $email, $pwd) {
    $emailExists = emailExists($conn, $email);

    if ($emailExists === false) {
        header("location: ../index.php?error=wronglogin");
        exit();
    }

    $pwdHashed = $emailExists["usersPwd"];
    $checkPwd = password_verify($pwd, $pwdHashed);

    if ($checkPwd === false) {
        header("location: ../index.php?error=wrongpassword");
        exit();
    }
    else if($checkPwd === true) {
        session_start();
        $sql = "SELECT * FROM users
            WHERE usersEmail='$email'";
        $data = mysqli_query($conn, $sql);
        if (mysqli_num_rows($data) > 0) {
            $row = mysqli_fetch_array($data);
            $id = $row["usersId"];
            $name = $row["usersName"];
            $sname = $row["usersSname"];
            $uid = $row["usersId"];
            $type = $row["userType"];
            $auth = $row["auth"];
        }
        $_SESSION["id"] = $id; 
        $_SESSION["loggedIn"] = 1;
        $_SESSION["name"] = $name;
        $_SESSION["sname"] = $sname;
        $_SESSION["type"] = $type;
        $_SESSION["auth"] = $auth;
        if($type == "Student"){
            header("location: ../home.php");
            exit();
        }
        if($type == "Tutor"){
            header("location: ../tutorSettings.php");
            exit();
        }
        if($type == "Admin"){
            header("location: ../admin.php");
            exit();
        }
        

    }

}

//function that gets a course name from its ID
function getCourseName($conn, $id){
    $sql = "SELECT * FROM courses
            WHERE courseId = '$id'";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data)>0){
        $row = mysqli_fetch_array($data);
        $name = $row["name"];
        return $name;
    }
}

//function that gets a course description from its ID
function getCourseDesc($conn, $id){
    $sql = "SELECT * FROM courses
            WHERE courseId = '$id'";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data)>0){
        $row = mysqli_fetch_array($data);
        $desc = $row["courseDesc"];
        return $desc;
    }
}

//checks if a student is authorised on a course using their ID and the course ID
function isStudentAuth($conn, $userId, $courseId){
    $sql = "SELECT * FROM studentsOnCourses
            WHERE courseId = '$courseId' AND usersId=$userId";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data)>0){
        $row = mysqli_fetch_array($data);
        $status = $row["authorised"];
        return $status;
    }
}

//enrols a student onto a course
function courseEnrol($conn, $enrolCourseId, $enrolUserId) {
	$enrolDate = date("Y-m-d H:i:s");

	$sql = "INSERT INTO studentsOnCourses
			VALUES('$enrolUserId', '$enrolCourseId', '$enrolDate', '0')";
	if (mysqli_query($conn, $sql)) {
		echo "<p>You have been enrolled onto "; echo  getCourseName($conn, $enrolCourseId); echo "</p>";
	}
	else echo mysqli_error($conn);
}

//checks if a student is on a course
function courseCheck($conn, $courseId, $userId){
    $check = 0;
    $sql = "SELECT * FROM studentsOnCourses
    WHERE courseId = '$courseId' AND usersId = '$userId'";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data)>0){
        if(isStudentAuth($conn, $userId, $courseId)==1){
            $check=1;
        }
    }
    return $check;
}

//Function to change a users password
function passwordChange($conn, $id, $currentPass, $newPass, $newPassRepeat){
    //$id = $_SESSION["id"];
    $sql = "SELECT * FROM users
            WHERE usersId='$id'";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data) > 0) {
        $row = mysqli_fetch_array($data);
        $checkPwd = password_verify($currentPass, $row["usersPwd"]);
        if($checkPwd === true){
            if(pwdMatch($newPass, $newPassRepeat) === false){
                $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
                mysqli_query($conn,"UPDATE users set usersPwd='$hashedPass' WHERE usersId='$id'");
                header("location: ../settings.php?error=none");
                exit();
            } else{
                header("location: ../settings.php?error=passwordsdontmatch");
                exit();
            }
        } else{
            header("location: ../settings.php?error=incorrectpassword");
                exit();
        }
    }

}

//Function to change a users name
function nameChange($conn, $id, $name, $sName){
    $sql = "SELECT * FROM users
            WHERE usersId='$id'";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data) > 0) {
        $row = mysqli_fetch_array($data);
        if(empty($name) || empty($sName)){
            header("location: ../settings.php?error=emptyinput");
            exit();
        } else{
            mysqli_query($conn,"UPDATE users set usersName='$name' WHERE usersId='$id'");
            mysqli_query($conn,"UPDATE users set usersSname='$sName' WHERE usersId='$id'");   
            $_SESSION["name"] = $name;
            $_SESSION["sname"] = $sName;
            header("location: ../settings.php?error=none");
            exit();
        }
    }

}

//Function to upload a file to a course
function uploadResource($conn) {
	if (isset($_FILES["uploadFile"])) {
		$file = $_FILES["uploadFile"];
		$fileName = $file["name"];
		$tmpName = $file["tmp_name"];
		$fileSize = $file["size"];
		$pathInfo = pathinfo($fileName);
		$extension = $pathInfo["extension"];
		$upDate = date("Y-m-d H:i:s");
        $courseId = $_POST["courseId"];
        $fname = $_SESSION["name"];
        $sname = $_SESSION["sname"];

		if (move_uploaded_file($tmpName, "uploads/$fileName")) {
			echo "<p style='color: green;'>File $fileName successfully uploaded.</p>";


			$sql = "INSERT INTO resources(name, extension, courseId, uploadDate, size, fname, sname)
					VALUES('$fileName', '$extension', '$courseId', '$upDate', '$fileSize', '$fname', '$sname')";
			if (mysqli_query($conn, $sql)) echo "<p style='color: green;'>Resource uploaded.</p>";
			else echo "<p style='red: green;'>Resource upload failed." . mysqli_error($conn) . "</p>";


			if ($fileName == "quiz.txt") {
				header("Location:  show_quiz.php");
			} 
		}
	}
}

//Function to upload a post to a course
function uploadPost($conn) {
	if (isset($_FILES["uploadImg"])) {
		$file = $_FILES["uploadImg"];
		$fileName = $file["name"];
		$tmpName = $file["tmp_name"];
		$fileSize = $file["size"];
		$pathInfo = pathinfo($fileName);
		$extension = $pathInfo["extension"];
        if($extension !== "png" && $extension !== "PNG" && $extension !== "jpg" && $extension !== "JPEG" && $extension !== "bmp" && $extension !== "svg"){
            header("location: ../tutorSettings.php?error=incorrectimagetype");
            exit();
        }
		$upDate = date("Y-m-d H:i:s");
        $courseId = $_POST["courseId"];
        $title = $_POST["title"];
        $content = $_POST["content"];

		if (move_uploaded_file($tmpName, "postImg/$fileName")) {
			echo "<p style='color: green;'> $fileName successfully uploaded.</p>";


			$sql = "INSERT INTO posts(title, imgName, extension, courseId, content, uploadDate)
					VALUES('$title', '$fileName', '$extension', '$courseId', '$content', '$upDate')";
			if (mysqli_query($conn, $sql)) echo "<p style='color: green;'>Post has been added to course page.</p>";
			else echo "<p style='red: green;'>Post has not been added to course page." . mysqli_error($conn) . "</p>";


			if ($fileName == "quiz.txt") {
				header("Location:  show_quiz.php");
			} 
		}
		else {
			echo "<p style='color: red;'>$fileName upload failed.</p>";
		}
	}
}

//Displays all posts from a specific course
function displayPosts($conn, $courseId){
    $query = "SELECT * FROM posts WHERE courseId = '$courseId' ORDER BY id ASC";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $date = $row["uploadDate"];
            $title = $row["title"];
            $content = $row["content"];
            $img = $row["imgName"];
            $id = $row["id"];
            echo "
            <div class='article' id='post$id'>
            <div class='articleInformation'>
                <h3>$date</h3>
            </div>
            <div class='articleTitle'>
                <h1 class='articleTitleMain'>$title</h1>
            </div>
            <div class='articleImg'>
                <img src='postImg/$img'>
            </div>
            <div id='textArea'>
                <p class='SmallText'>
                    $content
                </p>
            </div>
        </div> ";


        }
    } 
}

//Displays all resources for a specific course
function displayResources($conn, $courseId){
    $query = "SELECT * FROM resources WHERE courseId = '$courseId' ORDER BY id ASC";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $date = $row["uploadDate"];
            $name = $row["name"];
            $size = $row["size"];
            $sizeMB = number_format(($size/100000), 2);
            $fname = $row["fname"];
            $sname = $row["sname"];
            echo "
            <hr style='width: 100%; border-radius: 10px;', size='1', color=grey>
            <div class='downLoadRow'>
                <div class='row'> 
                    <div class='title'>
                        <img class='fileImg' src='styling/Assets/file.png'>
                        <a href='uploads/$name' target='_blank'><p class='fileName'>$name</p></a>
                    </div>
                    <div class='meta'>
                        <div class='rowDateUpload'>
                            <p>$date</p>
                        </div>
                        <div class='rowFileSize'>
                            <p>$sizeMB MB</p>
                        </div>
                        <div class='rowAuthor'>
                            <p>$fname $sname</p>
                        </div>
                    </div>
                </div>
            </div>";
        }
    }
}

//Creates the quiz container
function displayQuizContainer($conn, $id){
    for($i = 1; $i <= 4; $i++){
        if(courseCheck($conn, $i, $id)){
            echo '
            <div class="quizContainer">
                <div class="quizBoxTitle">
                    <p>'; echo getCourseName($conn, $i); echo'</p>
                </div>
                '; displayQuizRow($conn, $i); echo'
            </div>';

        }
    }
}

//Creates a quiz row
function displayQuizRow($conn, $courseId){
    echo "
    <div class='rowOfQuizzes'>
        "; showQuizzes($conn, $courseId); echo"
    </div>";

}

//Displays the grades of a student on each of their courses
function displayGrades($conn, $id){
    for($i = 1; $i <= 4; $i++){
        if(courseCheck($conn, $i, $id)){
            //displayGrade($conn, $id, $i);
            echo '<h4>'; echo getCourseName($conn, $i); echo '</h4>';
            echo '<div class="gradesContain">';
            if($i == 1){
                echo'<div class="generalInfomation bluePurple">';
            }
            if($i == 2){
                echo'<div class="generalInfomation jag">';
            }
            if($i == 3){
                echo'<div class="generalInfomation wave">';
            }
            if($i == 4){
                echo'<div class="generalInfomation triangle">';
            }

            echo'
            <div class="averageCompletion">
                <p class="averageCompleteTitle">Average<br>Completion:</p>';
                getCourseGrade($conn, $_SESSION["id"], $i);
            echo '
            </div>
            <div class="quizTakenContainer">
                <p>Quizzes Taken: </p>';
                $sql = "SELECT * FROM studentGrades WHERE courseID='$i' AND studentID='$id'";
                $data = mysqli_query($conn, $sql);
                $done = mysqli_num_rows($data);
                echo'<p class="spaceLeft">'; echo $done; echo '</p>
            </div>
            <div class="quizzesLeftContainer">
                <p>Quizzes Left: </p>';
                $sql2 = "SELECT * FROM quizList WHERE courseID ='$i'";
                $data2 = mysqli_query($conn, $sql2);
                $left = mysqli_num_rows($data2) - $done;
                echo' <p class="spaceLeft">'; echo $left; echo '</p>
            </div>
            </div>
            <div class="gradesInformation">
            <hr style="width: 100%; border-radius: 10px;", size="1", color=grey>
            <div class="titleSection">
                <div class="assementTitle">
                    <p>Assessment Title</p>
                </div>
                <div class="percentageTitle">
                    <p>Percentage</p>
                </div>
            </div>
            <div class="gradesSection">';
            $sql = "SELECT * FROM studentGrades WHERE courseID='$i' AND studentID='$id'";
            $data = mysqli_query($conn, $sql);
            if(mysqli_num_rows($data) > 0){
                while($row = mysqli_fetch_assoc($data)){
                    $score = $row["score"];
                    $quizId = $row["quizID"];
                    $sql2 = "SELECT * FROM quizList WHERE quizID ='$quizId'";
                    $data2 = mysqli_query($conn, $sql2);
                    if (mysqli_num_rows($data2) > 0) {
                        $row = mysqli_fetch_array($data2);
                        $quizName = $row["quizName"];
                        echo'
                        <hr style="width: 100%; border-radius: 10px;", size="1", color=grey>
                        <div class="gradesRow">
                            <p class="assementValue">'; echo $quizName; echo'</p>
                            <p class="percentageValue">'; echo $score; echo'% </p>
                        </div>';
                    }
                }
            }
            echo'
            </div>
            </div>
            </div>';   
                
        }
    }
}

function authUsersForm($conn){
    $sql = "SELECT * FROM users WHERE auth = 0";
    $data = mysqli_query($conn, $sql);
    if (mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_array($data)) {
            extract($row);
            echo "<p>".$usersName ." " . $usersSname. " </p>";
            echo "<form style='margin-left 0; margin-top: 0;' method='post' action=''>";
            echo "<input type='hidden' name='usersId' value='$usersId'/>";
            echo "<input class='buttonPress' type='submit' value='Authorize'/>";
            echo "</form>";
            echo "<br/>";
        
        }
    }
}

function authUsers($conn) {
    if (isset($_POST["usersId"])) {
        extract($_POST);
        $sql = "UPDATE users
                SET auth='1'
                WHERE usersId='$usersId'";
        if (mysqli_query($conn, $sql)) {
        }
        else "Something went wrong: " . mysqli_error($conn);
    }
}

//Displays a list of students that are yet to be authorised
function authoriseStudentsForm($conn) {
    $sql = "SELECT users.usersId AS studentId,
                   users.usersName AS studentName,
                   users.usersSname AS studentSurname,
                   courses.courseId AS courseId
            FROM studentsOnCourses
            INNER JOIN users
            ON users.usersId=studentsOnCourses.usersId
            INNER JOIN courses
            ON courses.courseId=studentsOnCourses.courseId
            WHERE studentsOnCourses.authorised='0'";
        $data = mysqli_query($conn, $sql) or die(mysqli_error($conn));
        $numRows = mysqli_num_rows($data);
    
        if ($numRows > 0) {
    
            while ($row = mysqli_fetch_array($data)) {
                extract($row);
                echo "<p>".$studentName ." " . $studentSurname. ": ". getCourseName($conn, $courseId);"</p>";
                echo "<form style='margin-left 0; margin-top: 0;' method='post' action=''>";
                echo "<input type='hidden' name='courseId' value='$courseId'/>";
                echo "<input type='hidden' name='studentId' value='$studentId'/>";
                echo "<input class='buttonPress' type='submit' value='Authorize'/>";
                echo "</form>";
                echo "<br/>";

            }
    
        }
        else {
            echo "There are no enrollments currently to be authorize.";
        }
    }

//Function that processes student authorisation
function authoriseStudents($conn) {
    if (isset($_POST["courseId"]) && isset($_POST["studentId"])) {
        extract($_POST);
        $sql = "UPDATE studentsOnCourses
                SET authorised='1'
                WHERE usersId='$studentId'
                AND courseId='$courseId'";
        if (mysqli_query($conn, $sql)) {
        }
        else "Something went wrong: " . mysqli_error($conn);
    }
}

//Function to display all quizzes on course
function showQuizzes($conn, $courseId){
    $boxDesign="";
    if($courseId==1){
        $boxDesign = "<div class='quizBoxTop bluePurple'>";
    }
    if($courseId==2){
        $boxDesign = "<div class='quizBoxTop circleBack'>";
    }
    if($courseId==3){
        $boxDesign = "<div class='quizBoxTop triangle'>";
    }
    if($courseId==4){
        $boxDesign = "<div class='quizBoxTop wave'>";
    }
    $query = "SELECT * FROM quizlist WHERE courseID = '$courseId' ORDER BY quizID ASC  ";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $ans = $row['quizID'];
        $name = $row['quizName'];
        $course = $row['courseID'];
        echo"
            <div class='quizBox'>";
            echo $boxDesign; echo "                    
                        <h4>"; echo $name; echo "</h4>
                    </div>
                    <div class='quizBoxBottom'>
                        <form method='post' action='quiztest.php?quizNum=".$ans."'>
                        <button type='submit' class='quizTry'>Attempt Quiz</button>
                        </form>
                    </div>
                </div>
            ";
        }
    } else {
        echo"<p>No quizzes to show</p>";
    }
}

//Displays the course page
function showCoursePage($conn, $courseID, $courseName){
    
    echo '            
    <div id="'. $courseName.'"> '; 
    displayPosts($conn, $courseID);
    echo '
    <div class="article">
            <h1 class="downLoadsTitle">
                Downloads
            </h1>
            <div class="downLoadArea">
                <hr style="width: 100%; border-radius: 10px;", size="1", color=grey>
                <div class="downLoadAreaTitle">
                    <div class="name">
                        <p>Name</p>
                    </div>
                    <div class="uploaded">
                        <p>Uploaded</p>                        
                    </div>        
                    <div class="size">
                        <p>Size</p>
                    </div>        
                    <div class="author">
                        <p>Author</p>
                    </div>
                </div>';

                displayResources($conn, $courseID);
                echo '
                <hr style="width: 100%; border-radius: 10px;", size="1", color=grey>
                </div>
            </div>
        </div>
    </div>';
}

//Shows a list of the most recent quizzes
function showRecentQuiz($conn, $userId){

    $count = 0;
    $courses = array(0, 0, 0, 0);
    if(courseCheck($conn, 1, $userId)){
        $courses[0] = 1;
    }
    if(courseCheck($conn, 2, $userId)){
        $courses[1] = 2;
    }
    if(courseCheck($conn, 3, $userId)){
        $courses[2] = 3;
    }
    if(courseCheck($conn, 4, $userId)){
        $courses[3] = 4;
    }
    
    $sql = "SELECT * FROM quizList WHERE
            courseID = '$courses[0]' OR
            courseID = '$courses[1]' OR
            courseID = '$courses[2]' OR
            courseID = '$courses[3]' ORDER BY quizID desc";
    $data = mysqli_query($conn, $sql);
    if(mysqli_num_rows($data) > 0){
        while(($row = mysqli_fetch_array($data)) && ($count <= 4)){
            extract($row);
            echo'
            <hr style="width: 100%; border-radius: 10px;", size="1", color=grey>
            <div class="downLoadRow">
                <div class="row"> 
                    <div class="title">
                        <img class="fileImg" src="styling/Assets/test.png">
                        <p class="fileName">'; echo $quizName; echo'</p>
                    </div>
                    <div class="meta">
                        <div class="rowCourse">
                            <p>'; echo getCourseName($conn, $courseID); echo'</p>
                        </div>
                    </div>
                </div>
            </div>
            ';
            $count++;
        }
    }
    
}

//Gets a students grades from all courses or a specific course
function getCourseGrade($conn, $userId, $courseID){
    $overall = 0;
    $mean=0;
    if($courseID == 0){ //For all courses
        $sql = "SELECT * FROM studentGrades WHERE studentID = $userId";
    }
    if($courseID > 0){ //For a specific course
        $sql = "SELECT * FROM studentGrades WHERE courseID = $courseID AND studentID = $userId";
    }
    $data = mysqli_query($conn, $sql);
    $quizzesDone = mysqli_num_rows($data);
    if(mysqli_num_rows($data) > 0){
        while($row = mysqli_fetch_array($data)){
            $score = $row["score"];
            $overall = $overall + $score;
        }
    }
    if($overall != 0){
        $mean = $overall/$quizzesDone;
    }

    echo '
    <div class="progressBar">
        <div id="progressCircle">
            <div style="background-image: conic-gradient(#7f5af0 ' . $mean . '%, rgb(0, 0, 0) 0);" class="circle">
                <div class="inner">
                    <p>' . $mean . '%</p>
                </div>
            </div>
        </div>
    </div>    
    ';
}

//Displays quick links for each post at the top of a course page
function quickLinks($conn, $courseId){
    $query = "SELECT * FROM posts WHERE courseId = '$courseId' ORDER BY id ASC";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $title = $row["title"];
            $id = $row["id"];
            echo "
            <a href='#post$id'>$title<a>";
        }
    } 
}
