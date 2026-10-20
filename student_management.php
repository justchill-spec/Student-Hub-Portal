<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
auth_require_role('admin');

function studentManagementEscape(string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function studentManagementPostString(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? trim($value) : null;
}

$connection = null;
$databaseError = null;
$formError = null;
$formValues = [];
$editingStudent = null;
$students = [];
$departments = [];
$courses = [];
$flash = null;

try {
    $connection = auth_database();
} catch (mysqli_sql_exception $exception) {
    error_log('StudentHub student-management connection failed: ' . $exception->getMessage());
    $databaseError = 'Student records are unavailable right now. Check the database connection and try again.';
}

if (isset($_SESSION['student_management_flash']) && is_array($_SESSION['student_management_flash'])) {
    $flash = $_SESSION['student_management_flash'];
    unset($_SESSION['student_management_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $_SESSION['student_management_flash'] = [
            'type' => 'error',
            'message' => 'Your session token is invalid or expired. Please try the action again.',
        ];
        header('Location: student_management.php', true, 303);
        exit;
    }

    $action = studentManagementPostString('action');
    $formValues = [
        'student_id' => studentManagementPostString('student_id') ?? '',
        'student_code' => strtoupper(studentManagementPostString('student_code') ?? ''),
        'full_name' => studentManagementPostString('full_name') ?? '',
        'department_id' => studentManagementPostString('department_id') ?? '',
        'semester' => studentManagementPostString('semester') ?? '',
        'course' => studentManagementPostString('course') ?? '',
        'email' => strtolower(studentManagementPostString('email') ?? ''),
        'phone' => studentManagementPostString('phone') ?? '',
        'cgpa' => studentManagementPostString('cgpa') ?? '',
        'status' => studentManagementPostString('status') ?? 'Active',
    ];

    if ($connection === null) {
        $formError = $databaseError ?? 'The database connection is unavailable.';
    } elseif ($action === 'delete') {
        $studentId = filter_var($formValues['student_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($studentId === false) {
            $formError = 'Select a valid student record to delete.';
        } else {
            try {
                $statement = $connection->prepare('DELETE FROM students WHERE student_id = ?');
                $statement->bind_param('i', $studentId);
                $statement->execute();
                $deleted = $statement->affected_rows;
                $statement->close();
                $_SESSION['student_management_flash'] = [
                    'type' => $deleted === 1 ? 'success' : 'error',
                    'message' => $deleted === 1
                        ? 'Student record deleted successfully.'
                        : 'No student record was found to delete.',
                ];
                header('Location: student_management.php', true, 303);
                exit;
            } catch (mysqli_sql_exception $exception) {
                error_log('StudentHub student deletion failed: ' . $exception->getMessage());
                $formError = (int) $exception->getCode() === 1451
                    ? 'This student has related registrations and cannot be deleted.'
                    : 'The student record could not be deleted. Please try again.';
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        $studentId = filter_var($formValues['student_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $departmentId = filter_var($formValues['department_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $semester = filter_var($formValues['semester'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $cgpa = filter_var($formValues['cgpa'], FILTER_VALIDATE_FLOAT);

        if (!preg_match('/^[A-Z0-9_-]{3,20}$/', $formValues['student_code'])) {
            $formError = 'Student ID must be 3-20 letters, numbers, hyphens, or underscores.';
        } elseif (!preg_match("/^[\\p{L}\\s.'-]{2,120}$/u", $formValues['full_name'])) {
            $formError = 'Enter a valid full name between 2 and 120 characters.';
        } elseif (strlen($formValues['email']) > 254 || filter_var($formValues['email'], FILTER_VALIDATE_EMAIL) === false) {
            $formError = 'Enter a valid email address of 254 characters or fewer.';
        } elseif (!preg_match('/^[0-9+(). -]{7,20}$/', $formValues['phone'])) {
            $formError = 'Enter a valid phone number between 7 and 20 characters.';
        } elseif ($formValues['course'] === '' || strlen($formValues['course']) > 50) {
            $formError = 'Course is required and must be 50 characters or fewer.';
        } elseif ($departmentId === false || $semester === false) {
            $formError = 'Select a valid department and semester (1-12).';
        } elseif ($cgpa === false || !is_finite((float) $cgpa) || $cgpa < 0 || $cgpa > 10) {
            $formError = 'CGPA must be a number between 0 and 10.';
        } elseif (!in_array($formValues['status'], ['Active', 'Inactive'], true)) {
            $formError = 'Select a valid student status.';
        } elseif ($action === 'update' && $studentId === false) {
            $formError = 'Select a valid student record to update.';
        } else {
            try {
                $departmentCheck = $connection->prepare('SELECT department_id FROM departments WHERE department_id = ?');
                $departmentCheck->bind_param('i', $departmentId);
                $departmentCheck->execute();
                $departmentExists = $departmentCheck->get_result()->num_rows === 1;
                $departmentCheck->close();

                if (!$departmentExists) {
                    $formError = 'Select an available department.';
                } elseif ($action === 'create') {
                    $statement = $connection->prepare(
                        'INSERT INTO students
                            (student_code, full_name, department_id, semester, course, email, phone, cgpa, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $statement->bind_param(
                        'ssiisssds',
                        $formValues['student_code'],
                        $formValues['full_name'],
                        $departmentId,
                        $semester,
                        $formValues['course'],
                        $formValues['email'],
                        $formValues['phone'],
                        $cgpa,
                        $formValues['status']
                    );
                    $statement->execute();
                    $statement->close();
                    $_SESSION['student_management_flash'] = [
                        'type' => 'success',
                        'message' => 'Student record added successfully.',
                    ];
                    header('Location: student_management.php', true, 303);
                    exit;
                } else {
                    $statement = $connection->prepare(
                        'UPDATE students
                         SET student_code = ?, full_name = ?, department_id = ?, semester = ?, course = ?,
                             email = ?, phone = ?, cgpa = ?, status = ?
                         WHERE student_id = ?'
                    );
                    $statement->bind_param(
                        'ssiisssdsi',
                        $formValues['student_code'],
                        $formValues['full_name'],
                        $departmentId,
                        $semester,
                        $formValues['course'],
                        $formValues['email'],
                        $formValues['phone'],
                        $cgpa,
                        $formValues['status'],
                        $studentId
                    );
                    $statement->execute();
                    $updated = $statement->affected_rows;
                    $statement->close();
                    $_SESSION['student_management_flash'] = [
                        'type' => 'success',
                        'message' => $updated > 0
                            ? 'Student record updated successfully.'
                            : 'No student record was found, or the submitted details were unchanged.',
                    ];
                    header('Location: student_management.php', true, 303);
                    exit;
                }
            } catch (mysqli_sql_exception $exception) {
                error_log('StudentHub student save failed: ' . $exception->getMessage());
                $formError = (int) $exception->getCode() === 1062
                    ? 'That student ID or email is already assigned to another record.'
                    : 'The student record could not be saved. Please check the database and try again.';
            }
        }
    } else {
        $formError = 'Choose a valid student-management action.';
    }
}

$query = $_GET['q'] ?? '';
$query = is_string($query) ? trim($query) : '';
$selectedCourse = $_GET['course'] ?? '';
$selectedCourse = is_string($selectedCourse) ? trim($selectedCourse) : '';
$editId = $_GET['edit'] ?? '';

if ($connection !== null) {
    try {
        $departmentResult = $connection->query('SELECT department_id, department_name FROM departments ORDER BY department_name');
        $departments = $departmentResult->fetch_all(MYSQLI_ASSOC);
        $courseResult = $connection->query('SELECT DISTINCT course FROM students ORDER BY course');
        $courses = $courseResult->fetch_all(MYSQLI_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editId !== '') {
            $validatedEditId = is_string($editId)
                ? filter_var($editId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;
            if ($validatedEditId === false) {
                $formError = 'The selected student record is invalid.';
            } else {
                $editStatement = $connection->prepare('SELECT * FROM students WHERE student_id = ?');
                $editStatement->bind_param('i', $validatedEditId);
                $editStatement->execute();
                $editingStudent = $editStatement->get_result()->fetch_assoc() ?: null;
                $editStatement->close();
                if ($editingStudent === null) {
                    $formError = 'The student record to edit was not found.';
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formValues !== []) {
            $editingStudent = $formValues;
        }

        $searchPattern = '%' . $query . '%';
        $listStatement = $connection->prepare(
            'SELECT s.student_id, s.student_code, s.full_name, s.department_id, d.department_name,
                    s.semester, s.course, s.email, s.phone, s.cgpa, s.status
             FROM students AS s
             INNER JOIN departments AS d ON d.department_id = s.department_id
             WHERE (? = \'\' OR s.full_name LIKE ? OR s.email LIKE ? OR s.course LIKE ?)
               AND (? = \'\' OR s.course = ?)
             ORDER BY s.full_name'
        );
        $listStatement->bind_param(
            'ssssss',
            $query,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $selectedCourse,
            $selectedCourse
        );
        $listStatement->execute();
        $students = $listStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $listStatement->close();
    } catch (mysqli_sql_exception $exception) {
        error_log('StudentHub student-management query failed: ' . $exception->getMessage());
        $databaseError = 'Student records could not be loaded. Check that the Practical 11 database setup has been applied.';
    }
}

$csrfToken = auth_csrf_token();
$formValues = $formValues !== [] ? $formValues : ($editingStudent ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Hub Portal</title>
    <link rel="stylesheet" href="css/student-management.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="admin_dashboard.php">Student Hub Portal</a>
        <nav aria-label="Admin navigation">
            <a href="admin_dashboard.php">Admin dashboard</a>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= studentManagementEscape($csrfToken) ?>">
                <button class="link-button" type="submit">Log out</button>
            </form>
        </nav>
    </header>

    <main class="page">
        <section class="page-heading">
            <p class="eyebrow">Administrator tools</p>
            <h1>Student management</h1>
            <p>Add, update, search, filter, and remove academic student records.</p>
        </section>

        <?php if ($flash !== null): ?>
            <p class="notice <?= studentManagementEscape($flash['type'] ?? 'error') ?>" role="<?= ($flash['type'] ?? '') === 'success' ? 'status' : 'alert' ?>">
                <?= studentManagementEscape($flash['message'] ?? 'The action could not be completed.') ?>
            </p>
        <?php endif; ?>
        <?php if ($databaseError !== null): ?>
            <p class="notice error" role="alert"><?= studentManagementEscape($databaseError) ?></p>
        <?php endif; ?>
        <?php if ($formError !== null): ?>
            <p class="notice error" role="alert"><?= studentManagementEscape($formError) ?></p>
        <?php endif; ?>

        <section class="panel form-panel" aria-labelledby="form-title">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow"><?= $editingStudent !== null ? 'Edit record' : 'New record' ?></p>
                    <h2 id="form-title"><?= $editingStudent !== null ? 'Update student' : 'Add a student' ?></h2>
                </div>
                <?php if ($editingStudent !== null): ?>
                    <a class="secondary-button" href="student_management.php">Cancel edit</a>
                <?php endif; ?>
            </div>
            <form method="post" action="student_management.php<?= $editingStudent !== null && isset($formValues['student_id']) ? '?edit=' . rawurlencode((string) $formValues['student_id']) : '' ?>" class="student-form">
                <input type="hidden" name="csrf_token" value="<?= studentManagementEscape($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= $editingStudent !== null ? 'update' : 'create' ?>">
                <?php if ($editingStudent !== null): ?>
                    <input type="hidden" name="student_id" value="<?= studentManagementEscape($formValues['student_id'] ?? '') ?>">
                <?php endif; ?>
                <label>
                    Student ID
                    <input name="student_code" maxlength="20" required value="<?= studentManagementEscape($formValues['student_code'] ?? '') ?>">
                </label>
                <label>
                    Full name
                    <input name="full_name" maxlength="120" required value="<?= studentManagementEscape($formValues['full_name'] ?? '') ?>">
                </label>
                <label>
                    Email
                    <input type="email" name="email" maxlength="254" required value="<?= studentManagementEscape($formValues['email'] ?? '') ?>">
                </label>
                <label>
                    Phone
                    <input type="tel" name="phone" maxlength="20" required value="<?= studentManagementEscape($formValues['phone'] ?? '') ?>">
                </label>
                <label>
                    Department
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= studentManagementEscape($department['department_id']) ?>" <?= (string) ($formValues['department_id'] ?? '') === (string) $department['department_id'] ? 'selected' : '' ?>>
                                <?= studentManagementEscape($department['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Course
                    <input name="course" maxlength="50" required value="<?= studentManagementEscape($formValues['course'] ?? '') ?>">
                </label>
                <label>
                    Semester
                    <input type="number" name="semester" min="1" max="12" required value="<?= studentManagementEscape($formValues['semester'] ?? '') ?>">
                </label>
                <label>
                    CGPA
                    <input type="number" name="cgpa" min="0" max="10" step="0.01" required value="<?= studentManagementEscape($formValues['cgpa'] ?? '') ?>">
                </label>
                <label>
                    Status
                    <select name="status" required>
                        <?php foreach (['Active', 'Inactive'] as $status): ?>
                            <option value="<?= studentManagementEscape($status) ?>" <?= ($formValues['status'] ?? 'Active') === $status ? 'selected' : '' ?>>
                                <?= studentManagementEscape($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-actions">
                    <button class="primary-button" type="submit"><?= $editingStudent !== null ? 'Save changes' : 'Add student' ?></button>
                </div>
            </form>
        </section>

        <section class="panel records-panel" aria-labelledby="records-title">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Student directory</p>
                    <h2 id="records-title">Records <span class="record-count"><?= count($students) ?></span></h2>
                </div>
            </div>
            <form method="get" action="student_management.php" class="filter-form">
                <label>
                    Search name, email, or course
                    <input type="search" name="q" value="<?= studentManagementEscape($query) ?>" placeholder="e.g. Aisha, example@email.com, BSc IT">
                </label>
                <label>
                    Filter by course
                    <select name="course">
                        <option value="">All courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= studentManagementEscape($course['course']) ?>" <?= $selectedCourse === $course['course'] ? 'selected' : '' ?>>
                                <?= studentManagementEscape($course['course']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="primary-button" type="submit">Search</button>
                    <a class="secondary-button" href="student_management.php">Clear</a>
                </div>
            </form>

            <?php if ($students === []): ?>
                <p class="empty-state">No student records match your search and filter.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Course / department</th>
                                <th scope="col">Semester</th>
                                <th scope="col">CGPA</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td data-label="Student">
                                        <strong><?= studentManagementEscape($student['full_name']) ?></strong>
                                        <span class="subtext"><?= studentManagementEscape($student['student_code']) ?></span>
                                    </td>
                                    <td data-label="Contact">
                                        <span><?= studentManagementEscape($student['email']) ?></span>
                                        <span class="subtext"><?= studentManagementEscape($student['phone']) ?></span>
                                    </td>
                                    <td data-label="Course / department">
                                        <span><?= studentManagementEscape($student['course']) ?></span>
                                        <span class="subtext"><?= studentManagementEscape($student['department_name']) ?></span>
                                    </td>
                                    <td data-label="Semester"><?= studentManagementEscape($student['semester']) ?></td>
                                    <td data-label="CGPA"><?= number_format((float) $student['cgpa'], 2) ?></td>
                                    <td data-label="Status"><span class="status <?= strtolower(studentManagementEscape($student['status'])) ?>"><?= studentManagementEscape($student['status']) ?></span></td>
                                    <td data-label="Actions" class="row-actions">
                                        <a class="secondary-button" href="student_management.php?edit=<?= studentManagementEscape($student['student_id']) ?>">Edit</a>
                                        <form method="post" action="student_management.php" onsubmit="return confirm('Delete <?= studentManagementEscape($student['student_code']) ?>? This action cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?= studentManagementEscape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="student_id" value="<?= studentManagementEscape($student['student_id']) ?>">
                                            <button class="danger-button" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
