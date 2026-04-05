<?php


if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STUDENT_LOGIN_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'You have succesfully logged in!',
            text: 'You have succesfully logged in as a student. Study well!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'DELETE_NOTIFICATION_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'You have succesfully deleted a notification!',
            text: 'The selected notification has been removed from your dashboard.',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'DELETE_ALL_NOTIFICATIONS') {
    echo "
    <script>
        Swal.fire({
            title: 'You have succesfully deleted all notifications!',
            text: 'All notifications have been removed from your dashboard.',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'READ_NOTIFICATION_SUCCESSFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'You have succesfully read a notification!',
            text: 'The selected notification has been read from your dashboard.',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}




elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'READ_ALL_NOTIFICATIONS') {
    echo "
    <script>
        Swal.fire({
            title: 'You have succesfully read all notifications!',
            text: 'All notifications have been read from your dashboard.',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'FILE_SUBMISSION_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Activity submission successful!',
            text: 'You have succesfully submitted an activity! Please await for grading!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'FILE_SUBMISSION_RESET_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Activity submission reset succesful!',
            text: 'You have succesfully reseted an activity! Please await for grading!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}


elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_PROF_PIC') {
    echo "
    <script>
        Swal.fire({
            title: 'Profile picture upload successful!',
            text: 'You have succesfully uploaded a new profile picture!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}


elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_PROF_PIC_FAIL') {
    echo "
    <script>
        Swal.fire({
            title: 'Profile picture update error!',
            text: 'Please try again in updating your profile picture!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}


elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_PROF_PIC_FAIL') {
    echo "
    <script>
        Swal.fire({
            title: 'Profile picture update error!',
            text: 'Please try again in updating your profile picture!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_PASS_OK') {
    echo "
    <script>
        Swal.fire({
            title: 'Password updated successful!',
            text: 'You have succesfully updated your account password!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'PROF_UPD_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Profile updated successful!',
            text: 'You have succesfully updated your account details!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_PASS_OK') {
    echo "
    <script>
        Swal.fire({
            title: 'Password updated successful!',
            text: 'You have succesfully updated your account password!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'PROF_UPD_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Proffile updated error!',
            text: 'There has been an error in updating your account details, please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SUCCESS_CLASS_JOIN') {
    echo "
    <script>
        Swal.fire({
            title: 'Class join success!',
            text: 'You have succesfully joined a class!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SUCCESS_CLASS_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Profile updated error!',
            text: 'There has been an error in joining the class, please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'CLASS_ALREADY_JOINED') {
    echo "
    <script>
        Swal.fire({
            title: 'Class join error!',
            text: 'You are already part of this class! Please check your code properly!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

?>
