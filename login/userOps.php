<?php
    require_once("../config.php");
    require_once("tnllib.php");
    require_once("../lib/enrollib.php");
    
    global $CFG, $USER, $student_role, $admin_role, $teacher_role;
    $loggerName = "userOps : ";
    /*
    
    Integration Notes:
        Updated role_assign calls for 2.3.3 by removing hard-coded '0' for $groupid setting;
        this field appears to be removed from the 2.3.3 role_assign call.
        
        Updated role_unassign by removing hard-coded '0', also, role_unassign 
        is deprecated in this edition this is slated to be removed in Moodle 2.5.

    **** REQUIRED PARAMETERS ****
    1. op  = operation
    2. tnlid = user Id
    3. md5 = md5 hash
    
    **** OPTIONAL PARAMETERS ****
    4. cid         = course Id
    5. fname       = first name
    6. lname       = last name
    7. email       = email
    8. institution = users school site

    **** OP OPTIONS ****
    1. enrol
    2. unenrol
    3. addteacher
    4. removeteacher
    
    5. create
    6. delete
    7. update
    
    **** RETURN ERROR CODES ****
    -1  = Could not add user to db.
    -2  = User already exists.
    -3  = User not deleted/removed from moodle.
    -4  = Not a valid operation.
    -5  = User could not be updated.
    -6  = User not un-enrolled.
    -7  = User enroll error.
    -8  = Teacher remove error.
    -9  = Teacher add error.
    -10 = User does not exist.
    */
    $op=trim($_POST['op']);
    $tnlid=trim($_POST['userid']);
    $md5=trim($_POST['md5']);

    $cid = (isset($_POST['cid']) ? trim($_POST['cid']) : "");
    $fname = (isset($_POST['fname']) ? trim($_POST['fname']) : "");
    $lname = (isset($_POST['lname']) ? trim($_POST['lname']) : "");
    $email = (isset($_POST['email']) ? trim($_POST['email']) : "");
    
    // There is a 40 char limit to this value
    $institution = substr((isset($_POST['institution']) ? trim($_POST['institution']) : ""), 0, 39);

    // Useful for debug if you want to copy and paste the request and not post the data.
    if ($op=='')
    {
      $op=$_GET['op'];
      $tnlid=$_GET['userid'];
      $md5=$_GET['md5'];
      $cid=$_GET['cid'];
      $fname=$_GET['fname'];
      $lname=$_GET['lname'];
      $email=$_GET['email'];
      // There is a 40 char limit to this value
      $institution = substr((isset($_GET['institution']) ? trim($_GET['institution']) : ""), 0, 39);
    }

    $USER_NOT_ADDED       = "-1";
    $USER_ALREADY_EXISTS  = "-2";
    $USER_NOT_DELETED     = "-3";
    $NO_OPERATION_ERROR   = "-4";
    $USER_NOT_UPDATED     = "-5";
    $USER_UNENROLL_ERROR  = "-6";
    $USER_ENROLL_ERROR    = "-7";
    $TEACHER_REMOVE_ERROR = "-8";
    $TEACHER_ADD_ERROR    = "-9";
    $USER_DOES_NOT_EXIST  = "-10";

    // BUILD MD5
    $checkMd5 = md5( $op . $CFG->userOpsMd5 );
    
    // CHECK MD5
    if( $md5 == $checkMd5 )
    {
    error_log($loggerName . 'tnlid: ' . $tnlid);
    error_log($loggerName . 'op: ' . $op);
        $user = new stdClass();
        $user = make_sure_user_exists($tnlid, $fname, $lname, $email, $institution);
        $course = new stdClass();
        $course->id = $cid;

        switch ( $op )
        {
            //********* CREATE USER **** //
            case "create" :
                error_log($loggerName . 'Creating or retrieving user for tnl person_id ' . $tnlid);
                if (isset($user->id)) {
                    error_log($loggerName . 'User successfully retrieved with id ' . $user->id);
                } else {
                    error_log($loggerName . 'Failure creating user!');
                }
                
                echo $user->id;
             break;

            //********** DELETE USER FROM MOODLE **** //
            //Do we really want to implement this.. seems risky (to delete ALL person data for this user)
            case "delete" :
                echo $USER_NOT_DELETED;
                break;

            //********** UPDATE USER **** //
            case "update" :
                // It's already been done!
                echo $user->id;
            break;

            //********** ENROL USER FOR MOODLE COURSE **** //
            case "enrol" :
                tnlEnroll($tnlid, $user->id, $course->id);
                echo '1';
                break;
            
            //********** UNENROL USER FROM A MOODLE COURSE **** //
            case "unenrol" :       
                $current_context = get_context_instance(CONTEXT_COURSE, $course->id);
                tnlUnenrol($user->id, $course->id);
                echo '1';
                break;
             
            case "registerInstructor" :
                enrol_try_internal_enrol($course->id, $user->id, $teacher_role);        
                echo '1';
                break;
                
            case "unregisterInstructor" :
                tnlUnenrol($user->id, $course->id);
                echo '1';
                break;
        }
    }
    
?>

