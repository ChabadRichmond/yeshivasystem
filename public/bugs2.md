Continued bugs and feature requests. Continuing from bugs.txt

38. [DONE] Allow students to be assigned to an attendance taker even when already assigned to a primary teacher. Allow for multiple attendance takers. Allow for multiple primary teachers as well.

    REVISED IMPLEMENTATION (Teaching Groups Model):
    - Created `teaching_groups` table: Each class can have multiple teaching groups (e.g., "Shiur Aleph", "Shiur Beis")
    - Each teaching group has ONE primary teacher and MANY students
    - Created `class_attendance_takers` table: Attendance takers assigned separately per-student, per-class
    - A student's primary teacher comes from their teaching group assignment
    - A student's attendance taker can be different from their primary teacher

    New UI (class edit page):
    - Column 1: Class details + enrolled students
    - Column 2: Teaching groups (create groups, assign primary teacher, assign students)
    - Column 3: Attendance takers (optionally override who takes attendance per student)

39. [DONE] When adding a permission to a individual student add a start time as well, so that when student leaves midday they should still be accounted to beginning of the day as well as an end time in case they come back mid-day.
    - Added start_time and end_time columns to student_permissions table
    - Permission form now shows optional time fields
    - Attendance marking and reports are time-aware (only excludes students during permission hours)
    - Full-day permissions work as before (leave times blank)

40. [DONE] The percentage in the attendance statistics does not add up to the same percentage as in the individual profile, it's usually about a percentage point different, can you look into why this is.
    - Profile was using count-based calculation with 30-record limit
    - Updated to use time-based calculation matching ReportController
    - Now excludes cancelled sessions
    - Uses same rounding (1 decimal place)
    - Percentages should now match between profile and stats page
