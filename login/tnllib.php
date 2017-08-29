<?php

    //These values were taken from the mdl_role DB table
    $loggerName             =   "tnllib : ";
    $admin_role             =   1;
    $creatornewroleid       =   2;
    $teacher_role           =   3;
    $student_role           =   5;
    
    //If the user does not exist, create it
    //Return the id of the new user, or of the already existing user
    function make_sure_user_exists($tnlid, $firstname='', $lastname='', $email='', $institution='')
    {
        global $DB, $student_role, $teacher_role, $admin_role, $loggerName;
        $loggerName = "tnllib : ";
        $user = new stdClass();
        error_log($loggerName . 'Attempting to find user with username=' . $tnlid);
        
        if (!isset($tnlid)) {
            error_log($loggerName . '$tnlid is not set - cannot proceed!');
            return false;
        }
        
        $user->username    = $tnlid;
        
        //The username is the person_id, so it should be unique - do they exist?
        $found = $DB->record_exists('user', array('username'=>$user->username));

        //If we did not find the user in the system, create one and get it's id
        if( !$found )
        {
            error_log($loggerName . 'User not found, creating user.');
            $user = createUser($user, $tnlid, $firstname, $lastname, $email, $institution);
        }
        else
        {
            //If we did find the user, get it's id
            $rs = $DB->get_record("user", array("username"=>$user->username), '*', IGNORE_MISSING);
            $user->id=$rs->id;
            
            // If the lastname field isn't set, we aren't updating the user - just return the ID
             if (isset($lastname) && strlen($lastname) > 0) {
                $user = updateUser($user, $tnlid, $firstname, $lastname, $email, $institution);
            }
        }

        //Return the user object of the new user, or a basic object only containing the id of an already existing one
        error_log($loggerName . 'Returning user with ID ' . $user->id);
        return $user;
    }

    function updateUser($user, $tnlid, $firstname='', $lastname='', $email='noreply@truenorthlogic.com', $institution='') {
        global $DB, $student_role, $teacher_role, $admin_role, $loggerName;
        error_log($loggerName . 'User successfully retrieved with id ' . $user->id . ', updating user.');
        $user->firstname = (isset($firstname) ? $firstname : "");
        $user->lastname = (isset($lastname) ? $lastname : "");
        $user->email = (($email=='' || $email==' ') ? "noreply@truenorthlogic.com" : $email);
        $user->institution = (isset($institution) ? $institution : "");
        $user->city        = "N/A";
        # Persistent html editor; if you remove this, no special editor is attached
        $user->htmleditor  = 1;
        $user->auth        = "manual";

        if ($tnlid = "tnl.admin") {
            $user->password    = md5("Tn!m00dl3");
        } else {
            $user->password    = md5("m00d1p@ss");
        }

        if (!$DB->update_record('user', $user)) {
            error_log($loggerName . loggerName . 'Error updating user record!');
            error('Error updating user record');
        }
            
        error_log($loggerName . 'User successfully updated');
        return $user;
    }
    
    function createUser($user, $tnlid, $firstname='', $lastname='', $email='', $institution='') {
        global $DB, $student_role, $teacher_role, $admin_role, $loggerName;
        $user->firstname = (isset($firstname) ? $firstname : "");
        $user->lastname = (isset($lastname) ? $lastname : "");
        $user->email = (isset($email) ? $email : "noreply@truenorthlogic.com");
        $user->email = (($email==' ') ? "noreply@truenorthlogic.com" : $email);
        $user->institution = (isset($institution) ? $institution : "");
        $user->city        = "N/A";
        
        if ($tnlid = "tnl.admin") {
            $user->password    = md5("Tn!m00dl3");
        } else {
            $user->password    = md5("m00d1p@ss");
        }
        
        //This is a list of fields corresponding to what is inserted into the DB if we were adding a user from the Moodle 1.8.4 interface.
        $user->auth        = "manual";
        $user->confirmed   = 1;
        $user->policyagreed  = 1;
        $user->idnumber    = "";
        $user->emailstop   = 0;
        $user->icq         = "";
        $user->skype       = "";
        $user->yahoo       = "";
        $user->aim         = "";
        $user->msn         = "";
        $user->phone1      = "";
        $user->phone2      = "";
        $user->department  = "";
        $user->deleted     = "0";
        $user->address     = "";
        $user->country     = "US";
        $user->lang        = "en_us";
        $user->timezone    = "99";
        $user->url         = "";
        $user->description = "";
        $user->mailformat  = 1;
        $user->maildigest  = 0;
        $user->maildisplay = 2;
        $user->autosubscribe = 1;
        $user->trackforums = 0;
        $user->timemodified= time();
        $user->screenreader= 0;     
    
        if (!$user->id = $DB->insert_record('user', $user))
        {
            error_log($loggerName . 'Error creating user record!');
            error('Error creating user record!');
        }
            
        error_log($loggerName . 'User successfully created with id ' . $user->id);
        return $user;
    }
    
    //Attempts to enroll the user in the passed in course
    function tnlEnroll($tnlid, $userid, $courseid) {
        global $student_role, $admin_role, $loggerName;
        error_log($loggerName . 'Attempting to enroll tnl person_id ' . $tnlid . ' in course ID ' . $courseid); 
        enrol_try_internal_enrol($courseid, $userid, $student_role);               
        error_log($loggerName . 'Enrollment complete for user ID ' . $userid);
        echo $userid;
    }
    
    // Unenrols a user from a course
    function tnlUnenrol( $user_id, $course_id ) {
        global $DB, $student_role, $teacher_role, $admin_role, $loggerName;
        $role_id = $student_role;
        $enrolinstances = enrol_get_instances( $course_id, true );

        echo 'Got $enrolinstances: '; print_r( $enrolinstances ); echo ".\n";

        $enrols = enrol_get_plugins( true );

        echo 'Got $enrols: '; print_r( $enrols ); echo ".\n";

        $unenrolled = false;
        foreach ( $enrolinstances as $instance ) {
            if ( !$unenrolled and $enrols[ $instance->enrol ]->allow_unenrol( $instance ) ) {
                $unenrolinstance = $instance;
                $unenrolled = true;
            }
        }
  
        // This looks as though it selects only those instances that
        // can unenrol. By dumping the instances, I discovered that
        // they have an 'enrol' field which indicates the kind of
        // plugin. For manual enrolment, the field contains 'manual'.
        // And for the course I tried, $enrolinstances didn't contain
        // any others. I don't know whether that would always be so.

        echo 'Got $unenrolinstance: '; print_r( $unenrolinstance ); echo ".\n";

        // Should check that $unenrolinstance->enrol is 'manual'. If it
        // is, the statement below is probably safe.

        $enrols[ $unenrolinstance->enrol ]->unenrol_user( $unenrolinstance, $user_id, $role_id );
    }
    
?>
