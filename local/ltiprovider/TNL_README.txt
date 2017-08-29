This tool has been taken and modified for Truenorthlogic

The following changes have been made


1. -  $custom_user_institution has been added to set the institution from the incoming lti message.
      The default behavior is to pull this value from a default template, but this is the default
      site from the Empari instance, and is not the same for all users.  The value is passed in the
      custom parameters and is called custom_institution.

2. -  $custom_course_format has been added to set te format of new courses from the incoming lti message.
      The default behavior sets the course format to "Topics", but there is a configuration in Empari
      to allow the client to specify the value.

3. -  $custom_update_context has been added to decide if we should update the existing course.  Prior to this change
      if the course in empari had any changes, they were not persisted in moodle.

4. -  $custom_service has been added to decide if we are deleting the course or unenrolling a user.

5. -  custom_course_start_month, custom_course_start_day, and custom_course_start_year have been added to create the
      course start date

6. -  custom_course_format is now sent to allow the user to create a course based on topics, course, social, etc

7. -  custom_numsections is now sent to allow the user to specify the number of weeks or sections to add to a new course

8. -  custom_description is now sent

