<?php

function getStudentScope(mysqli $conn, int $studentUserId, string $quizAlias = 'q', string $evalAlias = 'e'): array
{
    $studentUserId = intval($studentUserId);

    $student_level_row = $conn->query("SELECT level_id FROM students WHERE user_id = {$studentUserId} LIMIT 1");
    $student_level_id = ($student_level_row && $student_level_row->num_rows > 0)
        ? intval($student_level_row->fetch_assoc()['level_id'])
        : 0;

    $faculty_ids = array();
    if ($student_level_id > 0) {
        $faculty_qry = $conn->query("SELECT DISTINCT f.user_id FROM faculty f INNER JOIN faculty_levels fl ON fl.faculty_id = f.id WHERE fl.level_id = {$student_level_id}");
        if ($faculty_qry && $faculty_qry->num_rows > 0) {
            while ($row = $faculty_qry->fetch_assoc()) {
                $faculty_ids[] = intval($row['user_id']);
            }
        }
    }

    $quiz_visibility_condition = "{$quizAlias}.id IN (SELECT quiz_id FROM quiz_student_list WHERE user_id = {$studentUserId})";
    $eval_visibility_condition = "";

    if (!empty($faculty_ids)) {
        $faculty_list = implode(',', $faculty_ids);
        $quiz_visibility_condition .= " OR {$quizAlias}.user_id IN ({$faculty_list})";
        $eval_visibility_condition = "{$evalAlias}.created_by IN ({$faculty_list})";
    } else {
        $eval_visibility_condition = "1=0";
    }

    return array(
        'student_level_id' => $student_level_id,
        'faculty_ids' => $faculty_ids,
        'quiz_visibility_condition' => $quiz_visibility_condition,
        'eval_visibility_condition' => $eval_visibility_condition
    );
}
