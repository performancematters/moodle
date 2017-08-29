<?php
    require_once("../config.php");
    require_once("../course/lib.php");
    require_once("tnllib.php");
    require_once("../lib/moodlelib.php");
    global $CFG, $USER;
    $loggerName = "courseOps : ";
    
    /*
    **** REQUIRED PARAMETERS ****
    1. op  = operation
    2. md5 = md5 hash

    **** OPTIONAL PARAMETERS ****
    3. cid       = course Id
    4. cname     = course name
    5. sname     = short name
    6. cdesc     = course description
    7. catid     = category id
    8. year      = for start date
    9. month     = for start date
    10. day      = for start date

    **** OP OPTIONS ****
    1. create   create new course
    2. delete   delete course
    3. update   update course
    */
    
    $cid = (isset($_POST['cid']) ? $_POST['cid'] : "");
    $cname = (isset($_POST['cname']) ? $_POST['cname'] : "");
    $sname = (isset($_POST['sname']) ? $_POST['sname'] : "");
    $cdesc = (isset($_POST['day']) ? $_POST['cdesc'] : "");
    $year = (isset($_POST['day']) ? $_POST['year'] : "");
    $month = (isset($_POST['month']) ? $_POST['month'] : "");
    $day = (isset($_POST['day']) ? $_POST['day'] : "");
    $md5 = (isset($_POST['md5']) ? $_POST['md5'] : null);
    $catid = (isset($_POST['catid']) ? $_POST['catid'] : null);
    $op = (isset($_POST['op']) ? $_POST['op'] : null);
    $userid = (isset($_POST['userid']) ? $_POST['userid'] : null);
    $format = (isset($_POST['format']) ? $_POST['format'] : null);
    $numsections = (isset($_POST['numsections']) ? $_POST['numsections'] : null);

if ($op=='')
{
    $cid = (isset($_GET['cid']) ? $_GET['cid'] : "");
    $cname = (isset($_GET['cname']) ? $_GET['cname'] : "");
    $sname = (isset($_GET['sname']) ? $_GET['sname'] : "");
    $cdesc = (isset($_GET['day']) ? $_GET['cdesc'] : "");
    $year = (isset($_GET['day']) ? $_GET['year'] : "");
    $month = (isset($_GET['month']) ? $_GET['month'] : "");
    $day = (isset($_GET['day']) ? $_GET['day'] : "");
    $md5 = (isset($_GET['md5']) ? $_GET['md5'] : null);
    $catid = (isset($_GET['catid']) ? $_GET['catid'] : null);
    $op = (isset($_GET['op']) ? $_GET['op'] : null);
    $userid = (isset($_GET['userid']) ? $_GET['userid'] : null);
    $format = (isset($_GET['format']) ? $_GET['format'] : null);
    $numsections = (isset($_GET['numsections']) ? $_GET['numsections'] : null);
}

    //**** RETURN ERROR CODES ****
    $COURSE_DEEP_COPY_ERROR   = "-1";
    $COURSE_UPDATE_ERROR      = "-2";
    $COURSE_DELETE_ERROR      = "-3";
    $NO_OPERATION_ERROR       = "-4";
    $COURSE_ID_DOES_NOT_EXIST = "-5";
    $NOT_IMPLEMENTED          = "-6";

    // BUILD MD5
    $checkMd5 = md5( $op . $CFG->courseOpsMd5 );
    
    // CHECK MD5
    if( $md5 == $checkMd5 )
    {
        $course = new stdClass();

        if( $cid != "" )        
            $course->id = $cid;

        if( $cname != "" )
            $course->fullname = $cname;

        if( $sname != "" )
            $course->shortname = $sname;
    
        if( $cdesc != "" )
            $course->summary = $cdesc;
        else
            $course->summary = "Online Course";
        
        if( $catid != "" )
            $course->category = $catid;

        if( $year != "" && $month != "" && $day != "" )
            $course->startdate = make_timestamp( $year, $month, $day );
    
        // There is a bug in the section create logic that causes 
        // a course update to be called when course create should be
        // called.
        if ( $op == "update" && $cid == "" ) {
            $op = "create";
        }

        switch ( $op )
        {
            //********* CREATE BLANK COURSE **** //
            case 'create':
                error_log($loggerName . 'Attempting to create new course');
                $course->moodlesso=1;
                $course->category=1;
                $course->idnumber='';
                $course->startday=$day;
                $course->startmonth=$month;
                $course->startyear=$year;
                $course->enrolperiod=0;
                $course->groupmode=0;
                $course->groupmodeforce=0;
                $course->password='';
                $course->guest=0;
                $course->hiddensections=1;
                $course->newsitems=5;
                $course->showgrades=1;
                $course->showreports=0;
                $course->maxbytes=20971520;
                $course->metacourse=0;
                $course->modinfo='a:0:{}';
                $course->id='';
                $course->sesskey='j1wNvfS2bu';
    
                if ($format != "") 
                    $course->format      = $format;
                else 
                    $course->format      = "topics";
        
                if ($numsections != "")
                    $course->numsections = $numsections;
                else 
                    $course->numsections = 10;
            
                // check if the shortname already exist - this is a common issue and indicates that the course
                // exists in the system, but for some reason isn't assocaited with this course (we 'guarantee'
                // unique short names by using the course's section_id coupled with the section name).
                if (!empty($course->shortname)) {
                    if ($DB->record_exists('course', array('shortname' => $course->shortname))) {
                            $course = $DB->get_record('course', array('shortname' => $course->shortname));
                            error_log($loggerName . 'Course shortname already taken: '. $course->shortname . ', returning course_id: ' . $course->id);
                            echo $course->id;
                            break;
                        } else {
                            if (!$course = create_course($course)) {
                                error_log($loggerName . 'Failed to create_course!');
                                print_error('coursenotcreated');
                            }       
                        }
                }
                
                $context = get_context_instance(CONTEXT_COURSE, $course->id);
    
                $user = new stdClass();
                $user = make_sure_user_exists($userid);
                if (isset($user->id)) {
                    error_log($loggerName . 'User successfully retrieved with id ' . $user->id);
                } else {
                    echo "-88";
                    break;
                }

                // assign default role to creator if not already having permission to manage course assignments
                if (!has_capability('moodle/course:view', $context) or !has_capability('moodle/role:assign', $context)) {
                    error_log($loggerName . 'Assigning teacher / creator role ID ' . $creatornewroleid . ' for user id ' . $user->id . ' and context id ' . $context->id);
                    role_assign($creatornewroleid, $user->id, $context->id);
                }

                error_log($loggerName . 'Successfully created course with ID ' . $course->id);  
                echo $course->id;
                break;

            //********** UPDATE COURSE **** //
            case 'update':
                update_course($course);             
                error_log($loggerName . 'Successfully updated course with ID ' . $course->id);  
                echo $course->id;
                break;

            //********** DELETE COURSE **** //
            case "delete":
                // Returns 'true' if course is deleted, 'false' if it isn't
                echo delete_course($course, false);
                break;

            //********** NO OP **** //
            default:
                echo $NO_OPERATION_ERROR;
        }
    }
?>
