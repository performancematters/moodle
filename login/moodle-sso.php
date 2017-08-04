<?php
    require_once("../config.php");
    require_once("tnllib.php");
    require_once($CFG->libdir.'/filelib.php');
    require_once("../lib/enrollib.php");
    
    require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');
    $loggerName = "moodle-sso : ";
/*
TNL-Moodle Integration 2.3.3 information :

This is managed by the moodle-2.3.3 TNL Mercurial project; you can see all
checkins there, too.

TNL File Additions - NEW files:

config.php              A copy of config-dist.php with  <<unconfigured:: 
    used to mark substitution spots and a few other changes (dbtype specification).
Login/courseOps.php     Section creation and management.
Login/userOps.php       User creation and management.
Login/tnllib.php        User lookup and creation function
Login/moodle-sso.php    'Navigate Section' and login functionality.


TNL Modifications to Moodle source code to make the integration work:

Lib/setuplib.php        Added an include to allow custom code to work 
Lib/db/install.php      Configured default admin (tnl.admin) user and password
Lib/outputrenderers.php Altered logout URL to redirect to portal.
Lib/moodlelib.php       Remove block of code that checks $max_logins to prevent 'max login attempts reached' msg when navigating to sections repeatedly
Auth/manual/auth.php    Changed default return for can_change_password() from true to false to disable users from breaking our SSO via password changes.
Login/logout.php        Logout changed to redirect users to portal and to use custom config entries in $CFG.

The following needs to be in default_properties.txt:
#########################################################
# Moodle Configuration Points for Moodle 2.3.3
#
asset.sso.moodle.url=/moodle/login/moodle-sso.php?
asset.sso.moodle.useridprefix=
asset.sso.moodle.md5append=.4ZNfwxy4IQ
asset.course.moodle.url=/moodle/login/courseOps.php?
asset.user.moodle.url=/moodle/login/userOps.php?

    // REQUIRED PARAMETERS
    $id          = users id
    $fname   = user first name
    $lname    = user last name
    $email       = user email
    $institution = user institution/school
    $amd5        = md5 hash for the admin login
    $nmd5        = md5 hash for the navigator login
    $cmd5        = md5 hash for the course creator ("author") login
    $course_id     = course section

    // OPTIONAL PARAMETERS
    $showcourselist = used to show/hide access to other course - AJA 7/21/13 - Doesn't work in Moodle 2.3!
    $returnserver = host to return to; if not present, then use $CFG->uportalURL
*/
    $_SESSION['USER']=0;
    global $student_role, $admin_role, $teacher_role;
    // IMPORTANT NOTE : id is different than tnlid
    // id is internal to the moodle person table
    // tnlid is interal to the tnl system person table

    $tnlid       = trim($_GET['userid']);
    $fname   = trim($_GET['fname']);
    $lname    = trim($_GET['lname']);
    $email       = trim($_GET['email']);
	
	//echo $fname;

    // There is a 40 char limit to this value
    $institution = substr((isset($_GET['institution']) ? trim($_GET['institution']) : ""), 0, 39);

    $md5         = trim($_GET['md5']);
    $course_id   = trim($_GET['section']);
    
    $amd5 = (isset($_GET['amd5']) ? $_GET['amd5'] : "");
    $nmd5 = (isset($_GET['nmd5']) ? $_GET['nmd5'] : "");
    $cmd5 = (isset($_GET['cmd5']) ? $_GET['cmd5'] : "");
    
    // AJA 7/21/13 - Doesn't work in 2.3.3!
    $showcourselist = (isset($_GET['showcourselist']) ? $_GET['showcourselist'] : ""); 
    
    $returnserver = (isset($_GET['returnserver']) ? $_GET['returnserver'] : "");
	

    $author   = "false";
    $teacher  = "false";
    $admin    = "false";


	
    $username = substr($fname, 0, 1);
	
    $username = $username . $lname;

	
    $username = strtolower($username);
	
    // BUILD MD5   
    $checkMd5     = md5($tnlid . $CFG->ssoMd5Hash);
    $checkAdmin   = md5($tnlid . ".a" . $CFG->ssoMd5Hash);
    $checkAuthor  = md5($tnlid . ".c" . $CFG->ssoMd5Hash);
    $checkTeacher = md5($tnlid . ".n" . $CFG->ssoMd5Hash);



	
    error_log($loggerName . 'Beginning md5 and ability checks for person ID ' . $tnlid);
    // CHECK MD5 //
    if( $md5=="" || $md5 != $checkMd5 )
    {
      error_log($loggerName . 'Bad MD5 - config error?  Rejecting Moodle login!');
      die("Uh-oh!  A system integration error prevented me from logging you into Moodle.  Please relay this message to your administrator or support@truenorthlogic.com.");
    }

    error_log($loggerName . 'Creating or retrieving user for tnl person_id ' . $tnlid);
    $user = make_sure_user_exists($tnlid, $fname, $lname, $email, $institution);
    if (isset($user->id)) {
        error_log($loggerName . 'User successfully retrieved with id ' . $user->id);
    }

		
	
    // $course_id == -1 indicates we should just log them into Moodle and direct them to the main moodle page
    if ($course_id == -1) {
        $SESSION->wantsurl = $CFG->wwwroot;
    } else {
        error_log($loggerName . 'Retrieving context for course_id ID ' . $course_id);
        $current_context = get_context_instance(CONTEXT_COURSE, $course_id);
        if (empty($current_context->id)) {
            error_log($loggerName . 'Context successfully retrieved with id ' . $current_context->id);
        }
    }

    // Most of the following uportalReturnURL and redirecting was copied from 1.5.3 and 1.3 versions
    if ($user) 
    {
        $USER = $user;
        $USER->loggedin = true;
        $USER->site = $CFG->wwwroot;  // for added security
        $USER->currentlogin = time();
        
    if ($course_id > 0) {
        // Enroll all users in the course.
        // tnlEnroll($tnlid, $user->id, $course_id);
                    
    // We update the DB with the users rights and roles, but we don't downgrade roles.
    // The values for $admin_role, $teacher_role are defined in tnllib.php
    // IF USER IS SUPER USER (ADMIN) UPDATE THE DB
    if( $amd5!="" && $amd5 == $checkAdmin )
    {
        error_log($loggerName . 'Setting site administrator status for user ' . $user->id);       
        // Assign site administrator status
        $admins = array();
        foreach(explode(',', $CFG->siteadmins) as $admin) {
            $admin = (int)$admin;
            if ($admin) {
                $admins[$admin] = $admin;
            }
        }

        $admins[$user->id] = $user->id;
        set_config('siteadmins', implode(',', $admins));
        role_assign($admin_role, $user->id, $current_context->id);
    }
    // IF USER IS COURSE AUTHOR UPDATE THE DB
    else if( $cmd5 != "" && $cmd5 == $checkAuthor)
    {
        $author = "true";
        $teacher = "true";
        $admin = "true";
        error_log($loggerName . 'Setting teacher roles for user ' . $user->id);
        role_assign($teacher_role, $user->id, $current_context->id);
    }
    // IF USER IS TEACHER (NAVIGATOR) UPDATE THE DB
    else if( $nmd5 != "" && $nmd5 == $checkTeacher)
    {
        $teacher = "true";
        error_log($loggerName . 'Setting teacher role for user ' . $user->id);
        role_assign($teacher_role, $user->id, $current_context->id);
    }
    }

    update_login_count();
	


    
	
    get_string_manager()->reset_caches();
        
						
		
        if (!update_user_login_times())
        {
            error("Error: could not update login records");
        }
		//alert($_SESSION['USER']);
		
			
		
        if (empty($_SESSION['USER']) || $_SESSION['USER']->id==0)
        {
            $_SESSION['USER'] = $USER;
        }

        set_moodle_cookie($USER->username);
        unset($SESSION->lang);

        if (empty($returnserver))
        {
            $_SESSION['uportalReturnURL'] = $CFG->uportalURL;
        }
        else
        {
            $_SESSION['uportalReturnURL'] = $CFG->uportalServer . $returnserver;
        }


		$_SESSION['courseAdmin']    = $author;
		$_SESSION['teacher']        = $teacher;
		$_SESSION['isloggedin']=$USER->id;
		redirect("$CFG->wwwroot/course/view.php?id=$course_id");
		
        reset_login_count();
        die;
    } 
    else 
    {
        echo "Error: the sso was not able to find or create your user";
    }

		

    
	
    exit;
?>
