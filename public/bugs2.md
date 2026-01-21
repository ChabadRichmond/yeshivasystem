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

41. Can we have all English States change to Hebrew, whether when choosing a date in the attendance itself, or when filtering dates and reports, or in the dates of individual reports. All dates in the system should be seen with Hebrew dates, as well as the default right now is to show from the beginning of the month the first, instead it should be showing the first of the Hebrew month Alef, meaning where you have the individual report default showing from the beginning of January 1st, while in the month of Shvat it should show from Alef Shvat till today.

42. The Mashgiach is very used to using a spreadsheet we are in the spreadsheet he is able to see a quick report of each week each student's attendance for every single class in that week as well as a sum up of minutes missed and percentage calculated, is there a way we can have a spreadsheet style view where he can easily it on one page see the total report of each student of that week with specific details for each class minutes missed and percentage of that week.

42. Any way to get the reports to load faster.