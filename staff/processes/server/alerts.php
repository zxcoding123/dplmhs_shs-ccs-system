<?php
if (isset($_SESSION['STATUS'])) {
    switch ($_SESSION['STATUS']) {
        case 'TEACHER_LOGIN_SUCCESSFUL':
            echo "
            <script>
                Swal.fire({
                    title: 'Login successful!',
                    text: 'You have successfully logged into your account as a teacher!',
                    icon: 'success'
                });
            </script>
            ";
            break;
            case 'NO_STUDENTS_ENROLLED':
                echo "
                <script>
                    Swal.fire({
                        title: 'No Students Enrolled',
                        text: 'Currently, there are no students enrolled in this class. Please check back later or notify the administrator if this is an issue.',
                        icon: 'warning'
                    });
                </script>
                ";
                break;
            
        case 'READ_ALL_NOTIFICATIONS':
            echo "
                <script>
                    Swal.fire({
                        title: 'All notifications read successful!',
                        text: 'All of your notifications have been read succesfully!',
                        icon: 'success'
                    });
                </script>
                ";
            break;

        case 'DELETE_ALL_NOTIFICATIONS':
            echo "
                    <script>
                        Swal.fire({
                            title: 'All notifications delete successful!',
                            text: 'All of your notifications have been deleted succesfully!',
                            icon: 'success'
                        });
                    </script>
                    ";
            break;

    case 'GRADING_SUCCESS':
        echo "
        <script>
            Swal.fire({
                title: 'You have graded succesfully!',
                text: 'You have graded a particular activity succesfully!',
                icon: 'success'
            });
        </script>
        ";
break;


        case 'DELETE_NOTIFICATIONS':
            echo "
                        <script>
                                 Swal.fire({
                                    title: 'Notification deletion succesful!',
                                    text: 'A selected notification has been deleted succesfully!',
                                    icon: 'success'
                                });
                        
                        </script>
                        ";
            break;

        case 'READ_NOTIFICATIONS':
            echo "
                            <script>
                                Swal.fire({
                                    title: 'Notification read succesful!',
                                    text: 'A selected notification has been read succesfully!',
                                    icon: 'success'
                                });
                            </script>
                            ";
            break;




        case 'ADD_NOTES_SUCCESS':
            echo "
            <script>
                Swal.fire({
                    title: 'Addition of note successful!',
                    text: 'You have successfully added a note!',
                    icon: 'success'
                });
            </script>
            ";
            break;

        case 'ADD_NOTES_FAIL':
            echo "
            <script>
                Swal.fire({
                    title: 'Addition of note failed!',
                    text: 'There was an error in adding the note, please try again!',
                    icon: 'error'
                });
            </script>
            ";
            break;

        case 'NOTES_DELETED_SUCCESSFULLY':
            echo "
            <script>
                Swal.fire({
                    title: 'Deletion of note successful!',
                    text: 'You have successfully deleted a note!',
                    icon: 'success'
                });
            </script>
            ";
            break;

        case 'CLASS_ADDED_SUCCESFUL':
            echo "
                <script>
                    Swal.fire({
                        title: 'Addition of class succesful!',
                        text: 'You have successfully added a new class to teach! Please await the permission from the admin!',
                        icon: 'success'
                    });
                </script>
                ";
            break;


        case 'NEW_LEARNING_MATERIAL_ADDED':
            echo "
                <script>
                    Swal.fire({
                        title: 'Addition of new learning material succesful!',
                        text: 'You have successfully added a new learning material for the class!',
                        icon: 'success'
                    });
                </script>
                ";
            break;





        case 'NOTES_EDITION_SUCCESSFUL':
            echo "
            <script>
                Swal.fire({
                    title: 'Edition of note successful!',
                    text: 'You have successfully edited your note!',
                    icon: 'success'
                });
            </script>
            ";
            break;

        case 'ADD_REMINDER_SUCCESS':
            echo "
            <script>
                Swal.fire({
                    title: 'Reminder added successfully!',
                    text: 'You have successfully added a reminder!',
                    icon: 'success'
                });
            </script>
            ";
            break;

        case 'ADD_REMINDER_FAILURE':
            echo "
            <script>
                Swal.fire({
                    title: 'Failed to add reminder',
                    text: 'There was an issue adding the reminder. Please try again!',
                    icon: 'error'
                });
            </script>
            ";
            break;

        case 'ADD_REMINDER_VALIDATION_FAILURE':
            echo "
            <script>
                Swal.fire({
                    title: 'Validation error!',
                    text: 'Reminder content and date cannot be empty!',
                    icon: 'warning'
                });
            </script>
            ";
            break;

        case 'ADD_REMINDER_INVALID_REQUEST':
            echo "
            <script>
                Swal.fire({
                    title: 'Invalid request!',
                    text: 'Please fill in all required fields to proceed!',
                    icon: 'error'
                });
            </script>
            ";
            break;

        case 'ADD_REMINDER_UNAUTHORIZED':
            echo "
            <script>
                Swal.fire({
                    title: 'Unauthorized access!',
                    text: 'You tried to access a restricted area!',
                    icon: 'error'
                });
            </script>
            ";
            break;

        case 'REMINDERS_DELETED_SUCCESSFULLY':
            echo "
            <script>
                Swal.fire({
                    title: 'Deletion of reminder successful!',
                    text: 'You have successfully deleted a reminder!',
                    icon: 'success'
                });
            </script>
            ";
            break;



        case 'NEW_INFO_SUCCESFUL':
            echo "
                    <script>
                        Swal.fire({
                            title: 'Staff Information Updated Successfully!',
                            text: 'The staff information has been successfully updated.',
                            icon: 'success'
                        });
                    </script>
                ";
            break;

        case 'NEW_INFO_ERROR':
            echo "
                    <script>
                        Swal.fire({
                            title: 'Error Updating Staff Information',
                            text: 'There was an error while updating the staff information. Please try again.',
                            icon: 'error'
                        });
                    </script>
                ";
            break;


        case 'STUDENT_ENROLL_SUCCESSFUL':
            echo "
                <script>
                    Swal.fire({
                        title: 'Student ernollment successful!',
                        text: 'You have successfully enrolled a student!',
                        icon: 'success'
                    });
                </script>
                ";
            break;

        case 'STUDENT_UNENROLL_SUCCESSFUL':
            echo "
                <script>
                    Swal.fire({
                        title: 'Student unenrollment successful!',
                        text: 'You have successfully unenrolled a student!',
                        icon: 'success'
                    });
                </script>
                    ";
            break;



        case 'ACT_ADDED_SUCCESS':
            echo "
                <script>
                    Swal.fire({
                        title: 'Activity added successfully!',
                        text: 'You have successfully added a new activity!',
                        icon: 'success'
                    });
                </script>
                    ";
            break;

        case 'ACT_DELETED_SUCCESS':
            echo "
                    <script>
                        Swal.fire({
                            title: 'Activity deleted successfully!',
                            text: 'You have successfully deleted an activity and its corresponding attachments!',
                            icon: 'success'
                        });
                    </script>
                        ";
            break;



        case 'STAFF_CREATE_ACC_SUCCESFUL':
            echo "
                    <script>
                        Swal.fire({
                            title: 'Staff account created successfully!',
                            text: 'You have successfully created a staff account!',
                            icon: 'success'
                        });
                    </script>
                        ";
            break;

        case 'SUCCESSFUL_LOG_OUT':
            echo "
                        <script>
                            Swal.fire({
                                title: 'Staff account log out succesful!',
                                text: 'You have successfully logged out!',
                                icon: 'success'
                            });
                        </script>
                            ";
            break;


        case 'TEACHER_LOGIN_SUCCESFUL':
            echo "
                        <script>
                            Swal.fire({
                                title: 'Staff account log in succesful!',
                                text: 'You have successfully logged in!',
                                icon: 'success'
                            });
                        </script>
                            ";
            break;

        case 'NEW_INFO_SUCCESSFUL':
            echo "
                            <script>
                                Swal.fire({
                                    title: 'Staff account update succesful!',
                                    text: 'You have successfully updated your account details!',
                                    icon: 'success'
                                });
                            </script>
                                ";
            break;



        case 'STAFF_ACCOUNT_EXISTS':
            echo "
                        <script>
                            Swal.fire({
                                title: 'Staff account credentials already exist!',
                                text: 'This email is already existing! Please login with your credentials, if you are the owner.',
                                icon: 'error'
                            });
                        </script>
                            ";
            break;


        case 'REVERTED_GRADES':
            echo "
                           <script>
                        Swal.fire({
                            title: 'Grades Reverted!',
                            text: 'All grades have been reverted back for modification!',
                            icon: 'success'
                        });
                    </script>
                                ";
            break;

            case 'SAVED_GRADES':
                echo "
                               <script>
                            Swal.fire({
                                title: 'Grades Saved!',
                                text: 'All grades have been succesfully saved!',
                                icon: 'success'
                            });
                        </script>
                                    ";
                break;

        case 'SUBMISSION_FOR_APPROVAL':
            echo "
                        <script>
                            Swal.fire({
                                title: 'Grades Submitted for Approval!',
                                text: 'The grades are now pending approval. Once approved, they will be finalized.',
                                icon: 'success'
                            });
                        </script>
                    ";
            break;







        case 'ACT_ATTACHMENT_ERROR':
            echo "
                    <script>
                        Swal.fire({
                            title: 'Activity addition of attachment error!',
                            text: 'There has been an error in adding an attachment, please try again!',
                            icon: 'error'
                        });
                    </script>
                        ";
            break;

        case 'ACT_ERROR_SAME':
            echo "
                        <script>
                            Swal.fire({
                                title: 'Activity already exists!',
                                text: 'The name of the activity already exists! Please perform a new one.',
                                icon: 'error'
                            });
                        </script>
                            ";
            break;

        case 'TEACHER_NOT_LOGGED_IN':
            echo "
                            <script>
                                Swal.fire({
                                    title: 'Teacher not logged in!',
                                    text: 'Please login your credentials as a teacher to access the functions!',
                                    icon: 'error'
                                });
                            </script>
                                ";
            break;

        case 'ALL_PRESENT':
            echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'All students have been set to Present.',
                        icon: 'success',
                        confirmButtonText: 'Okay'
                    });
                </script>";
            break;

        case 'ALL_ABSENT':
            echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'All students have been set to Absent.',
                        icon: 'success',
                        confirmButtonText: 'Okay'
                    });
                </script>";
            break;

            case 'CHANGE_RADIUS':
                echo "
                    <script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'You have succesfully edited the attendance option in terms of scanning radius!',
                            icon: 'success',
                            confirmButtonText: 'Okay'
                        });
                    </script>";
                break;





        default:
            echo "
            <script>
                Swal.fire({
                    title: 'Unknown status!',
                    text: 'An unexpected status was encountered. Please contact support if needed.',
                    icon: 'info'
                });
            </script>
            ";
            break;
    }

    // Unset status after displaying the alert
    unset($_SESSION['STATUS']);
}
?>