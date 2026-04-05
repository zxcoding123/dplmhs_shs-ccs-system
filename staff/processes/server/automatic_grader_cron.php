<?php
                                // Assuming PDO connection is already established
                                $class_id = $_GET['class_id'] ?? null;

                                if (!$class_id) {
                                    die("Class ID is required");
                                }

                                function calculateGrade($percentage)
                                {

                                    if ($percentage >= 0.96) return "1.00"; // 96-100%
                                    if ($percentage >= 0.91) return "1.25"; // 91-95%
                                    if ($percentage >= 0.86) return "1.50"; // 86-90%
                                    if ($percentage >= 0.81) return "1.75"; // 81-85%
                                    if ($percentage >= 0.76) return "2.00"; // 76-80%
                                    if ($percentage >= 0.71) return "2.25"; // 71-75%
                                    if ($percentage >= 0.66) return "2.50"; // 66-70%
                                    if ($percentage >= 0.61) return "2.75"; // 61-65%
                                    if ($percentage >= 0.45) return "3.00"; // 50-60% (Passing)
                                    return "5.00"; // Below 50% (Failing)
                                }

                                function roundToNearestGrade($value, $grades)
                                {
                                    return array_reduce($grades, function ($closest, $grade) use ($value) {
                                        return abs($grade - $value) < abs($closest - $value) ? $grade : $closest;
                                    }, $grades[0]);
                                }

                                // Fetch class information for the current class
                                $classStmt = $pdo->prepare("SELECT subject, type FROM classes WHERE id = ?");
                                $classStmt->execute([$class_id]);
                                $classInfo = $classStmt->fetch(PDO::FETCH_ASSOC);

                                if (!$classInfo) {
                                    die("Invalid Class ID");
                                }

                                $classSubject = $classInfo['subject'] ?? '';
                                $classType = strtolower($classInfo['type'] ?? '');

                                // Fetch all classes with the same subject to check for related Lecture/Lab classes
                                $relatedClassesStmt = $pdo->prepare("SELECT id, type FROM classes WHERE subject = ?");
                                $relatedClassesStmt->execute([$classSubject]);
                                $relatedClasses = $relatedClassesStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Detect Lecture and Lab based on all related classes
                                $hasLab = false;
                                $hasLec = false;
                                $lectureClassId = null;
                                $labClassId = null;

                                foreach ($relatedClasses as $relatedClass) {
                                    $relatedType = strtolower($relatedClass['type'] ?? '');
                                    if (strpos($relatedType, 'laboratory') !== false || strpos($relatedType, 'lab') !== false) {
                                        $hasLab = true;
                                        $labClassId = $relatedClass['id'];
                                    }
                                    if (strpos($relatedType, 'lecture') !== false || strpos($relatedType, 'lec') !== false) {
                                        $hasLec = true;
                                        $lectureClassId = $relatedClass['id'];
                                    }
                                }

                                $hasBothLabAndLec = $hasLab && $hasLec;

                                // Fetch rubrics with percentiles
                                $rubricsStmt = $pdo->prepare("SELECT DISTINCT title, percentile FROM rubrics WHERE class_id = ?");
                                $rubricsStmt->execute([$class_id]);
                                $rubrics = $rubricsStmt->fetchAll(PDO::FETCH_ASSOC);
                                $rubricTypes = array_column($rubrics, 'title');
                                $percentiles = array_column($rubrics, 'percentile', 'title');

                                // Detect rubric types that include the word 'Attendance'
                                $attendanceRubrics = preg_grep('/\bAttendance\b/', $rubricTypes);

                                // Separate the rubric types into two categories: with Attendance and without Attendance
                                $activityTypes = array_diff($rubricTypes, $attendanceRubrics);  // Rubrics that don't include Attendance
                                $attendanceTypes = $attendanceRubrics;  // Rubrics that include Attendance

                                // Ensure that attendance types are included in the activity types if needed
                                $activityTypes = array_merge($activityTypes, $attendanceTypes);

                                // Flag to check if any Attendance rubric is present
                                $hasAttendance = !empty($attendanceTypes);



                                // Fetch activities
                                $activities = [];
                                if (!empty($activityTypes)) {
                                    $placeholders = implode(',', array_fill(0, count($activityTypes), '?'));
                                    $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ? AND type IN ($placeholders)");
                                    $activitiesStmt->execute(array_merge([$class_id], $activityTypes));
                                    $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                // Organize activities by type and term
                                $activitiesByType = [];
                                $activityIds = [];
                                foreach ($activities as $activity) {
                                    $activityIds[] = $activity['id'];
                                    if (!isset($activitiesByType[$activity['type']])) {
                                        $activitiesByType[$activity['type']] = [
                                            'midterm' => [],
                                            'final' => [],
                                            'max_points' => ['midterm' => 0, 'final' => 0]
                                        ];
                                    }
                                    $activitiesByType[$activity['type']][$activity['term']][] = $activity;
                                    $activitiesByType[$activity['type']]['max_points'][$activity['term']] += floatval($activity['max_points']);
                                }

                                // Fetch student submissions
                                $submissions = [];
                                if (!empty($activityIds)) {
                                    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
                                    $submissionsStmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN ($placeholders)");
                                    $submissionsStmt->execute($activityIds);
                                    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                $studentScores = [];
                                foreach ($submissions as $submission) {
                                    $studentScores[$submission['student_id']][$submission['activity_id']] = $submission['score'];
                                }

                                // Fetch students from enrollments
                                $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
                                $stmt->execute([$class_id]);
                                $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                $students = [];
                                if (!empty($studentIds)) {
                                    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
                                    $stmt = $pdo->prepare("SELECT student_id, fullName FROM students WHERE student_id IN ($placeholders) ORDER BY fullName");
                                    $stmt->execute($studentIds);
                                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                // Attendance handling
                                $totalMeetings = 0;
                                $attendanceDates = [];
                                $attendanceRecords = [];
                                if ($hasAttendance) {
                                    $stmt = $pdo->prepare("SELECT id, date FROM classes_meetings WHERE class_id = ? ORDER BY date ASC");
                                    $stmt->execute([$class_id]);
                                    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $totalMeetings = count($meetings);
                                    $attendanceDates = array_column($meetings, 'date', 'id');



                                    $attendanceStmt = $pdo->prepare("SELECT student_id, meeting_id, status FROM attendance WHERE class_id = ?");
                                    $attendanceStmt->execute([$class_id]);
                                    $attendanceRecordsRaw = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($attendanceRecordsRaw as $record) {
                                        $studentId = $record['student_id'];
                                        $meetingId = $record['meeting_id'];
                                        if (!isset($attendanceRecords[$studentId])) {
                                            $attendanceRecords[$studentId] = [];
                                        }
                                        $attendanceRecords[$studentId][$meetingId] = $record['status'];
                                    }
                                }

                                $totalPoints = [];
                                foreach ($activityTypes as $type) {
                                    $totalPoints[$type] = ['midterm' => 0, 'final' => 0]; // Always initialize both keys
                                    if (!isset($activitiesByType[$type])) {
                                        continue;
                                    }
                                    // Calculate midterm points
                                    if (!empty($activitiesByType[$type]['midterm'])) {
                                        foreach ($activitiesByType[$type]['midterm'] as $activity) {
                                            $totalPoints[$type]['midterm'] += floatval($activity['max_points']);
                                        }
                                    }
                                    // Calculate final points
                                    if (!empty($activitiesByType[$type]['final'])) {
                                        foreach ($activitiesByType[$type]['final'] as $activity) {
                                            $totalPoints[$type]['final'] += floatval($activity['max_points']);
                                        }
                                    }
                                }

                                // Define required activities and final exams
                                $requiredActivityIds = array_column($activities, 'id');
                                $finalExamActivityIds = array_column(array_filter($activities, function ($activity) {
                                    return stripos($activity['type'], 'exam') !== false && $activity['term'] === 'final';
                                }), 'id');

                                $studentGrades = [];
                                foreach ($students as $student) {
                                    $studentId = $student['student_id'];
                                    $grades = ['midterm' => 0, 'final' => 0];
                                
                                    // Check if student has existing grades in the database
                                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                    $checkStmt->execute([':class_id' => $class_id, ':student_id' => $studentId]);
                                    $exists = $checkStmt->fetchColumn() > 0;
                                
                                    if ($exists) {
                                        // Fetch existing grades and status
                                        $selectStmt = $pdo->prepare("SELECT midterm_grade, final_grade, overall_grade, status FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                        $selectStmt->execute([':class_id' => $class_id, ':student_id' => $studentId]);
                                        $existingRecord = $selectStmt->fetch(PDO::FETCH_ASSOC);
                                
                                        if ($existingRecord) {
                                            $existingMidtermGrade = $existingRecord['midterm_grade'];
                                            $existingFinalGrade = $existingRecord['final_grade'];
                                            $existingStatus = $existingRecord['status'];
                                
                                            // Handle special cases like AW, UW, and INC grades
                                            if ($existingMidtermGrade === 'AW' || $existingFinalGrade === 'AW') {
                                                $midtermGrade = $finalGrade = $overallGrade = 'AW';
                                                $status = $existingStatus;
                                            } elseif ($existingMidtermGrade === 'UW' || $existingFinalGrade === 'UW') {
                                                $midtermGrade = $finalGrade = $overallGrade = 'UW';
                                                $status = $existingStatus;
                                            } else {
                                                // Check if grade needs to be calculated or is already available
                                                if (in_array($existingStatus, ['for_approval', 'pending', 'final', 'accepted', 'saved'])) {
                                                    $midtermGrade = $existingMidtermGrade;
                                                    $finalGrade = $existingFinalGrade;
                                                    $overallGrade = $existingRecord['overall_grade'];
                                                    $status = $existingStatus;
                                                } else {
                                                    // Default grade if no activity is available
                                                    if (empty($activityTypes) || empty($activities)) {
                                                        $midtermGrade = $finalGrade = $overallGrade = "N/A";
                                                        $status = "N/A";
                                                    } else {
                                                        // Calculate grades based on activities only if they exist
                                                        foreach ($activityTypes as $type) {
                                                            // Check if activities exist for this type
                                                            if (!empty($activitiesByType[$type]['midterm']) || !empty($activitiesByType[$type]['final'])) {
                                                                $grades['midterm'] += !empty($activitiesByType[$type]['midterm']) ? 
                                                                    calculateActivityScore($studentId, $activitiesByType[$type]['midterm'], $studentScores, $totalPoints, $percentiles[$type], 'midterm') : 0;
                                                                $grades['final'] += !empty($activitiesByType[$type]['final']) ? 
                                                                    calculateActivityScore($studentId, $activitiesByType[$type]['final'], $studentScores, $totalPoints, $percentiles[$type], 'final') : 0;
                                                            }
                                                        }
                                
                                                        // Handle attendance
                                                        if ($hasAttendance && $totalMeetings > 0) {
                                                            // Calculate the attendance percentage
                                                            $attendancePercentage = calculateAttendance($studentId, $attendanceDates, $attendanceRecords, $totalMeetings);
                                
                                                            foreach ($attendanceTypes as $attendanceType) {
                                                                if (isset($percentiles[$attendanceType])) {
                                                                    $grades['midterm'] += $attendancePercentage * ($percentiles[$attendanceType] / 100) / 2;
                                                                    $grades['final'] += $attendancePercentage * ($percentiles[$attendanceType] / 100) / 2;
                                                                }
                                                            }
                                                        }
                                
                                                        // Check for missing requirements or final exam
                                                        $missingRequirements = checkMissingRequirements($studentId, $requiredActivityIds, $studentScores);
                                                        $missedFinalExam = checkMissingRequirements($studentId, $finalExamActivityIds, $studentScores);
                                
                                                        // Calculate grades only if there are activities
                                                        if ($grades['midterm'] > 0 || $grades['final'] > 0) {
                                                            $midtermGrade = calculateGrade($grades['midterm']);
                                                            $finalGrade = calculateGrade($grades['final']);
                                
                                                            $midtermNumeric = floatval($midtermGrade);
                                                            $finalNumeric = floatval($finalGrade);
                                                            
                                                            if ($missingRequirements || $missedFinalExam) {
                                                                $midtermGrade = $missingRequirements ? checkUngradedStatus($studentId, $requiredActivityIds) : $midtermGrade;
                                                                $finalGrade = $missingRequirements ? checkUngradedStatus($studentId, $requiredActivityIds) : $finalGrade;
                                                                $overallGrade = $missingRequirements ? checkUngradedStatus($studentId, $requiredActivityIds) : "INC";
                                                                $status = $missingRequirements ? checkUngradedStatus($studentId, $requiredActivityIds) : "INC";
                                                            } else {
                                                                $rawAverage = ($midtermNumeric + $finalNumeric) / 2;
                                                                $validGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 5.00];
                                                                $overallGrade = number_format(roundToNearestGrade($rawAverage, $validGrades), 2);
                                                            }
                                                        } else {
                                                            $midtermGrade = $finalGrade = $overallGrade = "N/A";
                                                            $status = "N/A";
                                                        }
                                                    }
                                                }
                                
                                                // Check if there's a need to update the grade
                                                if ($existingMidtermGrade !== $midtermGrade || $existingFinalGrade !== $finalGrade || $existingRecord['overall_grade'] !== $overallGrade) {
                                                    $updateStmt = $pdo->prepare("UPDATE student_grades SET midterm_grade = :midterm_grade, final_grade = :final_grade, overall_grade = :overall_grade, updated_at = NOW() WHERE class_id = :class_id AND student_id = :student_id");
                                                    $updateStmt->execute([':class_id' => $class_id, ':student_id' => $studentId, ':midterm_grade' => $midtermGrade, ':final_grade' => $finalGrade, ':overall_grade' => $overallGrade]);
                                                }
                                            }
                                
                                            // Calculate combined Lecture and Lab grade if applicable
                                            $combinedOverallGrade = $overallGrade;
                                            if ($hasBothLabAndLec) {
                                                $lecGrade = fetchGrade($lectureClassId, $studentId, $pdo);
                                                $labGrade = fetchGrade($labClassId, $studentId, $pdo);
                                                $combinedOverallGrade = calculateCombinedGrade($lecGrade, $labGrade);
                                            }
                                
                                            $studentGrades[$studentId] = [
                                                'fullName' => $student['fullName'],
                                                'midterm' => $midtermGrade,
                                                'final' => $finalGrade,
                                                'lecGrade' => $lecGrade ?? 'INC',
                                                'labGrade' => $labGrade ?? 'INC',
                                                'gpa' => $overallGrade,
                                                'overallGrade' => $combinedOverallGrade
                                            ];
                                        }
                                    }
                                }

                                // In the calculateActivityScore function
                                function calculateActivityScore($studentId, $activities, $studentScores, $totalPoints, $percentile, $term)
                                {  // Changed $type to $term
                                    $score = 0;
                                    foreach ($activities as $activity) {
                                        $score += ($studentScores[$studentId][$activity['id']] ?? 0);
                                    }
                                    // Ensure the type is derived from activities and term exists
                                    $type = !empty($activities) ? $activities[0]['type'] : '';
                                    $totalPointsForType = isset($totalPoints[$type][$term]) && $totalPoints[$type][$term] > 0 ? $totalPoints[$type][$term] : 1;

                                    return ($score / $totalPointsForType) * ($percentile / 100);
                                }

                                function calculateAttendance($studentId, $attendanceDates, $attendanceRecords, $totalMeetings)
                                {
                                    $presentCount = 0;
                                    foreach ($attendanceDates as $meetingId => $date) {
                                        if (($attendanceRecords[$studentId][$meetingId] ?? 'absent') === 'present') {
                                            $presentCount++;
                                        }
                                    }
                                    return $presentCount / $totalMeetings;
                                }

                                // Modified function to check missing requirements
                                function checkMissingRequirements($studentId, $requiredActivityIds, $studentScores)
                                {
                                    foreach ($requiredActivityIds as $activityId) {
                                        if (!isset($studentScores[$studentId][$activityId]) || $studentScores[$studentId][$activityId] == 0) {
                                            return true;
                                        }
                                    }
                                    return false;
                                }

                                // New function to check if submission exists but isn't graded
                                function checkUngradedStatus($studentId, $requiredActivityIds)
                                {
                                    global $pdo; // Assuming you're using PDO for database connection

                                    foreach ($requiredActivityIds as $activityId) {
                                        $stmt = $pdo->prepare("
            SELECT score, status 
            FROM activity_submissions 
            WHERE student_id = :student_id 
            AND activity_id = :activity_id
        ");
                                        $stmt->execute([
                                            ':student_id' => $studentId,
                                            ':activity_id' => $activityId
                                        ]);

                                        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

                                        // If submission exists but score is null or status indicates not graded
                                        if (
                                            $submission &&
                                            (is_null($submission['score']) ||
                                                $submission['status'] === 'submitted' ||
                                                $submission['status'] === 'pending')
                                        ) {
                                            return 'N/A';
                                        }
                                    }
                                    return 'INC'; // Default to INC if not ungraded
                                }

                                function fetchGrade($classId, $studentId, $pdo)
                                {
                                    $stmt = $pdo->prepare("SELECT overall_grade FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                    $stmt->execute([':class_id' => $classId, ':student_id' => $studentId]);
                                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                                    return $record ? $record['overall_grade'] : 'N/A';
                                }

                                function calculateCombinedGrade($lecGrade, $labGrade)
                                {
                                    // Check for non-numeric statuses and return them directly
                                    if ($lecGrade === 'INC' || $labGrade === 'INC') {
                                        return 'INC';
                                    }
                                    if ($lecGrade === 'AW' || $labGrade === 'AW') {
                                        return 'AW';
                                    }
                                    if ($lecGrade === 'UW' || $labGrade === 'UW') {
                                        return 'UW';
                                    }
                                    if ($lecGrade === 'N/A' || $labGrade === 'N/A') {
                                        return 'N/A';
                                    }

                                    if (is_numeric($lecGrade) || is_numeric($labGrade)) {
                                        // Convert grades to numeric values and calculate weighted average
                                        $lecNumeric = floatval($lecGrade);
                                        $labNumeric = floatval($labGrade);
                                        $weightedAverage = ($lecNumeric * 0.6) + ($labNumeric * 0.4); // 60% Lecture, 40% Lab

                                        // Define valid grades and find the nearest one
                                        $validGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 5.00, 25];
                                        return findNearestGrade($weightedAverage, $validGrades);
                                    }
                                }

                                function findNearestGrade($weightedAverage, $validGrades)
                                {
                                    $nearestGrade = $validGrades[0]; // Default to the first valid grade
                                    $minDifference = abs($weightedAverage - $validGrades[0]);

                                    foreach ($validGrades as $grade) {
                                        $difference = abs($weightedAverage - $grade);
                                        if ($difference < $minDifference) {
                                            $minDifference = $difference;
                                            $nearestGrade = $grade;
                                        }
                                    }
                                    return $nearestGrade;
                                }

?>