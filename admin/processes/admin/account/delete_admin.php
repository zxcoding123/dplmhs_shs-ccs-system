<?php
include('../../server/conn.php');
session_start();

if (isset($_GET['id'])) {
    $adminId = $_GET['id'];

    try {
        $sql = "DELETE FROM admin WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $adminId, PDO::PARAM_INT);
        $stmt->execute();

        // Optionally, set a success session message
        $_SESSION['STATUS'] = "ADMIN_DELETED_SUCCESS";

        // Redirect back to the admin list page
        header("Location: ../../../admin_management.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "ADMIN_DELETED_FAILED";
        header("Location: ../../../admin_management.php");
        exit();
    }
}
?>
